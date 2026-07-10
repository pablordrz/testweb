FROM ubuntu:18.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update -y && apt-get install -y git

WORKDIR /root/

# Repo de referencia con el binario vsftpd 2.3.4 backdoored ya compilado
# (evita depender de imágenes de terceros ya desaparecidas de Docker Hub).
RUN git clone https://github.com/Anon-Exploiter/vulnerable-packages && \
    cd vulnerable-packages && \
    cd backdoored-vsftpd-2.3.4 && \
    mv * /root/ && \
    cd /root && \
    rm -rfv vulnerable-packages

RUN apt-get purge -y git && \
    apt-get -y autoclean && \
    apt-get -y autoremove && \
    apt-get -y clean

RUN mkdir -p /usr/share/empty/ && \
    mkdir -p /var/ftp/ && \
    useradd -d /var/ftp ftp

RUN chown root.root /var/ftp && \
    chmod og-w /var/ftp

RUN mv vsftpd /usr/local/sbin/vsftpd && \
    mv vsftpd.conf /etc/

# Flag final del reto: solo accesible tras disparar el backdoor (puerto 6200),
# ya que vsftpd corre como root y el fichero no está dentro de ningún share FTP.
COPY flag2.txt /root/flag2.txt
RUN chmod 600 /root/flag2.txt

RUN echo 'echo Started FTP Server@ `hostname -i 2>/dev/null`:21' > /root/run.sh && \
    echo "/usr/local/sbin/vsftpd" >> /root/run.sh

CMD ["/bin/bash", "-c", "/bin/bash run.sh"]
