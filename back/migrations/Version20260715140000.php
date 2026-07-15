<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Widen word_pool.text to VARCHAR(255)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE word_pool MODIFY text VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE word_pool MODIFY text VARCHAR(100) NOT NULL');
    }
}
