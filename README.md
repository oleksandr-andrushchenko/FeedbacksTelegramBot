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
* generate badge (picture) with username/rating to post in messengers
* "list my feedbacks" command
* "list feedbacks on me" command
* manual payments
* ban users
* allow to buy subscription even if already have one

* Simplify country selection
* Remove confirmation steps for premium-users
* -> Check other channels and put emojis & description as much as possible + html

* Everything should be extremely easy, simple and clear to understand!!!
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
* cache bots as files


* create all possible search terms (for each type) in case of unknown selected????
* integrate more databases
* add overal term rating to feedback
* improve all types of searching
* fix bug with empty keyboard when error occurs in message handlers
* ask user to leave a phone number or email for future notifications (is anybody gonna search him)
* add context to search parsers and improve parsing
* add results couns field to feeedbacK-search
* add icons for search result lists
* implement the same logic for clarity orgs (as for persons was mada)
* implement search by tax_number for orgs
* add lookup processors test coverage
* implement rpvoder search term variants as keyboard buttons
* improve secrets for phone searching (add more)
* array_unique for feedbacks & feedback searches
* wrap all search records into object with parsed link (and show it for premium users)
* improve secrets modifier, add brackets for secreted values (just user to know what is hidden)
* add ru names support for blackbox search provider
* add successfull indicators for absense in bad registries
* add PersonName::matchFirstName method (is will check across all equal meaning names), implement instead of PersonName::getFirst() === $compare