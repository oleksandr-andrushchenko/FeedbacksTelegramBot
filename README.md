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
* if users country/locale is out of bots country/locale - propose to go to native bot
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

* groups: -1001645239372 (dev), -1001673132934 (local)
* admins: 409525390
* add Locale to bot, in this case eash locale will have their own localized group
* add bot id/country to payments/subscriptions
* on country/locale change - and if such bot exists - propose user to switch onto it
* switch from groups to channels
* soft deletes mode (flag) for telegram conversations (will be useful for initial tracks)
* Twitter search term parse update