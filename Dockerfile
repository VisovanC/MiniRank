FROM php:8.2-cli-alpine

WORKDIR /app

COPY . /app

RUN php tests/run.php

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD wget -q -O /dev/null http://127.0.0.1:8000/login.php || exit 1

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]