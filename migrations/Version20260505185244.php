<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add AniList-sourced fields to anime, plus character/staff entities.
 *
 * Adds:
 *   - 23 columns on anime (anilist_id, mal_id, titles, format, status, dates, scores, ...)
 *   - character + staff tables
 *   - anime_character + anime_staff join tables
 *   - DROP messenger_messages (orphan table from removed symfony/messenger)
 *
 * Preserves existing perf indexes on anime and genre — they were intentionally
 * added outside Doctrine's entity declarations and should stay.
 */
final class Version20260505185244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add AniList fields to anime, create character/staff entities and joins';
    }

    public function up(Schema $schema): void
    {
        // New tables
        $this->addSql('CREATE TABLE `character` (
            id INT AUTO_INCREMENT NOT NULL,
            anilist_id INT DEFAULT NULL,
            name VARCHAR(256) NOT NULL,
            image_url VARCHAR(1024) DEFAULT NULL,
            old_image_url VARCHAR(1024) DEFAULT NULL,
            gender VARCHAR(16) DEFAULT NULL,
            UNIQUE INDEX UNIQ_937AB03426682F6B (anilist_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        $this->addSql('CREATE TABLE staff (
            id INT AUTO_INCREMENT NOT NULL,
            anilist_id INT DEFAULT NULL,
            name VARCHAR(256) NOT NULL,
            image_url VARCHAR(1024) DEFAULT NULL,
            old_image_url VARCHAR(1024) DEFAULT NULL,
            language VARCHAR(32) DEFAULT NULL,
            UNIQUE INDEX UNIQ_426EF39226682F6B (anilist_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        $this->addSql('CREATE TABLE anime_character (
            id INT AUTO_INCREMENT NOT NULL,
            anime_id INT NOT NULL,
            character_id INT NOT NULL,
            voice_actor_id INT DEFAULT NULL,
            role VARCHAR(16) DEFAULT NULL,
            INDEX IDX_4824B930794BBE89 (anime_id),
            INDEX IDX_4824B9301136BE75 (character_id),
            INDEX IDX_4824B9307985444F (voice_actor_id),
            UNIQUE INDEX uniq_anime_character (anime_id, character_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        $this->addSql('CREATE TABLE anime_staff (
            id INT AUTO_INCREMENT NOT NULL,
            anime_id INT NOT NULL,
            staff_id INT NOT NULL,
            role VARCHAR(128) NOT NULL,
            INDEX IDX_2EC793AD794BBE89 (anime_id),
            INDEX IDX_2EC793ADD4D57CD (staff_id),
            UNIQUE INDEX uniq_anime_staff_role (anime_id, staff_id, role),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        $this->addSql('ALTER TABLE anime_character ADD CONSTRAINT FK_4824B930794BBE89 FOREIGN KEY (anime_id) REFERENCES anime (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE anime_character ADD CONSTRAINT FK_4824B9301136BE75 FOREIGN KEY (character_id) REFERENCES `character` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE anime_character ADD CONSTRAINT FK_4824B9307985444F FOREIGN KEY (voice_actor_id) REFERENCES staff (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE anime_staff ADD CONSTRAINT FK_2EC793AD794BBE89 FOREIGN KEY (anime_id) REFERENCES anime (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE anime_staff ADD CONSTRAINT FK_2EC793ADD4D57CD FOREIGN KEY (staff_id) REFERENCES staff (id) ON DELETE CASCADE');

        // Anime: new AniList-sourced columns
        $this->addSql('ALTER TABLE anime
            ADD anilist_id INT DEFAULT NULL,
            ADD mal_id INT DEFAULT NULL,
            ADD title_romaji VARCHAR(1024) DEFAULT NULL,
            ADD title_native VARCHAR(1024) DEFAULT NULL,
            ADD banner_url VARCHAR(1024) DEFAULT NULL,
            ADD old_banner_url VARCHAR(1024) DEFAULT NULL,
            ADD cover_color VARCHAR(16) DEFAULT NULL,
            ADD duration SMALLINT DEFAULT NULL,
            ADD format VARCHAR(32) DEFAULT NULL,
            ADD status VARCHAR(32) DEFAULT NULL,
            ADD source VARCHAR(32) DEFAULT NULL,
            ADD season VARCHAR(16) DEFAULT NULL,
            ADD season_year SMALLINT DEFAULT NULL,
            ADD start_date DATE DEFAULT NULL,
            ADD end_date DATE DEFAULT NULL,
            ADD country_of_origin VARCHAR(4) DEFAULT NULL,
            ADD is_adult TINYINT(1) DEFAULT 0 NOT NULL,
            ADD average_score SMALLINT DEFAULT NULL,
            ADD mean_score SMALLINT DEFAULT NULL,
            ADD popularity INT DEFAULT NULL,
            ADD favourites INT DEFAULT NULL,
            ADD trailer_youtube_id VARCHAR(32) DEFAULT NULL,
            ADD updated_at DATETIME DEFAULT NULL,
            ADD INDEX idx_anime_mal_id (mal_id),
            ADD INDEX idx_anime_popularity (popularity),
            ADD INDEX idx_anime_average_score (average_score)');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_1304594226682F6B ON anime (anilist_id)');

        // Drop messenger_messages table (orphan from removed symfony/messenger)
        $this->addSql('DROP TABLE IF EXISTS messenger_messages');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE messenger_messages (
            id BIGINT AUTO_INCREMENT NOT NULL,
            body LONGTEXT NOT NULL,
            headers LONGTEXT NOT NULL,
            queue_name VARCHAR(190) NOT NULL,
            created_at DATETIME NOT NULL,
            available_at DATETIME NOT NULL,
            delivered_at DATETIME DEFAULT NULL,
            INDEX IDX_75EA56E016BA31DB (delivered_at),
            INDEX IDX_75EA56E0E3BD61CE (available_at),
            INDEX IDX_75EA56E0FB7336F0 (queue_name),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE anime_character DROP FOREIGN KEY FK_4824B930794BBE89');
        $this->addSql('ALTER TABLE anime_character DROP FOREIGN KEY FK_4824B9301136BE75');
        $this->addSql('ALTER TABLE anime_character DROP FOREIGN KEY FK_4824B9307985444F');
        $this->addSql('ALTER TABLE anime_staff DROP FOREIGN KEY FK_2EC793AD794BBE89');
        $this->addSql('ALTER TABLE anime_staff DROP FOREIGN KEY FK_2EC793ADD4D57CD');
        $this->addSql('DROP TABLE anime_character');
        $this->addSql('DROP TABLE anime_staff');
        $this->addSql('DROP TABLE `character`');
        $this->addSql('DROP TABLE staff');

        $this->addSql('DROP INDEX UNIQ_1304594226682F6B ON anime');
        $this->addSql('ALTER TABLE anime
            DROP INDEX idx_anime_mal_id,
            DROP INDEX idx_anime_popularity,
            DROP INDEX idx_anime_average_score,
            DROP anilist_id, DROP mal_id, DROP title_romaji, DROP title_native,
            DROP banner_url, DROP old_banner_url, DROP cover_color,
            DROP duration, DROP format, DROP status, DROP source,
            DROP season, DROP season_year, DROP start_date, DROP end_date,
            DROP country_of_origin, DROP is_adult,
            DROP average_score, DROP mean_score, DROP popularity, DROP favourites,
            DROP trailer_youtube_id, DROP updated_at');
    }
}
