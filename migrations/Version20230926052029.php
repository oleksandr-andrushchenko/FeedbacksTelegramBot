<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230926052029 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE address_localities (id INT UNSIGNED AUTO_INCREMENT NOT NULL, country_code VARCHAR(2) NOT NULL, region_1_short VARCHAR(255) NOT NULL, region_1_long VARCHAR(255) NOT NULL, region_2_short VARCHAR(255) NOT NULL, region_2_long VARCHAR(255) NOT NULL, locality_short VARCHAR(255) NOT NULL, locality_long VARCHAR(255) NOT NULL, count INT UNSIGNED DEFAULT 1 NOT NULL, INDEX IDX_397A08774EA59BA6 (locality_short), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE users ADD address_locality_id INT UNSIGNED DEFAULT NULL, DROP region_1, DROP region_2, DROP locality, CHANGE location_latitude location_latitude VARCHAR(12) DEFAULT NULL, CHANGE location_longitude location_longitude VARCHAR(12) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E99F4D1959 FOREIGN KEY (address_locality_id) REFERENCES address_localities (id)');
        $this->addSql('CREATE INDEX IDX_1483A5E99F4D1959 ON users (address_locality_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E99F4D1959');
        $this->addSql('DROP TABLE address_localities');
        $this->addSql('DROP INDEX IDX_1483A5E99F4D1959 ON users');
        $this->addSql('ALTER TABLE users ADD region_1 VARCHAR(255) DEFAULT NULL, ADD region_2 VARCHAR(255) DEFAULT NULL, ADD locality VARCHAR(255) DEFAULT NULL, DROP address_locality_id, CHANGE location_latitude location_latitude VARCHAR(11) DEFAULT NULL, CHANGE location_longitude location_longitude VARCHAR(11) DEFAULT NULL');
    }
}
