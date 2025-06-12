# Dockerfile.php
FROM php:8.3-fpm

RUN apt-get update && \
    apt-get install -y docker.io curl && \
    curl -SL https://github.com/docker/compose/releases/download/v2.29.2/docker-compose-linux-x86_64 -o /usr/local/bin/docker-compose && \
    chmod +x /usr/local/bin/docker-compose