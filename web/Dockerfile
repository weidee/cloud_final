FROM php:8.1-apache

# 啟用 rewrite (如果需要)
RUN a2enmod rewrite

# 複製 web 目錄底下所有檔案到 /var/www/html
COPY . /var/www/html/

# 設定 www-data 使用者可寫入 /var/www/html (若需要)
RUN chown -R www-data:www-data /var/www/html

# 容器內 /var/www/html/index.php 可以作為預設首頁
EXPOSE 80

CMD ["apache2-foreground"]

