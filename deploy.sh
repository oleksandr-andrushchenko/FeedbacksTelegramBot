#!/bin/bash

if [ $# -eq 0 ]; then
  echo "stage argument is required"
  exit 1
fi

stages=("dev" "prod")
value="\<${1}\>"

# shellcheck disable=SC2199
if [[ ${stages[@]} =~ $value ]]; then
  echo "continue with $1 stage"
else
  echo "invalid stage"
  exit 1
fi

kernelLogDir=$(docker compose run php sed -n '29p' vendor/bref/symfony-bridge/src/BrefKernel.php)

if [[ $kernelLogDir == //* ]]; then
    echo "kernel log dir has been changed"
    exit 1
fi

if ! docker compose run -e FORCE_SKIPPED=1 php php bin/phpunit; then
    echo "some test has been failed"
    exit 1
fi

docker compose run php composer install --prefer-dist --optimize-autoloader --no-dev
docker compose run php php bin/console cache:clear --env=prod
docker compose run php php bin/console cache:warmup --env=prod

serverless deploy --stage=$1

#serverless bref:cli --args="doctrine:database:create" --stage=prod
serverless bref:cli --args="doctrine:migrations:migrate --no-interaction --all-or-nothing" --stage=$1

docker compose run php composer install
docker compose run php php bin/console cache:warmup

#serverless logs -f web --tail
