<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230724012610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feedback_user_subscriptions (id INT UNSIGNED AUTO_INCREMENT NOT NULL, payment_id BIGINT UNSIGNED DEFAULT NULL, messenger_user_id INT UNSIGNED DEFAULT NULL, subscription_plan SMALLINT UNSIGNED NOT NULL, expire_at DATETIME NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_F0E0BB8A4C3A3BB (payment_id), INDEX IDX_F0E0BB8A9E4CEF7D (messenger_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE feedback_user_subscriptions ADD CONSTRAINT FK_F0E0BB8A4C3A3BB FOREIGN KEY (payment_id) REFERENCES telegram_payments (id)');
        $this->addSql('ALTER TABLE feedback_user_subscriptions ADD CONSTRAINT FK_F0E0BB8A9E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE telegram_payments ADD successful_payment JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_user_subscriptions DROP FOREIGN KEY FK_F0E0BB8A4C3A3BB');
        $this->addSql('ALTER TABLE feedback_user_subscriptions DROP FOREIGN KEY FK_F0E0BB8A9E4CEF7D');
        $this->addSql('DROP TABLE feedback_user_subscriptions');
        $this->addSql('ALTER TABLE telegram_payments DROP successful_payment');
    }
}
