<?php

namespace App\Command;

use App\Config\DifficultyConfig;
use App\Entity\Word;
use App\Repository\WordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-words',
    description: 'Import words into word_pool (7 difficulty levels, 100 words each)',
)]
class ImportWordsCommand extends Command
{
    private const WORDS_PER_LEVEL = 100;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private WordRepository $wordRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $wordsByLevel = $this->loadWords($io);

        $this->clearWordPool();

        $count = 0;
        foreach ($wordsByLevel as $difficulty => $wordList) {
            foreach ($wordList as $text) {
                $text = trim($text);
                if ($text === '') {
                    continue;
                }

                $word = new Word();
                $word->setText($text);
                $word->setDifficulty((int) $difficulty);
                $this->entityManager->persist($word);
                ++$count;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Imported %d words (%d levels × %d words).',
            $count,
            DifficultyConfig::MAX_LEVEL,
            self::WORDS_PER_LEVEL
        ));

        return Command::SUCCESS;
    }

    /**
     * @return array<int, string[]>
     */
    private function loadWords(SymfonyStyle $io): array
    {
        $dataDir = __DIR__.'/Data';
        $words = [];

        for ($level = 1; $level <= DifficultyConfig::MAX_LEVEL; ++$level) {
            $path = $dataDir.'/level_'.$level.'.php';

            if (!is_file($path)) {
                throw new \RuntimeException(sprintf('Word file not found: %s', $path));
            }

            /** @var string[] $list */
            $list = require $path;

            if (count($list) !== self::WORDS_PER_LEVEL) {
                $io->warning(sprintf(
                    'Level %d: expected %d words, got %d (%s)',
                    $level,
                    self::WORDS_PER_LEVEL,
                    count($list),
                    basename($path)
                ));
            }

            $words[$level] = $list;
        }

        return $words;
    }

    private function clearWordPool(): void
    {
        $conn = $this->entityManager->getConnection();

        // Переимпорт слов: сбрасываем пул и прогресс раундов (старые сессии станут неактуальны).
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $conn->executeStatement('DELETE FROM turn_log');
        $conn->executeStatement('DELETE FROM round_progress');
        $conn->executeStatement('TRUNCATE TABLE word_pool');
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }
}
