# CTF: LFI → Log Poisoning → RCE → Pivoting

## Arquitectura

```
                 [Atacante]
                     |
                 :8080 (host)
                     |
              ┌──────────────┐
              │  ctf-web     │  <- red "frontend" (expuesta)
              │  (PHP/Apache)│  <- red "internal"  (aislada, internal: true)
              └──────┬───────┘
                     |
              ┌──────┴───────┐
              │ ctf-internal │  <- SOLO en red "internal", sin ports publicados
              │ (servicio    │     -> inalcanzable desde el host/Internet
              │  vulnerable) │     -> solo accesible pivotando desde ctf-web
              └──────────────┘
```

La red `internal` está marcada como `internal: true` en Docker, lo que significa
que no tiene salida a la red del host ni a Internet: solo los contenedores
conectados a ella pueden verse entre sí. Así, `ctf-internal` es inalcanzable
para el "atacante" hasta que consiga ejecución de comandos en `ctf-web` y
pivote desde ahí (por ejemplo, escaneando `172.x.x.0/24` desde dentro del
contenedor web una vez tenga una shell).

## Cómo construir y levantar

```bash
cd ctf-lfi-pivot
docker compose build
docker compose up -d
```

La web queda accesible en `http://localhost:8080/index.php?page=welcome.php`.

## Fase 1 — LFI

`index.php` incluye directamente el parámetro `page` sin whitelist ni
sanitización (`include($_GET['page'])`). Esto permite leer archivos
arbitrarios del sistema usando path traversal, por ejemplo el log de Apache:

```
http://localhost:8080/index.php?page=../../../../var/log/apache2/access.log
```

## Fase 2 — Log Poisoning

Apache registra en el `access.log` (formato `combined`) cabeceras como el
`User-Agent` sin escapar su contenido. Si se envía una petición con un
`User-Agent` que contenga código PHP, ese código queda escrito literalmente
en el log. Al incluir después ese mismo log a través del LFI, PHP lo
interpreta como si fuera un script `.php`, logrando ejecución de código.

Es una técnica de explotación muy documentada (HackTricks, PortSwigger,
material de OSCP, etc.); no la desarrollo aquí paso a paso con payloads
listos para copiar/pegar, pero con la mecánica anterior tienes lo necesario
para diseñar y verificar el reto vosotros mismos, y para que quien resuelva
el CTF tenga que investigar y construir el payload.

Una vez logrado esto, tendrás ejecución de comandos en el contexto de
`www-data` dentro de `ctf-web`, con lo que se puede establecer una conexión
saliente hacia una máquina del atacante (reverse shell) usando cualquiera de
las técnicas estándar de post-explotación en PHP/bash que ya se enseñan en
cualquier curso de pentesting.

`flag1.txt` está en `/var/www/flag1.txt`, alcanzable una vez tengas
ejecución de comandos o LFI directo.

## Fase 3 — Pivoting hacia `ctf-internal`

Desde una shell dentro de `ctf-web` (que sí está en ambas redes), habría que:

1. Identificar el rango de la red `internal` (`ip a`, `/etc/hosts`, o resolviendo
   el nombre del servicio `internal-vuln` vía DNS interno de Docker).
2. Escanear puertos hacia `internal-vuln` (con lo que haya disponible dentro
   del contenedor: `bash -c 'echo > /dev/tcp/ip/puerto'`, `nc`, `python`, etc.,
   según lo que instales en la imagen de `ctf-web`). Debería aparecer el
   puerto 22 (SSH) abierto.
3. Conectarte por SSH a esa máquina probando credenciales débiles.

## Sobre la máquina 2 (`internal-vuln`): SSH con credenciales débiles

Esta máquina no explota ningún CVE ni backdoor: es simplemente un servidor
SSH (OpenSSH sobre Ubuntu 22.04) con un usuario `admin` cuya contraseña es
`admin`. El objetivo de esta fase es enseñar el riesgo de credenciales por
defecto/débiles y el uso de fuerza bruta ligera o intentos manuales tras
descubrir el puerto 22 abierto.

El `Dockerfile` en `internal/ssh/`:

1. Instala `openssh-server`.
2. Crea el usuario `admin` con contraseña `admin` (`chpasswd`).
3. Habilita `PasswordAuthentication yes` (deshabilititado por defecto en
   imágenes recientes) y `PermitRootLogin no` (para que no puedas conectar
   directamente como root, solo como `admin`).
4. Copia `flag2.txt` a `/home/admin/flag2.txt` con permisos `600`,
   propiedad de `admin`.

Solo necesitas `docker compose up --build -d` desde la raíz del proyecto;
no hace falta clonar nada externo ni compilar nada.

### Por qué esto obliga a llegar hasta el shell y no basta con el RCE plano de `ctf-web`

- El servicio SSH no expone lectura de ficheros por sí mismo: solo sirve
  para autenticar y abrir una sesión. No hay forma de leer `flag2.txt`
  sin **conocer las credenciales y establecer la sesión SSH** (que es la
  "shell" de esta segunda fase).
- Al no publicar el puerto 22 al host (solo vive en la red `internal`),
  además hace falta haber pivotado desde `ctf-web` para poder siquiera
  intentar conectarte.
- Desde `ctf-web` puedes probar la conexión con el cliente `ssh` si está
  instalado en la imagen, o instalarlo sobre la marcha si tienes acceso a
  paquetes desde ese contenedor:
  ```
  ssh admin@ctf-internal
  ```
  contraseña: `admin`.

`flag2.txt` queda así como la flag final del reto: solo se obtiene tras
completar las tres fases (LFI → log poisoning/RCE → pivoting → login SSH
con las credenciales débiles), nunca por atajo.

### Nota sobre dificultad

Al ser credenciales fijas y conocidas de antemano por quien monta el reto,
esta fase es intencionadamente más sencilla que un CVE real — pensada para
cerrar el reto con una fase de "credenciales débiles" clásica en vez de
sumar complejidad de explotación. Si más adelante quieres subir la
dificultad, se puede sustituir fácilmente por una lista de contraseñas a
adivinar (fuerza bruta con `hydra`/`medusa` contra un diccionario pequeño)
en lugar de una credencial ya conocida.
