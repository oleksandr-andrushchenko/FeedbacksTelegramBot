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
* implement notifications on subscription matches
* after country selection - link to che channel
* manual payments
* ban users
* mirrors should redirect users to primary bot
* each primary bot should have country(and/or locale) and if user is out of this country/locale - propose to go to native bot
* log new user activity
* allow to buy subscription even if already have one
* ask about custom message before propose typing in contact command

* Simplify country selection
* Create & implement experienced mode for user: no press menu button text, less texts, shorter texts etc. (mode enabled automatically after activity checks with/without user confirmation)
* Remove confirmation steps for premium-users
* -> Check other channels and put emojis & description as much as possible + html

* Everything should be extremely easy, simple and clear to understand!!!
* Add leave as on back button click
* Fix limits reply (counts miss-matches)
* Keep feedbacks, feedback searches and feedback lookups even if Limtis exceeded (notify user when found something - maybe as kfor payment before)
* Protect against multi-account (if search term is popular - ask for money even if limits not exceeded)
* MOve all render logic into views
* Inject service locator in telegram channel for commands (coz many dependencies loadings)
* Add car number as search term type
* Custom search term types for each country (hide non-popular - show on demand)