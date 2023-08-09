<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230809065340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feedback_searches (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED DEFAULT NULL, messenger_user_id INT UNSIGNED DEFAULT NULL, search_term_messenger_user_id INT UNSIGNED DEFAULT NULL, search_term_text VARCHAR(255) NOT NULL, search_term_normalized_text VARCHAR(255) NOT NULL, search_term_type SMALLINT UNSIGNED NOT NULL, search_term_messenger SMALLINT UNSIGNED DEFAULT NULL, search_term_messenger_username VARCHAR(255) DEFAULT NULL, is_premium TINYINT(1) NOT NULL, country_code VARCHAR(2) DEFAULT NULL, locale_code VARCHAR(2) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2F56936BA76ED395 (user_id), INDEX IDX_2F56936B9E4CEF7D (messenger_user_id), INDEX IDX_2F56936B2333E4FC (search_term_messenger_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feedback_user_subscriptions (id INT UNSIGNED AUTO_INCREMENT NOT NULL, payment_id BIGINT UNSIGNED DEFAULT NULL, messenger_user_id INT UNSIGNED DEFAULT NULL, subscription_plan SMALLINT UNSIGNED NOT NULL, expire_at DATETIME NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_F0E0BB8A4C3A3BB (payment_id), INDEX IDX_F0E0BB8A9E4CEF7D (messenger_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feedbacks (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED DEFAULT NULL, messenger_user_id INT UNSIGNED DEFAULT NULL, search_term_messenger_user_id INT UNSIGNED DEFAULT NULL, search_term_text VARCHAR(255) NOT NULL, search_term_normalized_text VARCHAR(255) NOT NULL, search_term_type SMALLINT UNSIGNED NOT NULL, search_term_messenger SMALLINT UNSIGNED DEFAULT NULL, search_term_messenger_username VARCHAR(255) DEFAULT NULL, rating SMALLINT NOT NULL, description LONGTEXT DEFAULT NULL, is_premium TINYINT(1) NOT NULL, country_code VARCHAR(2) DEFAULT NULL, locale_code VARCHAR(2) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7E6C3F89A76ED395 (user_id), INDEX IDX_7E6C3F899E4CEF7D (messenger_user_id), INDEX IDX_7E6C3F892333E4FC (search_term_messenger_user_id), INDEX IDX_7E6C3F899D568F55 (search_term_normalized_text), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_users (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED DEFAULT NULL, messenger SMALLINT UNSIGNED NOT NULL, identifier VARCHAR(255) NOT NULL, username VARCHAR(255) DEFAULT NULL, locale_code VARCHAR(5) DEFAULT NULL, is_show_hints TINYINT(1) DEFAULT true NOT NULL, is_show_extended_keyboard TINYINT(1) DEFAULT false NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2F5DF3DDA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_bots (id SMALLINT UNSIGNED AUTO_INCREMENT NOT NULL, primary_bot_id SMALLINT UNSIGNED DEFAULT NULL, username VARCHAR(32) NOT NULL, token VARCHAR(46) NOT NULL, country_code VARCHAR(2) NOT NULL, locale_code VARCHAR(2) NOT NULL, group_name SMALLINT UNSIGNED NOT NULL, is_check_updates TINYINT(1) DEFAULT true NOT NULL, is_check_requests TINYINT(1) DEFAULT true NOT NULL, is_accept_payments TINYINT(1) DEFAULT false NOT NULL, is_admin_only TINYINT(1) DEFAULT true NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_DACD6EDF85E0677 (username), INDEX IDX_DACD6ED6A367C37 (primary_bot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_conversations (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, messenger_user_id INT UNSIGNED DEFAULT NULL, bot_id SMALLINT UNSIGNED DEFAULT NULL, chat_id BIGINT NOT NULL, class VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, state JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7B5D79B89E4CEF7D (messenger_user_id), INDEX IDX_7B5D79B892C1C487 (bot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_payments (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, messenger_user_id INT UNSIGNED DEFAULT NULL, bot_id SMALLINT UNSIGNED DEFAULT NULL, uuid VARCHAR(32) NOT NULL, chat_id BIGINT NOT NULL, method SMALLINT UNSIGNED NOT NULL, purpose VARCHAR(255) NOT NULL, price_amount NUMERIC(7, 2) NOT NULL, price_currency VARCHAR(255) NOT NULL, payload JSON NOT NULL, pre_checkout_query JSON DEFAULT NULL, successful_payment JSON DEFAULT NULL, status SMALLINT UNSIGNED DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_31578A7AD17F50A6 (uuid), INDEX IDX_31578A7A9E4CEF7D (messenger_user_id), INDEX IDX_31578A7A92C1C487 (bot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_requests (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, bot_id SMALLINT UNSIGNED DEFAULT NULL, method VARCHAR(32) NOT NULL, chat_id BIGINT DEFAULT NULL, inline_message_id BIGINT DEFAULT NULL, data JSON NOT NULL, response JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2F00C71992C1C487 (bot_id), INDEX IDX_2F00C7198B8E84281A9A7125F86D5A67 (created_at, chat_id, inline_message_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_updates (id BIGINT UNSIGNED NOT NULL, bot_id SMALLINT UNSIGNED DEFAULT NULL, data JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_1E0E72C692C1C487 (bot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_feedback_messages (id INT UNSIGNED AUTO_INCREMENT NOT NULL, messenger_user_id INT UNSIGNED DEFAULT NULL, user_id INT UNSIGNED DEFAULT NULL, text VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6C530ECF9E4CEF7D (messenger_user_id), INDEX IDX_6C530ECFA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, locale_code VARCHAR(2) DEFAULT NULL, rating SMALLINT DEFAULT NULL, country_code VARCHAR(2) DEFAULT NULL, phone_number BIGINT UNSIGNED DEFAULT NULL, email VARCHAR(128) DEFAULT NULL, subscription_expire_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', purged_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE feedback_searches ADD CONSTRAINT FK_2F56936BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE feedback_searches ADD CONSTRAINT FK_2F56936B9E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedback_searches ADD CONSTRAINT FK_2F56936B2333E4FC FOREIGN KEY (search_term_messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedback_user_subscriptions ADD CONSTRAINT FK_F0E0BB8A4C3A3BB FOREIGN KEY (payment_id) REFERENCES telegram_payments (id)');
        $this->addSql('ALTER TABLE feedback_user_subscriptions ADD CONSTRAINT FK_F0E0BB8A9E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedbacks ADD CONSTRAINT FK_7E6C3F89A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE feedbacks ADD CONSTRAINT FK_7E6C3F899E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedbacks ADD CONSTRAINT FK_7E6C3F892333E4FC FOREIGN KEY (search_term_messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE messenger_users ADD CONSTRAINT FK_2F5DF3DDA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE telegram_bots ADD CONSTRAINT FK_DACD6ED6A367C37 FOREIGN KEY (primary_bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('ALTER TABLE telegram_conversations ADD CONSTRAINT FK_7B5D79B89E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE telegram_conversations ADD CONSTRAINT FK_7B5D79B892C1C487 FOREIGN KEY (bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('ALTER TABLE telegram_payments ADD CONSTRAINT FK_31578A7A9E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE telegram_payments ADD CONSTRAINT FK_31578A7A92C1C487 FOREIGN KEY (bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('ALTER TABLE telegram_requests ADD CONSTRAINT FK_2F00C71992C1C487 FOREIGN KEY (bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('ALTER TABLE telegram_updates ADD CONSTRAINT FK_1E0E72C692C1C487 FOREIGN KEY (bot_id) REFERENCES telegram_bots (id)');
        $this->addSql('ALTER TABLE user_feedback_messages ADD CONSTRAINT FK_6C530ECF9E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE user_feedback_messages ADD CONSTRAINT FK_6C530ECFA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_searches DROP FOREIGN KEY FK_2F56936BA76ED395');
        $this->addSql('ALTER TABLE feedback_searches DROP FOREIGN KEY FK_2F56936B9E4CEF7D');
        $this->addSql('ALTER TABLE feedback_searches DROP FOREIGN KEY FK_2F56936B2333E4FC');
        $this->addSql('ALTER TABLE feedback_user_subscriptions DROP FOREIGN KEY FK_F0E0BB8A4C3A3BB');
        $this->addSql('ALTER TABLE feedback_user_subscriptions DROP FOREIGN KEY FK_F0E0BB8A9E4CEF7D');
        $this->addSql('ALTER TABLE feedbacks DROP FOREIGN KEY FK_7E6C3F89A76ED395');
        $this->addSql('ALTER TABLE feedbacks DROP FOREIGN KEY FK_7E6C3F899E4CEF7D');
        $this->addSql('ALTER TABLE feedbacks DROP FOREIGN KEY FK_7E6C3F892333E4FC');
        $this->addSql('ALTER TABLE messenger_users DROP FOREIGN KEY FK_2F5DF3DDA76ED395');
        $this->addSql('ALTER TABLE telegram_bots DROP FOREIGN KEY FK_DACD6ED6A367C37');
        $this->addSql('ALTER TABLE telegram_conversations DROP FOREIGN KEY FK_7B5D79B89E4CEF7D');
        $this->addSql('ALTER TABLE telegram_conversations DROP FOREIGN KEY FK_7B5D79B892C1C487');
        $this->addSql('ALTER TABLE telegram_payments DROP FOREIGN KEY FK_31578A7A9E4CEF7D');
        $this->addSql('ALTER TABLE telegram_payments DROP FOREIGN KEY FK_31578A7A92C1C487');
        $this->addSql('ALTER TABLE telegram_requests DROP FOREIGN KEY FK_2F00C71992C1C487');
        $this->addSql('ALTER TABLE telegram_updates DROP FOREIGN KEY FK_1E0E72C692C1C487');
        $this->addSql('ALTER TABLE user_feedback_messages DROP FOREIGN KEY FK_6C530ECF9E4CEF7D');
        $this->addSql('ALTER TABLE user_feedback_messages DROP FOREIGN KEY FK_6C530ECFA76ED395');
        $this->addSql('DROP TABLE feedback_searches');
        $this->addSql('DROP TABLE feedback_user_subscriptions');
        $this->addSql('DROP TABLE feedbacks');
        $this->addSql('DROP TABLE messenger_users');
        $this->addSql('DROP TABLE telegram_bots');
        $this->addSql('DROP TABLE telegram_conversations');
        $this->addSql('DROP TABLE telegram_payments');
        $this->addSql('DROP TABLE telegram_requests');
        $this->addSql('DROP TABLE telegram_updates');
        $this->addSql('DROP TABLE user_feedback_messages');
        $this->addSql('DROP TABLE users');
    }
}
