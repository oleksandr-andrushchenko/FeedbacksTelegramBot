<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230811041247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_searches CHANGE is_premium has_active_subscription TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE feedbacks CHANGE is_premium has_active_subscription TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_searches CHANGE has_active_subscription is_premium TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE feedbacks CHANGE has_active_subscription is_premium TINYINT(1) NOT NULL');
    }
}
