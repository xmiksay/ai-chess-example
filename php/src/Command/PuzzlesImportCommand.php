<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:puzzles:import',
    description: 'Stream-import Lichess puzzles CSV into SQLite.',
)]
final class PuzzlesImportCommand extends Command
{
    public function __construct(private readonly Connection $conn)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('path', InputArgument::REQUIRED, 'Path to lichess_db_puzzle.csv')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Stop after N rows (0 = no limit)', '0')
            ->addOption('batch', null, InputOption::VALUE_REQUIRED, 'Rows per transaction', '1000');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io    = new SymfonyStyle($input, $output);
        $path  = (string) $input->getArgument('path');
        $limit = (int) $input->getOption('limit');
        $batch = max(1, (int) $input->getOption('batch'));

        if (!is_readable($path)) {
            $io->error("Cannot read CSV: {$path}");
            return Command::FAILURE;
        }

        // SQLite tuning for bulk insert
        $this->conn->executeStatement('PRAGMA journal_mode=WAL');
        $this->conn->executeStatement('PRAGMA synchronous=NORMAL');
        $this->conn->executeStatement('PRAGMA temp_store=MEMORY');

        // Preload lookup maps name => id
        $themes   = $this->loadLookup('theme');
        $openings = $this->loadLookup('opening');

        $fh = fopen($path, 'r');
        if ($fh === false) {
            $io->error("fopen failed for {$path}");
            return Command::FAILURE;
        }
        $header = fgetcsv($fh); // discard header
        if ($header === false) {
            $io->error('Empty CSV (no header row)');
            fclose($fh);
            return Command::FAILURE;
        }

        $sqlPuzzle         = 'INSERT OR IGNORE INTO puzzle (id, fen, moves, rating, rating_deviation, popularity, nb_plays, game_url) VALUES (?,?,?,?,?,?,?,?)';
        $sqlPuzzleTheme    = 'INSERT OR IGNORE INTO puzzle_theme (puzzle_id, theme_id) VALUES (?,?)';
        $sqlPuzzleOpening  = 'INSERT OR IGNORE INTO puzzle_opening (puzzle_id, opening_id) VALUES (?,?)';
        $sqlThemeLookup    = 'INSERT OR IGNORE INTO theme (name) VALUES (?)';
        $sqlOpeningLookup  = 'INSERT OR IGNORE INTO opening (name) VALUES (?)';

        $progress = new ProgressBar($output, $limit > 0 ? $limit : 0);
        $progress->start();

        $this->conn->beginTransaction();
        $n = 0;

        while (($row = fgetcsv($fh)) !== false) {
            // Lichess columns: PuzzleId, FEN, Moves, Rating, RatingDeviation, Popularity, NbPlays, Themes, GameUrl, OpeningTags
            if (count($row) < 8) {
                continue;
            }
            [$id, $fen, $moves, $rating, $rd, $pop, $plays, $themesStr] = $row;
            $url         = $row[8] ?? null;
            $openingsStr = $row[9] ?? '';

            $this->conn->executeStatement($sqlPuzzle, [
                $id, $fen, $moves,
                (int) $rating, (int) $rd, (int) $pop, (int) $plays,
                $url !== '' ? $url : null,
            ]);

            foreach ($this->splitTags($themesStr) as $name) {
                $tid = $themes[$name] ?? null;
                if ($tid === null) {
                    $this->conn->executeStatement($sqlThemeLookup, [$name]);
                    $tid = (int) $this->conn->fetchOne('SELECT id FROM theme WHERE name = ?', [$name]);
                    $themes[$name] = $tid;
                }
                $this->conn->executeStatement($sqlPuzzleTheme, [$id, $tid]);
            }

            foreach ($this->splitTags($openingsStr) as $name) {
                $oid = $openings[$name] ?? null;
                if ($oid === null) {
                    $this->conn->executeStatement($sqlOpeningLookup, [$name]);
                    $oid = (int) $this->conn->fetchOne('SELECT id FROM opening WHERE name = ?', [$name]);
                    $openings[$name] = $oid;
                }
                $this->conn->executeStatement($sqlPuzzleOpening, [$id, $oid]);
            }

            $n++;
            if ($n % $batch === 0) {
                $this->conn->commit();
                $this->conn->beginTransaction();
                $progress->advance($batch);
            }
            if ($limit > 0 && $n >= $limit) {
                break;
            }
        }

        $this->conn->commit();
        fclose($fh);
        $progress->finish();
        $output->writeln('');

        $io->success(sprintf('Imported %d puzzles. Themes: %d, openings: %d.', $n, count($themes), count($openings)));
        return Command::SUCCESS;
    }

    /** @return array<string,int> */
    private function loadLookup(string $table): array
    {
        $rows = $this->conn->fetchAllAssociative("SELECT id, name FROM {$table}");
        $map  = [];
        foreach ($rows as $r) {
            $map[(string) $r['name']] = (int) $r['id'];
        }
        return $map;
    }

    /** @return list<string> */
    private function splitTags(?string $s): array
    {
        if ($s === null || $s === '') return [];
        $parts = preg_split('/\s+/', trim($s), -1, PREG_SPLIT_NO_EMPTY);
        return $parts === false ? [] : $parts;
    }
}
