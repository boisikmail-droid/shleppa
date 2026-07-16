<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add hat_id to team for custom headwear per team';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE team ADD hat_id VARCHAR(32) NOT NULL DEFAULT 'tophat'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE team DROP hat_id');
    }
}
