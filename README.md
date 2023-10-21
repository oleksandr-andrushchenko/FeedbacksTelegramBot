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
* subscribe on specific feedbacks
* generate badge (picture) with username/rating to post in messengers
* "list my feedbacks" command
* "list feedbacks on me" command
* implement notifications on subscription matches
* manual payments
* ban users
* if users country/locale is out of bots country/locale - propose to go to native bot
* log new user activity
* allow to buy subscription even if already have one

* Simplify country selection
* Remove confirmation steps for premium-users
* -> Check other channels and put emojis & description as much as possible + html

* Everything should be extremely easy, simple and clear to understand!!!
* Add leave as on back button click
* Fix limits reply (counts miss-matches)
* Keep feedbacks, feedback searches and feedback lookups even if Limtis exceeded (notify user when found something - maybe as kfor payment before)
* Protect against multi-account (if search term is popular - ask for money even if limits not exceeded)
* Inject service locator in telegram channel for commands (coz many dependencies loadings)

* add bot id/country to payments/subscriptions
* add contacts info (table columns) for each bot
* check if bot can add subscribers - if so - use it just after start using the bot
* bug (?): when user already exists and then change bot - there is still country/locale of the user (not bots country/locale), solution is to
* 1) propose to keep/change current settings on start
* 2) move settings to messenger user layer (+ delete from user) and make messenger user unique per [messenger, id, bot]
* Implemented request_user for keyboard button when asking for search term type (as telegram user possible type - will receive ID!!)
* Add region1, region2 and locality selection steps for country conversation's custom flow (when address table is ready)
* Replace help replies using php not twig

* Location select on start ??
* Deploy
* Create groups
* Create adv

* Sync groups automatically
* Share link on channel's message with feedbacks (give a chance to discuss the feedback)
* Add SearchTermParser tests
* Cache db requests
* Media uploads (S3)
* Improve search term text normalization: single-spaces,multi-spaces, case/incese-sensitives
* Validation for search terms: min/max length, emojis, tags etc.
* Add already added texts/prev/next for all conversations (for example for country/locale convs)
* Rename Lookup -> LookupFeedback, $tg->getText -> $tg->getInput, getPossibleTypes -> getTypes()
* Cache level 1 regions for ua,ru