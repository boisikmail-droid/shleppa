<?php

namespace App\Command;

use App\Config\CategoryConfig;
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
    description: 'Import curated words into word_pool (categories × difficulty)',
)]
class ImportWordsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WordRepository $wordRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entries = $this->loadWords($io);

        $this->clearWordPool();

        $count = 0;
        foreach ($entries as [$category, $difficulty, $text]) {
            $word = new Word();
            $word->setText($text);
            $word->setDifficulty($difficulty);
            $word->setCategory($category);
            $this->entityManager->persist($word);
            ++$count;

            if ($count % 500 === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Imported %d unique words across %d categories.',
            $count,
            count(CategoryConfig::CATEGORIES)
        ));

        return Command::SUCCESS;
    }

    /**
     * @return list<array{0: string, 1: int, 2: string}>
     */
    private function loadWords(SymfonyStyle $io): array
    {
        $dataDir = __DIR__.'/Data';
        $entries = [];
        /** @var array<string, true> */
        $seenGlobal = [];
        $skippedDup = 0;
        $skippedEmpty = 0;

        foreach (CategoryConfig::allSlugs() as $category) {
            /** @var array<string, true> */
            $seenInCategory = [];
            $maxLevel = CategoryConfig::maxLevelFor($category);

            for ($level = 1; $level <= $maxLevel; ++$level) {
                $path = $dataDir.'/'.$category.'/level_'.$level.'.php';

                if (!is_file($path)) {
                    $io->warning(sprintf('Word file missing (skipped): %s', $path));
                    continue;
                }

                /** @var mixed $list */
                $list = require $path;
                if (!is_array($list)) {
                    throw new \RuntimeException(sprintf('Invalid word file: %s', $path));
                }

                $levelCount = 0;
                foreach ($list as $text) {
                    $text = trim(preg_replace('/\s+/u', ' ', (string) $text) ?? '');
                    if ($text === '') {
                        ++$skippedEmpty;
                        continue;
                    }
                    if (mb_strlen($text) > 255) {
                        $text = mb_substr($text, 0, 255);
                    }

                    $key = mb_strtolower($text);
                    if (isset($seenInCategory[$key]) || isset($seenGlobal[$key])) {
                        ++$skippedDup;
                        continue;
                    }

                    $seenInCategory[$key] = true;
                    $seenGlobal[$key] = true;
                    $entries[] = [$category, $level, $text];
                    ++$levelCount;
                }

                if ($levelCount < 20) {
                    $io->warning(sprintf('%s/%d: only %d words after dedupe', $category, $level, $levelCount));
                }
            }
        }

        if ($skippedDup > 0) {
            $io->note(sprintf('Skipped %d duplicates', $skippedDup));
        }
        if ($skippedEmpty > 0) {
            $io->note(sprintf('Skipped %d empty entries', $skippedEmpty));
        }

        return $entries;
    }

    private function clearWordPool(): void
    {
        $conn = $this->entityManager->getConnection();

        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $conn->executeStatement('DELETE FROM turn_log');
        $conn->executeStatement('DELETE FROM round_progress');
        $conn->executeStatement('TRUNCATE TABLE word_pool');
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }
}
