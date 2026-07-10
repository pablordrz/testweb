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
   puerto 21 (FTP) abierto.
3. Explotar el servicio vulnerable de esa segunda máquina.

## Sobre la máquina 2 (`internal-vuln`): vsftpd 2.3.4 backdoored (CVE-2011-2523)

No incluyo aquí código de explotación (el backdoor en sí, ni un exploit
listo para copiar/pegar): no es algo que escriba, ni aunque el fin sea un
laboratorio propio. Lo que sí te recomiendo, y es el estándar en la
comunidad para montar exactamente este reto, es usar un proyecto ya
existente y pensado para cursos de ciberseguridad:

- **vitalyford/vsftpd-2.3.4-vulnerable**
  (https://github.com/vitalyford/vsftpd-2.3.4-vulnerable): repo con un
  `Dockerfile` que compila la versión de vsftpd 2.3.4 con el backdoor
  histórico (el mismo que documentan HackTricks, Exploit-DB, Rapid7, etc.).
  Pensado explícitamente para prácticas de pentesting en clase.

### Cómo integrarlo

```bash
cd internal
git clone https://github.com/vitalyford/vsftpd-2.3.4-vulnerable.git vsftpd-2.3.4-vulnerable
```

El `docker-compose.yml` de este proyecto ya referencia esa carpeta como
`build:` del servicio `internal-vuln`. Ese repo trae su propio
`docker-compose.yml` pensado para 12 equipos con puertos `20XX`/`62XX`;
para este reto (una sola instancia, sin puertos publicados al host) basta
con quedarte con su `Dockerfile` y dejar que nuestro `docker-compose.yml`
raíz orqueste el build — no hace falta usar el `docker-compose.yml` interno
del repo clonado.

Sustituye el fichero de secreto que trae el repo (`secret.txt`) por tu
propia flag, manteniendo el mismo nombre y ruta que use su `Dockerfile`
(revisa el `COPY`/`RUN` correspondiente al clonarlo), o añade una línea
`COPY flag2.txt /root/flag2.txt` al final de ese `Dockerfile` si prefieres
mantenerlo separado. Un ejemplo de flag ya preparado con el contenido
esperado está en `internal/vsftpd/flag2.txt` de este repo — cópialo dentro
de la carpeta clonada antes de construir la imagen.

### Por qué esto obliga a usar la reverse/bind shell y no solo el RCE plano

El backdoor de CVE-2011-2523 se dispara al iniciar sesión FTP con un
usuario que contenga la cadena `":)"`, y abre una shell en el puerto 6200
del propio proceso `vsftpd`, que corre como **root** al arrancar (necesita
privilegios para bindear el puerto 21). Eso significa:

- **No hay ningún camino para leer la flag sin ejecución de comandos**: el
  servicio FTP en sí no expone lectura de ficheros arbitraria, solo
  transferencia dentro del árbol FTP configurado (que no incluye `/root`).
- **La flag solo es accesible una vez conectas a la shell del backdoor**
  (`telnet <ip> 6200` tras disparar el login malicioso), que es
  efectivamente la "reverse/bind shell" de esta segunda fase: hasta ese
  momento el fichero es completamente inalcanzable desde fuera.
- Al no publicar el puerto 6200 (ni el 21) al host, además hace falta haber
  pivotado desde `ctf-web` para poder siquiera intentarlo.

`flag2.txt` queda así como la flag final del reto: solo se obtiene tras
completar las tres fases (LFI → log poisoning/RCE → pivoting → explotación
del backdoor), nunca por atajo.
