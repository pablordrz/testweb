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
laboratorio propio. El `Dockerfile` en `internal/vsftpd/` construye la
máquina a partir de un binario ya compilado y de acceso público, alojado en
un repo hecho explícitamente para pruebas de exploits/scanners:

- **Anon-Exploiter/vulnerable-packages**
  (https://github.com/Anon-Exploiter/vulnerable-packages): incluye el
  binario de vsftpd 2.3.4 con el backdoor histórico ya compilado (el mismo
  documentado en HackTricks, Exploit-DB, Rapid7, etc.), evitando depender de
  compilar desde fuentes o de imágenes de terceros que puedan desaparecer.

  > Nota: la alternativa más conocida, `vitalyford/vsftpd-2.3.4-vulnerable`,
  > depende de una imagen de Docker Hub (`nksnksnks/vsftpd.2.3.4-vuln-osvdb-73573`)
  > que ya no existe (fallo reportado desde 2019 sin solución), así que no
  > la uses como base.

### Cómo se integra

Ya está integrado: `docker-compose.yml` construye `internal-vuln` desde
`./internal/vsftpd`, cuyo `Dockerfile`:

1. Clona el repo anterior en tiempo de build (necesita acceso a GitHub
   desde el host donde ejecutes `docker compose build`).
2. Coloca el binario en `/usr/local/sbin/vsftpd` y su configuración en
   `/etc/vsftpd.conf`.
3. Copia `flag2.txt` (en la misma carpeta) a `/root/flag2.txt` con permisos
   `600`.
4. Arranca vsftpd como root vía `/root/run.sh` al iniciar el contenedor.

Solo necesitas `docker compose up --build -d` desde la raíz del proyecto;
no hace falta clonar nada a mano.

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
