<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230725203816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_searches ADD user_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_searches ADD CONSTRAINT FK_2F56936BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_2F56936BA76ED395 ON feedback_searches (user_id)');
        $this->addSql('ALTER TABLE feedbacks ADD user_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE feedbacks ADD CONSTRAINT FK_7E6C3F89A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_7E6C3F89A76ED395 ON feedbacks (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedbacks DROP FOREIGN KEY FK_7E6C3F89A76ED395');
        $this->addSql('DROP INDEX IDX_7E6C3F89A76ED395 ON feedbacks');
        $this->addSql('ALTER TABLE feedbacks DROP user_id');
        $this->addSql('ALTER TABLE feedback_searches DROP FOREIGN KEY FK_2F56936BA76ED395');
        $this->addSql('DROP INDEX IDX_2F56936BA76ED395 ON feedback_searches');
        $this->addSql('ALTER TABLE feedback_searches DROP user_id');
    }
}
