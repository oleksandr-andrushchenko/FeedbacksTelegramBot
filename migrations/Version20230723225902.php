<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230723225902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE telegram_payments (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, messenger_user_id INT UNSIGNED DEFAULT NULL, uuid VARCHAR(32) NOT NULL, chat_id BIGINT NOT NULL, method SMALLINT UNSIGNED NOT NULL, purpose VARCHAR(255) NOT NULL, price_amount NUMERIC(7, 2) NOT NULL, price_currency VARCHAR(255) NOT NULL, payload JSON NOT NULL, pre_checkout_query JSON DEFAULT NULL, status SMALLINT UNSIGNED DEFAULT 0 NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_31578A7AD17F50A6 (uuid), INDEX IDX_31578A7A9E4CEF7D (messenger_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE telegram_payments ADD CONSTRAINT FK_31578A7A9E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_payments DROP FOREIGN KEY FK_31578A7A9E4CEF7D');
        $this->addSql('DROP TABLE telegram_payments');
    }
}
