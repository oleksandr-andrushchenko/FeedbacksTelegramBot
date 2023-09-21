<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230921044039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('RENAME TABLE user_feedback_messages TO user_contact_messages');
        $this->addSql('ALTER TABLE user_contact_messages RENAME INDEX idx_6c530ecf9e4cef7d TO IDX_3C32C0DC9E4CEF7D');
        $this->addSql('ALTER TABLE user_contact_messages RENAME INDEX idx_6c530ecfa76ed395 TO IDX_3C32C0DCA76ED395');
        $this->addSql('ALTER TABLE user_contact_messages RENAME INDEX idx_6c530ecfa0e2f38 TO IDX_3C32C0DCA0E2F38');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('RENAME TABLE user_contact_messages TO user_feedback_messages');
        $this->addSql('ALTER TABLE user_contact_messages RENAME INDEX idx_3c32c0dc9e4cef7d TO IDX_6C530ECF9E4CEF7D');
        $this->addSql('ALTER TABLE user_contact_messages RENAME INDEX idx_3c32c0dca0e2f38 TO IDX_6C530ECFA0E2F38');
        $this->addSql('ALTER TABLE user_contact_messages RENAME INDEX idx_3c32c0dca76ed395 TO IDX_6C530ECFA76ED395');
    }
}
