# Serverless
* https://bref.sh/docs/

# Run ngrok tunnel
`ngrok http --host-header=rewrite http://localhost:8000`

# Refresh db
* php bin/console doctrine:database:drop --force
* php bin/console doctrine:database:create
* rm -rf migrations/*
* php bin/console doctrine:migrations:diff
* php bin/console doctrine:migrations:migrate

# Create test db
* docker compose exec -it mysql sh
* mysql -uroot -p1111
* CREATE DATABASE IF NOT EXISTS app_test;
* GRANT ALL PRIVILEGES ON app_test.* TO 'app'@'%';

# TODO
* queues
* soft deletes
* subscribe on specific feedbacks
* generate badge (picture) with username/rating to post in messengers
* "list my feedbacks" command
* "list feedbacks on me" command
* -> "subscribe on mine/somebodies feedbacks" command (or, for beginning, - just say smth like "we gonna notify you when smbd left smth")
* after country selection - link to che channel
* add command: how many times user X were been searched for (top command, usually - it gonna be current account - search for itself, but how many times somebody were searching me)
* manual payments
* ban users
* mirrors should redirect users to primary bot
* each primary bot should have country(and/or locale) and if user is out of this country/locale - propose to go to native bot
* log new user activity