FROM php:8.2-apache

RUN a2enmod rewrite

WORKDIR /var/www/html

# 修改 Apache 文档根目录为 public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --chown=www-data:www-data . /var/www/html

RUN mkdir -p /var/www/html/log/orders \
    && chown -R www-data:www-data /var/www/html/log \
    && chmod -R 775 /var/www/html/log

EXPOSE 80
