<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drops the legacy DC2Type comments on user.roles / user.created_at.
 *
 * DBAL 4 no longer emits these comments and maps 'json' to a native JSON
 * column, so the pre-DBAL-4 definitions show up as permanent schema drift.
 * On MariaDB, JSON is LONGTEXT plus a check constraint, so the stored data
 * is unchanged by this migration.
 */
final class Version20260916101844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop legacy DC2Type comments on user.roles and user.created_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` CHANGE roles roles JSON NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
