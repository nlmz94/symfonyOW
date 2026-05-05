<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'app:anime:wipe', description: 'Truncate all anime data and delete cover/banner files')]
class WipeAnimeCommand extends Command
{
    /** Order matters: child join tables first, then parents. */
    private const TABLES = [
        'anime_character',
        'anime_staff',
        'anime_genre',
        'anime_studio',
        'anime_producer',
        'anime',
        '`character`',
        'staff',
        'genre',
        'studio',
        'producer',
    ];

    public function __construct(
        private readonly Connection $db,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string     $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('keep-images', null, InputOption::VALUE_NONE, 'Keep cover/banner files in public/images/animes/')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Skip the confirmation prompt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $keepImages = (bool) $input->getOption('keep-images');
        $force      = (bool) $input->getOption('force');

        $io->warning([
            'This will TRUNCATE the following tables:',
            '  ' . implode(', ', self::TABLES),
            $keepImages ? '' : 'It will also DELETE every file in public/images/animes/.',
        ]);

        if (!$force && !$io->confirm('Proceed?', false)) {
            $io->writeln('Aborted.');
            return Command::SUCCESS;
        }

        $io->section('Truncating tables');
        $this->db->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach (self::TABLES as $table) {
                $this->db->executeStatement("TRUNCATE TABLE {$table}");
                $io->writeln("  truncated {$table}");
            }
        } finally {
            $this->db->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }

        if (!$keepImages) {
            $io->section('Deleting cover/banner files');
            $dir = $this->projectDir . '/public/images/animes';
            if (is_dir($dir)) {
                $fs = new Filesystem();
                $count = 0;
                foreach (glob($dir . '/*') ?: [] as $file) {
                    if (is_file($file)) {
                        $fs->remove($file);
                        $count++;
                    }
                }
                $io->writeln("  removed {$count} files from {$dir}");
            } else {
                $io->writeln("  {$dir} does not exist; skipping");
            }
        }

        $io->success('Wipe complete. Run `php bin/console app:anime:scrape --all` to repopulate.');
        return Command::SUCCESS;
    }
}
