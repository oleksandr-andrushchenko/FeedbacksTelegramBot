<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230813233538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE telegram_payment_methods (id SMALLINT UNSIGNED AUTO_INCREMENT NOT NULL, bot_id SMALLINT UNSIGNED DEFAULT NULL, name SMALLINT UNSIGNED NOT NULL, token VARCHAR(64) NOT NULL, currencies LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_CDACB4B492C1C487 (bot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE telegram_payment_methods ADD CONSTRAINT FK_CDACB4B492C1C487 FOREIGN KEY (bot_id) REFERENCES telegram_bots (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_payment_methods DROP FOREIGN KEY FK_CDACB4B492C1C487');
        $this->addSql('DROP TABLE telegram_payment_methods');
    }
}
