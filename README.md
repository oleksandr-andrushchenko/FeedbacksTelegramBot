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
* php bin/console telegram:bot:import telegram_bots.csv --no-interaction
* php bin/console telegram:channel:import telegram_channels.csv --no-interaction

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
* Replace help replies using php not twig

* Create adv

* Sync groups automatically
* Share link on channel's message with feedbacks (give a chance to discuss the feedback)
* Cache db requests
* Media uploads (S3)
* Improve search term text normalization: single-spaces,multi-spaces, case/incese-sensitives
* Validation for search terms: min/max length, emojis, tags etc.
* Re-factor: remove dots from questions, searchTerms: duplicates validation
* Make validators configurable via settings (options)
* More money: lock feedbacks and ask money for it
* Apply optimization tricks/tips for symfony prod application
* move translation out of the repo
* check doctrine queries, optimize if need
* if phone number added (create command) and rating is good - ask phone number in order to no allow creation on self
* facebook bot
* process old non-stopped conversations
* process new feedbacks (on me, on interested people) notification
* process new feedbacks search (on me, on interested people) notification
* add notification about new feedbacks in the channel
* cache bots as files
* update bot_ids for messenger users
* check and handle bot exit event (update messengerUser, for what: to know if send notifications for example, to understand how many people left the bot)


* create all possible search terms (for each type) in case of unknown selected????
* todo notifications
* normalize UA phones
* integrate more databases
* process and log new member update callback