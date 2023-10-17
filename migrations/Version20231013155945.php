<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231013155945 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feedbacks_feedback_search_terms (feedback_id INT UNSIGNED NOT NULL, feedback_search_term_id INT UNSIGNED NOT NULL, INDEX IDX_1725DC6AD249A887 (feedback_id), INDEX IDX_1725DC6AC2ED3DD8 (feedback_search_term_id), PRIMARY KEY(feedback_id, feedback_search_term_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE feedbacks_feedback_search_terms ADD CONSTRAINT FK_1725DC6AD249A887 FOREIGN KEY (feedback_id) REFERENCES feedbacks (id)');
        $this->addSql('ALTER TABLE feedbacks_feedback_search_terms ADD CONSTRAINT FK_1725DC6AC2ED3DD8 FOREIGN KEY (feedback_search_term_id) REFERENCES feedback_search_terms (id)');
        $this->addSql('ALTER TABLE feedbacks_search_terms DROP FOREIGN KEY FK_15C0A96FC2ED3DD8');
        $this->addSql('ALTER TABLE feedbacks_search_terms DROP FOREIGN KEY FK_15C0A96FD249A887');
        $this->addSql('DROP TABLE feedbacks_search_terms');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feedbacks_search_terms (feedback_id INT UNSIGNED NOT NULL, feedback_search_term_id INT UNSIGNED NOT NULL, INDEX IDX_15C0A96FC2ED3DD8 (feedback_search_term_id), INDEX IDX_15C0A96FD249A887 (feedback_id), PRIMARY KEY(feedback_id, feedback_search_term_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE feedbacks_search_terms ADD CONSTRAINT FK_15C0A96FC2ED3DD8 FOREIGN KEY (feedback_search_term_id) REFERENCES feedback_search_terms (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedbacks_search_terms ADD CONSTRAINT FK_15C0A96FD249A887 FOREIGN KEY (feedback_id) REFERENCES feedbacks (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE feedbacks_feedback_search_terms DROP FOREIGN KEY FK_1725DC6AD249A887');
        $this->addSql('ALTER TABLE feedbacks_feedback_search_terms DROP FOREIGN KEY FK_1725DC6AC2ED3DD8');
        $this->addSql('DROP TABLE feedbacks_feedback_search_terms');
    }
}
