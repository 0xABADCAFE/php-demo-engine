FROM php:7.4.16-cli
RUN docker-php-ext-install sockets pcntl
COPY . /usr/src/php-demo-engine
WORKDIR /usr/src/php-demo-engine
CMD [ "php", "display" ]
