#!/bin/bash

if ! docker container inspect phpdoc-parser-tests &>/dev/null; then
    docker run --detach --name phpdoc-parser-tests \
               --env MYSQL_ROOT_PASSWORD=verysecure \
               --env MYSQL_DATABASE=wordpress_tests \
               --publish-all mysql:8
elif [ "$(docker container inspect -f '{{.State.Running}}' phpdoc-parser-tests)" != "true" ]; then
    docker start phpdoc-parser-tests
fi

sed "s/WORDPRESS_DB_HOST=127.0.0.1/WORDPRESS_DB_HOST=127.0.0.1:$(docker port phpdoc-parser-tests 3306)/" .env.example > .env

