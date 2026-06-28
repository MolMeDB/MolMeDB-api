FROM postgres:15

WORKDIR /opt/bingo

COPY . /opt/bingo/

RUN mkdir -p /usr/lib/postgresql/15/lib/ \
    && cp /opt/bingo/lib/libbingo-postgres.so /usr/lib/postgresql/15/lib/

RUN echo "shared_buffers = 128MB" >> /usr/share/postgresql/postgresql.conf \
    && echo "kernel.shmmax = 8179869184" >> /etc/sysctl.conf \
    && echo "kernel.shmall = 4194304" >> /etc/sysctl.conf

# RUN sysctl -p 

WORKDIR /opt/bingo/

RUN chmod +x bingo-pg-install.sh && \
    sh ./bingo-pg-install.sh -libdir /opt/bingo/lib -y && \
    chown postgres:postgres /opt/bingo/lib/libbingo-postgres.so && \
    cp -p /opt/bingo/lib/libbingo-postgres.so /usr/lib/postgresql/15/lib/libbingo-postgres.so && \
    install -m 0644 /opt/bingo/bingo_install.sql /docker-entrypoint-initdb.d/10-bingo.sql
