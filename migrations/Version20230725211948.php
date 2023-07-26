<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230725211948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_searches ADD is_premium TINYINT(1) NOT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE feedback_user_subscriptions CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE feedbacks ADD is_premium TINYINT(1) NOT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE messenger_users CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE telegram_conversations CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE telegram_payments CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE telegram_updates CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE users CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedbacks DROP is_premium, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_searches DROP is_premium, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_users CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE telegram_conversations CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE telegram_payments CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_user_subscriptions CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE telegram_updates CHANGE created_at created_at DATETIME DEFAULT NULL');
    }
}
