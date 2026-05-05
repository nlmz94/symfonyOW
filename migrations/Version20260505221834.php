<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Bump anime.trailer_youtube_id from VARCHAR(32) to VARCHAR(64).
 * AniList sometimes returns trailer IDs longer than the typical 11-char YouTube id.
 */
final class Version20260505221834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Widen anime.trailer_youtube_id to VARCHAR(64)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE anime CHANGE trailer_youtube_id trailer_youtube_id VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE anime CHANGE trailer_youtube_id trailer_youtube_id VARCHAR(32) DEFAULT NULL');
    }
}
