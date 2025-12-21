<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add performance indexes to anime and genre tables
 */
final class Version20251221203500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add performance indexes to anime and genre tables';
    }

    public function up(Schema $schema): void
    {
        // Add indexes to anime table
        $this->addSql('CREATE INDEX idx_anime_title ON anime (title(255))');
        $this->addSql('CREATE INDEX idx_anime_episodes ON anime (episodes)');
        $this->addSql('CREATE INDEX idx_anime_aired ON anime (aired)');

        // Add index to genre table
        $this->addSql('CREATE INDEX idx_genre_name ON genre (name)');
    }

    public function down(Schema $schema): void
    {
        // Remove indexes from anime table
        $this->addSql('DROP INDEX idx_anime_title ON anime');
        $this->addSql('DROP INDEX idx_anime_episodes ON anime');
        $this->addSql('DROP INDEX idx_anime_aired ON anime');

        // Remove index from genre table
        $this->addSql('DROP INDEX idx_genre_name ON genre');
    }
}