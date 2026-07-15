<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Word categories + session settings; reset word_pool for 6-level dictionary';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('DELETE FROM turn_log');
        $this->addSql('DELETE FROM round_progress');
        $this->addSql('TRUNCATE TABLE word_pool');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');

        $this->addSql('ALTER TABLE word_pool ADD category VARCHAR(32) NOT NULL DEFAULT \'\'');
        $this->addSql('CREATE INDEX idx_word_category ON word_pool (category)');
        $this->addSql('CREATE INDEX idx_word_diff_cat ON word_pool (difficulty, category)');

        $this->addSql('ALTER TABLE game_session ADD settings JSON DEFAULT NULL');
        $this->addSql('UPDATE game_session SET settings = \'{}\' WHERE settings IS NULL');
        $this->addSql('ALTER TABLE game_session MODIFY settings JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_session DROP settings');
        $this->addSql('DROP INDEX idx_word_diff_cat ON word_pool');
        $this->addSql('DROP INDEX idx_word_category ON word_pool');
        $this->addSql('ALTER TABLE word_pool DROP category');
    }
}
