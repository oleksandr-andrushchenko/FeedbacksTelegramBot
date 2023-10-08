<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231008025002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_bots CHANGE tg_group _group SMALLINT UNSIGNED NOT NULL, CHANGE `primary` _primary TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE telegram_channels CHANGE tg_group _group SMALLINT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E99F4D1959');
        $this->addSql('DROP INDEX IDX_1483A5E99F4D1959 ON users');
        $this->addSql('ALTER TABLE users CHANGE address_locality_id address_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9F5B7AF75 FOREIGN KEY (address_id) REFERENCES address_localities (id)');
        $this->addSql('CREATE INDEX IDX_1483A5E9F5B7AF75 ON users (address_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE telegram_bots CHANGE _group tg_group SMALLINT UNSIGNED NOT NULL, CHANGE _primary `primary` TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE telegram_channels CHANGE _group tg_group SMALLINT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9F5B7AF75');
        $this->addSql('DROP INDEX IDX_1483A5E9F5B7AF75 ON users');
        $this->addSql('ALTER TABLE users CHANGE address_id address_locality_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E99F4D1959 FOREIGN KEY (address_locality_id) REFERENCES address_localities (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_1483A5E99F4D1959 ON users (address_locality_id)');
    }
}
