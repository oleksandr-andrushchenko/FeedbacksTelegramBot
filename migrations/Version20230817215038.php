<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230817215038 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feedback_search_searches (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED DEFAULT NULL, messenger_user_id INT UNSIGNED DEFAULT NULL, search_term_messenger_user_id INT UNSIGNED DEFAULT NULL, search_term_text VARCHAR(255) NOT NULL, search_term_normalized_text VARCHAR(255) NOT NULL, search_term_type SMALLINT UNSIGNED NOT NULL, search_term_messenger SMALLINT UNSIGNED DEFAULT NULL, search_term_messenger_username VARCHAR(255) DEFAULT NULL, country_code VARCHAR(2) DEFAULT NULL, locale_code VARCHAR(2) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_5CD71DC0A76ED395 (user_id), INDEX IDX_5CD71DC09E4CEF7D (messenger_user_id), INDEX IDX_5CD71DC02333E4FC (search_term_messenger_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE feedback_search_searches ADD CONSTRAINT FK_5CD71DC0A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE feedback_search_searches ADD CONSTRAINT FK_5CD71DC09E4CEF7D FOREIGN KEY (messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedback_search_searches ADD CONSTRAINT FK_5CD71DC02333E4FC FOREIGN KEY (search_term_messenger_user_id) REFERENCES messenger_users (id)');
        $this->addSql('ALTER TABLE feedback_searches DROP updated_at');
        $this->addSql('ALTER TABLE feedbacks DROP updated_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_search_searches DROP FOREIGN KEY FK_5CD71DC0A76ED395');
        $this->addSql('ALTER TABLE feedback_search_searches DROP FOREIGN KEY FK_5CD71DC09E4CEF7D');
        $this->addSql('ALTER TABLE feedback_search_searches DROP FOREIGN KEY FK_5CD71DC02333E4FC');
        $this->addSql('DROP TABLE feedback_search_searches');
        $this->addSql('ALTER TABLE feedback_searches ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE feedbacks ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
