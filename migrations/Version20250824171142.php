<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250824171142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename anime.img_url -> anime.old_img_url; add new anime.img_url for local paths';
    }

    public function up(Schema $schema): void
    {
        // MariaDB/MySQL
        $this->addSql("ALTER TABLE anime CHANGE COLUMN img_url old_img_url VARCHAR(1024) NULL");
        $this->addSql("ALTER TABLE anime ADD COLUMN img_url VARCHAR(1024) NULL AFTER old_img_url");
    }

    public function down(Schema $schema): void
    {
        // Revert: drop new img_url and rename old_img_url back to img_url
        $this->addSql("ALTER TABLE anime DROP COLUMN img_url");
        $this->addSql("ALTER TABLE anime CHANGE COLUMN old_img_url img_url VARCHAR(1024) NULL");
    }
}
