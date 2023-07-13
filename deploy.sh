#!/bin/sh
#set -e
docker compose run php composer install --prefer-dist --optimize-autoloader --no-dev
docker compose run php php bin/console cache:clear --env=prod
docker compose run php php bin/console cache:warmup --env=prod

#serverless deploy --stage=prod
serverless deploy

#get back dev env
docker compose run php composer install
docker compose run php php bin/console cache:warmup

#serverless bref:cli --args="doctrine:database:create"
#serverless bref:cli --args="doctrine:migrations:migrate --no-interaction"
#serverless bref:cli --args="telegram:webhook:update feedbacks"
#serverless bref:cli --args="telegram:commands:update feedbacks"
#serverless bref:cli --args="telegram:description:update feedbacks"


#serverless logs -f web --tail