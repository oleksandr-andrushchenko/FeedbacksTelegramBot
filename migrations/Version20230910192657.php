<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230910192657 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_bots ADD name VARCHAR(1024) NOT NULL, ADD bot_group_name SMALLINT UNSIGNED NOT NULL, ADD check_updates TINYINT(1) DEFAULT 1 NOT NULL, ADD check_requests TINYINT(1) DEFAULT 1 NOT NULL, ADD accept_payments TINYINT(1) DEFAULT 0 NOT NULL, ADD admin_only TINYINT(1) DEFAULT 1 NOT NULL, ADD texts_set TINYINT(1) DEFAULT 0 NOT NULL, ADD webhook_set TINYINT(1) DEFAULT 0 NOT NULL, ADD commands_set TINYINT(1) DEFAULT 0 NOT NULL, DROP is_check_updates, DROP is_check_requests, DROP is_accept_payments, DROP is_admin_only, DROP is_webhook_set, DROP is_texts_set, DROP is_commands_set, CHANGE group_name group_name VARCHAR(1024) DEFAULT NULL, CHANGE activity_telegram_group_username group_username VARCHAR(32) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_bots ADD is_check_updates TINYINT(1) DEFAULT 1 NOT NULL, ADD is_check_requests TINYINT(1) DEFAULT 1 NOT NULL, ADD is_accept_payments TINYINT(1) DEFAULT 0 NOT NULL, ADD is_admin_only TINYINT(1) DEFAULT 1 NOT NULL, ADD is_webhook_set TINYINT(1) DEFAULT 0 NOT NULL, ADD is_texts_set TINYINT(1) DEFAULT 0 NOT NULL, ADD is_commands_set TINYINT(1) DEFAULT 0 NOT NULL, DROP name, DROP bot_group_name, DROP check_updates, DROP check_requests, DROP accept_payments, DROP admin_only, DROP texts_set, DROP webhook_set, DROP commands_set, CHANGE group_name group_name SMALLINT UNSIGNED NOT NULL, CHANGE group_username activity_telegram_group_username VARCHAR(32) DEFAULT NULL');
    }
}
