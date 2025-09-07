<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250907103234 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anime_genre ADD PRIMARY KEY (id_anime, id_genre)');
        $this->addSql('ALTER TABLE anime_genre RENAME INDEX id_anime_idx TO IDX_EFF953C7FD811872');
        $this->addSql('ALTER TABLE anime_genre RENAME INDEX id_genre_idx TO IDX_EFF953C76DD572C8');
        $this->addSql('ALTER TABLE anime_producer MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX `primary` ON anime_producer');
        $this->addSql('ALTER TABLE anime_producer DROP id, CHANGE id_anime id_anime INT NOT NULL, CHANGE id_producer id_producer INT NOT NULL');
        $this->addSql('ALTER TABLE anime_producer ADD PRIMARY KEY (id_anime, id_producer)');
        $this->addSql('ALTER TABLE anime_producer RENAME INDEX id_idx TO IDX_72A49BF6FD811872');
        $this->addSql('ALTER TABLE anime_producer RENAME INDEX id_idx1 TO IDX_72A49BF6C7BFA549');
        $this->addSql('ALTER TABLE anime_studio MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX `primary` ON anime_studio');
        $this->addSql('ALTER TABLE anime_studio DROP id, CHANGE id_anime id_anime INT NOT NULL, CHANGE id_studio id_studio INT NOT NULL');
        $this->addSql('ALTER TABLE anime_studio ADD PRIMARY KEY (id_anime, id_studio)');
        $this->addSql('ALTER TABLE anime_studio RENAME INDEX has_anime_key_idx TO IDX_FC2183EBFD811872');
        $this->addSql('ALTER TABLE anime_studio RENAME INDEX has_studio_key_idx TO IDX_FC2183EB6C1CB25B');
        $this->addSql('DROP INDEX name_UNIQUE ON genre');
        $this->addSql('DROP INDEX pegi_UNIQUE ON pegi');
        $this->addSql('DROP INDEX name_UNIQUE ON producer');
        $this->addSql('DROP INDEX name_UNIQUE ON studio');
        $this->addSql('ALTER TABLE user ADD roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX name_UNIQUE ON genre (name)');
        $this->addSql('ALTER TABLE anime_studio ADD id INT AUTO_INCREMENT NOT NULL, CHANGE id_anime id_anime INT DEFAULT NULL, CHANGE id_studio id_studio INT DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE anime_studio RENAME INDEX idx_fc2183eb6c1cb25b TO has_studio_key_idx');
        $this->addSql('ALTER TABLE anime_studio RENAME INDEX idx_fc2183ebfd811872 TO has_anime_key_idx');
        $this->addSql('CREATE UNIQUE INDEX name_UNIQUE ON studio (name)');
        $this->addSql('DROP INDEX `primary` ON anime_genre');
        $this->addSql('ALTER TABLE anime_genre RENAME INDEX idx_eff953c7fd811872 TO id_anime_idx');
        $this->addSql('ALTER TABLE anime_genre RENAME INDEX idx_eff953c76dd572c8 TO id_genre_idx');
        $this->addSql('ALTER TABLE anime_producer ADD id INT AUTO_INCREMENT NOT NULL, CHANGE id_anime id_anime INT DEFAULT NULL, CHANGE id_producer id_producer INT DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE anime_producer RENAME INDEX idx_72a49bf6fd811872 TO id_idx');
        $this->addSql('ALTER TABLE anime_producer RENAME INDEX idx_72a49bf6c7bfa549 TO id_idx1');
        $this->addSql('CREATE UNIQUE INDEX pegi_UNIQUE ON pegi (pegi)');
        $this->addSql('DROP INDEX UNIQ_8D93D649E7927C74 ON user');
        $this->addSql('ALTER TABLE user DROP roles');
        $this->addSql('CREATE UNIQUE INDEX name_UNIQUE ON producer (name)');
    }
}
