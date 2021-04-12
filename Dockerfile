FROM php:8.0-cli
RUN docker-php-ext-install sockets pcntl
COPY . /usr/src/php-demo-engine
WORKDIR /usr/src/php-demo-engine
CMD [ "php", "display" ]