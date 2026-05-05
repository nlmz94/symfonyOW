<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Bump character.gender from VARCHAR(16) to VARCHAR(64).
 * AniList allows free-text gender values that occasionally exceed 16 chars.
 */
final class Version20260505192126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Widen character.gender to VARCHAR(64)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `character` CHANGE gender gender VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `character` CHANGE gender gender VARCHAR(16) DEFAULT NULL');
    }
}
