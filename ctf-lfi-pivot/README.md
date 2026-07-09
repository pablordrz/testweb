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
   según lo que instales en la imagen de `ctf-web`).
3. Explotar el servicio vulnerable de esa segunda máquina.

## Sobre la máquina 2 (`internal-vuln`)

No he incluido código de explotación (exploits/backdoors) para esta parte:
no es algo que pueda escribir, ni aunque el fin sea un laboratorio propio.
Lo que sí te recomiendo, y es de hecho el estándar en la comunidad para
montar este tipo de retos, es usar una imagen ya existente y mantenida con
un servicio *real* en versión vulnerable (no un backdoor artificial):

- **Vulhub** (https://github.com/vulhub/vulhub): repositorio con Dockerfiles
  listos para decenas de CVEs reales (Samba `usermap_script` CVE-2007-2447,
  ProFTPD `mod_copy` CVE-2015-3306, Apache Struts2 CVE-2017-5638, Drupal
  Drupalgeddon2, etc.). Basta con clonar el repo y usar la carpeta del CVE
  que elijas como `build:` de `internal-vuln` en el `docker-compose.yml`.
- **Metasploitable2 / Metasploitable3**: imagen ya empaquetada con múltiples
  servicios vulnerables, pensada exactamente para este uso.

Estos proyectos ya resuelven además el problema de encontrar paquetes de
versiones antiguas de software (repositorios EOL, etc.), que si lo haces a
mano desde cero puede ser bastante frágil.

### Servicio ya integrado en este proyecto: Samba 4.6.3 (CVE-2017-7494 / SambaCry)

El `docker-compose.yml` ya usa `vulhub/samba:4.6.3`, montando:

- `internal/smb.conf`: define un share `myshare` en `/home/share` con
  `guest ok = yes` y `read only = no` (acceso anónimo de escritura, requisito
  del CVE).
- `internal/share`: carpeta del host montada como ese share. Si falla la
  escritura desde el contenedor, dale permisos abiertos (`chmod 777`), es un
  laboratorio aislado.

El servicio está **solo** en la red `internal`, sin `ports:` publicados al
host: solo es alcanzable pivotando desde `ctf-web`.

`flag2.txt` se monta en `/root/flag2.txt` dentro del contenedor Samba,
**fuera** del share compartido (`/home/share`), para que haga falta lograr
ejecución de código real (RCE) y no baste con leer/escribir en el share
anónimo. Si tu exploit no se ejecuta como root, ajusta la ruta del montaje
en `docker-compose.yml` al usuario con el que sí obtengas ejecución.

Para el propio ataque a esa máquina (una vez montada), busca el PoC público
del CVE que elijas en Exploit-DB / el módulo correspondiente de Metasploit;
son recursos ya existentes y de libre consulta, no necesitas que yo genere
ese código.
