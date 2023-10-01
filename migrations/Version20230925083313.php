<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230925083313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD region_1 VARCHAR(255) DEFAULT NULL, ADD region_2 VARCHAR(255) DEFAULT NULL, ADD locality VARCHAR(255) DEFAULT NULL, DROP region_code, DROP city_code');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD region_code VARCHAR(255) DEFAULT NULL, ADD city_code VARCHAR(255) DEFAULT NULL, DROP region_1, DROP region_2, DROP locality');
    }
}
