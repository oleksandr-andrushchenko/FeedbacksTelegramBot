<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231011232332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9F5B7AF75');
        $this->addSql('CREATE TABLE level_1_regions (id INT UNSIGNED AUTO_INCREMENT NOT NULL, country_code VARCHAR(2) NOT NULL, name VARCHAR(64) NOT NULL, timezone VARCHAR(32) DEFAULT NULL, INDEX IDX_EA10FCA9F026BB7C5E237E06 (country_code, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP TABLE addresses');
        $this->addSql('ALTER TABLE telegram_channels ADD level_1_region_id INT DEFAULT NULL, DROP administrative_area_level_1, DROP administrative_area_level_2, DROP administrative_area_level_3');
        $this->addSql('DROP INDEX IDX_1483A5E9F5B7AF75 ON users');
        $this->addSql('ALTER TABLE users ADD level_1_region_id INT DEFAULT NULL, DROP address_id, DROP rating');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE addresses (id INT UNSIGNED AUTO_INCREMENT NOT NULL, country_code VARCHAR(2) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, count INT UNSIGNED DEFAULT 0 NOT NULL, timezone VARCHAR(32) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, administrative_area_level_1 VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, administrative_area_level_2 VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, administrative_area_level_3 VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6FCA75167E7ABCA5 (administrative_area_level_1), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE level_1_regions');
        $this->addSql('ALTER TABLE telegram_channels ADD administrative_area_level_1 VARCHAR(255) DEFAULT NULL, ADD administrative_area_level_2 VARCHAR(255) DEFAULT NULL, ADD administrative_area_level_3 VARCHAR(255) DEFAULT NULL, DROP level_1_region_id');
        $this->addSql('ALTER TABLE users ADD address_id INT UNSIGNED DEFAULT NULL, ADD rating SMALLINT DEFAULT NULL, DROP level_1_region_id');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9F5B7AF75 FOREIGN KEY (address_id) REFERENCES addresses (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_1483A5E9F5B7AF75 ON users (address_id)');
    }
}
