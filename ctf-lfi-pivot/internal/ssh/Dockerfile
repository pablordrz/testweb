FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y openssh-server sudo && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /var/run/sshd

# Usuario con credenciales débiles a propósito: admin / admin
RUN useradd -m -s /bin/bash admin && \
    echo 'admin:admin' | chpasswd

# Habilitar login por contraseña (deshabilitado por defecto en imágenes recientes)
RUN sed -i 's/#PasswordAuthentication yes/PasswordAuthentication yes/' /etc/ssh/sshd_config && \
    sed -i 's/PasswordAuthentication no/PasswordAuthentication yes/' /etc/ssh/sshd_config && \
    echo "PermitRootLogin no" >> /etc/ssh/sshd_config

# Flag: solo legible por el usuario admin, es decir, solo tras el login SSH
COPY flag2.txt /home/admin/flag2.txt
RUN chown admin:admin /home/admin/flag2.txt && chmod 600 /home/admin/flag2.txt

EXPOSE 22
CMD ["/usr/sbin/sshd", "-D"]
