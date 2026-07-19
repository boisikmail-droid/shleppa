<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260718140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add avatar_id to player for character portraits';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE player ADD avatar_id VARCHAR(32) NOT NULL DEFAULT 'm01'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP avatar_id');
    }
}
