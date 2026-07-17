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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Append words without wiping the pool.
 *
 * Place files at: src/Command/Data/extra/{category}/level_{1..6}.php
 * Each file returns a list of strings (same format as main Data/).
 *
 * Usage:
 *   php bin/console app:import-extra-words
 *   php bin/console app:import-extra-words --dir=/path/to/extra
 */
#[AsCommand(
    name: 'app:import-extra-words',
    description: 'Append extra words from Data/extra (no wipe of word_pool)',
)]
class ImportExtraWordsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WordRepository $wordRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dir',
            null,
            InputOption::VALUE_REQUIRED,
            'Directory with {category}/level_N.php files',
            __DIR__.'/Data/extra'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dir = (string) $input->getOption('dir');

        if (!is_dir($dir)) {
            $io->warning(sprintf(
                'Directory not found: %s — create it and add {category}/level_N.php files.',
                $dir
            ));
            $io->note('Example: Data/extra/food/level_1.php returning ["борщ", "окрошка"]');

            return Command::SUCCESS;
        }

        $existing = $this->loadExistingTexts();
        $entries = $this->loadExtraWords($dir, $io, $existing);

        if ($entries === []) {
            $io->success('Nothing new to import.');

            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($entries as [$category, $difficulty, $text]) {
            $word = new Word();
            $word->setText($text);
            $word->setDifficulty($difficulty);
            $word->setCategory($category);
            $this->entityManager->persist($word);
            ++$count;

            if ($count % 200 === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('Appended %d new words from %s', $count, $dir));

        return Command::SUCCESS;
    }

    /** @return array<string, true> */
    private function loadExistingTexts(): array
    {
        $rows = $this->wordRepository->createQueryBuilder('w')
            ->select('w.text AS t')
            ->getQuery()
            ->getScalarResult();

        $map = [];
        foreach ($rows as $row) {
            $map[mb_strtolower((string) $row['t'])] = true;
        }

        return $map;
    }

    /**
     * @param array<string, true> $existing
     *
     * @return list<array{0: string, 1: int, 2: string}>
     */
    private function loadExtraWords(string $dir, SymfonyStyle $io, array &$existing): array
    {
        $entries = [];
        $skippedDup = 0;
        $skippedEmpty = 0;
        $filesFound = 0;

        foreach (CategoryConfig::allSlugs() as $category) {
            for ($level = 1; $level <= DifficultyConfig::MAX_LEVEL; ++$level) {
                $path = $dir.'/'.$category.'/level_'.$level.'.php';
                if (!is_file($path)) {
                    continue;
                }
                ++$filesFound;

                /** @var mixed $list */
                $list = require $path;
                if (!is_array($list)) {
                    throw new \RuntimeException(sprintf('Invalid word file: %s', $path));
                }

                $added = 0;
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
                    if (isset($existing[$key])) {
                        ++$skippedDup;
                        continue;
                    }

                    $existing[$key] = true;
                    $entries[] = [$category, $level, $text];
                    ++$added;
                }

                $io->writeln(sprintf('  %s/%d: +%d', $category, $level, $added));
            }
        }

        if ($filesFound === 0) {
            $io->note('No level_*.php files under '.$dir);
        }
        if ($skippedDup > 0) {
            $io->note(sprintf('Skipped %d duplicates (already in pool)', $skippedDup));
        }
        if ($skippedEmpty > 0) {
            $io->note(sprintf('Skipped %d empty entries', $skippedEmpty));
        }

        return $entries;
    }
}
