FROM webdevops/php-nginx:8.1-alpine

COPY . /app

RUN chmod -R 755 /app && \
    chmod 640 /app/.env

EXPOSE 80

CMD ["supervisord"]
