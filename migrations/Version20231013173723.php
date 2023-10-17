<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231013173723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_search_searches DROP FOREIGN KEY FK_5CD71DC02333E4FC');
        $this->addSql('DROP INDEX IDX_5CD71DC02333E4FC ON feedback_search_searches');
        $this->addSql('ALTER TABLE feedback_search_searches DROP search_term_text, DROP search_term_normalized_text, DROP search_term_type, DROP search_term_messenger, DROP search_term_messenger_username, CHANGE search_term_messenger_user_id term_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_search_searches ADD CONSTRAINT FK_5CD71DC0E2C35FC FOREIGN KEY (term_id) REFERENCES feedback_search_terms (id)');
        $this->addSql('CREATE INDEX IDX_5CD71DC0E2C35FC ON feedback_search_searches (term_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_search_searches DROP FOREIGN KEY FK_5CD71DC0E2C35FC');
        $this->addSql('DROP INDEX IDX_5CD71DC0E2C35FC ON feedback_search_searches');
        $this->addSql('ALTER TABLE feedback_search_searches ADD search_term_text VARCHAR(255) NOT NULL, ADD search_term_normalized_text VARCHAR(255) NOT NULL, ADD search_term_type SMALLINT UNSIGNED NOT NULL, ADD search_term_messenger SMALLINT UNSIGNED DEFAULT NULL, ADD search_term_messenger_username VARCHAR(255) DEFAULT NULL, CHANGE term_id search_term_messenger_user_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback_search_searches ADD CONSTRAINT FK_5CD71DC02333E4FC FOREIGN KEY (search_term_messenger_user_id) REFERENCES messenger_users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_5CD71DC02333E4FC ON feedback_search_searches (search_term_messenger_user_id)');
    }
}
