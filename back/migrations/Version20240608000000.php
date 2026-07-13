<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240608000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema for Hat game';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE word_pool (id INT AUTO_INCREMENT NOT NULL, text VARCHAR(100) NOT NULL, difficulty INT NOT NULL, INDEX idx_word_difficulty (difficulty), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE game_session (id INT AUTO_INCREMENT NOT NULL, current_team_id INT DEFAULT NULL, current_player_id INT DEFAULT NULL, round_start_team_id INT DEFAULT NULL, round_start_player_id INT DEFAULT NULL, status VARCHAR(50) NOT NULL, total_words_count INT NOT NULL, turn_time_limit INT NOT NULL, words_data JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_session_status (status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE team (id INT AUTO_INCREMENT NOT NULL, session_id INT NOT NULL, name VARCHAR(100) NOT NULL, score INT NOT NULL, INDEX IDX_C4E0A61F613FECDF (session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE player (id INT AUTO_INCREMENT NOT NULL, team_id INT NOT NULL, name VARCHAR(100) NOT NULL, order_index INT NOT NULL, INDEX IDX_98197A65296CD8AE (team_id), UNIQUE INDEX uniq_team_order (team_id, order_index), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE round_progress (id INT AUTO_INCREMENT NOT NULL, session_id INT NOT NULL, word_id INT NOT NULL, round INT NOT NULL, is_guessed_in_this_round TINYINT(1) NOT NULL, INDEX idx_session_round_guessed (session_id, round, is_guessed_in_this_round), UNIQUE INDEX uniq_session_word_round (session_id, word_id, round), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE team_difficulty_state (id INT AUTO_INCREMENT NOT NULL, session_id INT NOT NULL, team_id INT NOT NULL, round INT NOT NULL, current_difficulty INT NOT NULL, words_guessed_in_cycle INT NOT NULL, UNIQUE INDEX uniq_session_team_round (session_id, team_id, round), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE game_turn (id INT AUTO_INCREMENT NOT NULL, session_id INT NOT NULL, team_id INT NOT NULL, player_id INT NOT NULL, round INT NOT NULL, is_finished TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE turn_log (id INT AUTO_INCREMENT NOT NULL, session_id INT NOT NULL, team_id INT NOT NULL, player_id INT NOT NULL, word_id INT NOT NULL, game_turn_id INT NOT NULL, round INT NOT NULL, status VARCHAR(20) NOT NULL, was_corrected TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_session_player_created (session_id, player_id, created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE game_session ADD CONSTRAINT FK_12345678_CURRENT_TEAM FOREIGN KEY (current_team_id) REFERENCES team (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE game_session ADD CONSTRAINT FK_12345678_CURRENT_PLAYER FOREIGN KEY (current_player_id) REFERENCES player (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE game_session ADD CONSTRAINT FK_12345678_ROUND_START_TEAM FOREIGN KEY (round_start_team_id) REFERENCES team (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE game_session ADD CONSTRAINT FK_12345678_ROUND_START_PLAYER FOREIGN KEY (round_start_player_id) REFERENCES player (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F613FECDF FOREIGN KEY (session_id) REFERENCES game_session (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE round_progress ADD CONSTRAINT FK_RP_SESSION FOREIGN KEY (session_id) REFERENCES game_session (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE round_progress ADD CONSTRAINT FK_RP_WORD FOREIGN KEY (word_id) REFERENCES word_pool (id)');
        $this->addSql('ALTER TABLE team_difficulty_state ADD CONSTRAINT FK_TDS_SESSION FOREIGN KEY (session_id) REFERENCES game_session (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team_difficulty_state ADD CONSTRAINT FK_TDS_TEAM FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_turn ADD CONSTRAINT FK_GT_SESSION FOREIGN KEY (session_id) REFERENCES game_session (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_turn ADD CONSTRAINT FK_GT_TEAM FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE game_turn ADD CONSTRAINT FK_GT_PLAYER FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE turn_log ADD CONSTRAINT FK_TL_SESSION FOREIGN KEY (session_id) REFERENCES game_session (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE turn_log ADD CONSTRAINT FK_TL_TEAM FOREIGN KEY (team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE turn_log ADD CONSTRAINT FK_TL_PLAYER FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE turn_log ADD CONSTRAINT FK_TL_WORD FOREIGN KEY (word_id) REFERENCES word_pool (id)');
        $this->addSql('ALTER TABLE turn_log ADD CONSTRAINT FK_TL_TURN FOREIGN KEY (game_turn_id) REFERENCES game_turn (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE turn_log DROP FOREIGN KEY FK_TL_TURN');
        $this->addSql('ALTER TABLE turn_log DROP FOREIGN KEY FK_TL_WORD');
        $this->addSql('ALTER TABLE turn_log DROP FOREIGN KEY FK_TL_PLAYER');
        $this->addSql('ALTER TABLE turn_log DROP FOREIGN KEY FK_TL_TEAM');
        $this->addSql('ALTER TABLE turn_log DROP FOREIGN KEY FK_TL_SESSION');
        $this->addSql('ALTER TABLE game_turn DROP FOREIGN KEY FK_GT_PLAYER');
        $this->addSql('ALTER TABLE game_turn DROP FOREIGN KEY FK_GT_TEAM');
        $this->addSql('ALTER TABLE game_turn DROP FOREIGN KEY FK_GT_SESSION');
        $this->addSql('ALTER TABLE team_difficulty_state DROP FOREIGN KEY FK_TDS_TEAM');
        $this->addSql('ALTER TABLE team_difficulty_state DROP FOREIGN KEY FK_TDS_SESSION');
        $this->addSql('ALTER TABLE round_progress DROP FOREIGN KEY FK_RP_WORD');
        $this->addSql('ALTER TABLE round_progress DROP FOREIGN KEY FK_RP_SESSION');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65296CD8AE');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61F613FECDF');
        $this->addSql('ALTER TABLE game_session DROP FOREIGN KEY FK_12345678_ROUND_START_PLAYER');
        $this->addSql('ALTER TABLE game_session DROP FOREIGN KEY FK_12345678_ROUND_START_TEAM');
        $this->addSql('ALTER TABLE game_session DROP FOREIGN KEY FK_12345678_CURRENT_PLAYER');
        $this->addSql('ALTER TABLE game_session DROP FOREIGN KEY FK_12345678_CURRENT_TEAM');
        $this->addSql('DROP TABLE turn_log');
        $this->addSql('DROP TABLE game_turn');
        $this->addSql('DROP TABLE team_difficulty_state');
        $this->addSql('DROP TABLE round_progress');
        $this->addSql('DROP TABLE player');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE game_session');
        $this->addSql('DROP TABLE word_pool');
    }
}
