ARG IMAGE=php:latest
FROM $IMAGE

RUN apt-get update -yqq &&  apt-get install git libcurl4-openssl-dev -yqq
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install curl
WORKDIR /app
COPY . /app
RUN echo $" \n\
// local config\n\
define('DB_HOST', 'mysql');\n\
define('DB_USER', 'root');\n\
define('DB_PWD', 'mysql');\n\
define('DB_NAME', 'lynxphp');\n\
" > backend/config_.php
CMD ["tests/run.sh"]
