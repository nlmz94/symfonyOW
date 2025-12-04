<?php

namespace App\Command;

use Liip\ImagineBundle\Service\FilterService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:liip:warmup',
    description: 'Warm up LiipImagineBundle cache for all images and filters.'
)]
class LiipWarmupCommand extends Command
{
    public function __construct(
        private readonly FilterService $filterService,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    private string $imagesDir = '/public/images';

    private array $filters = [
        'poster',
        'thumb',
        'profile',
        'poster_webp',
        'thumb_webp',
        'profile_webp'
    ];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $absoluteDir = $this->projectDir . $this->imagesDir;

        if (!is_dir($absoluteDir)) {
            $io->error("Directory not found: $absoluteDir");
            return Command::FAILURE;
        }

        $io->title('LiipImagine Cache Warmup');
        $io->text("Scanning directory: {$absoluteDir}");
        $io->newLine();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absoluteDir, \FilesystemIterator::SKIP_DOTS)
        );

        $count = 0;
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $relativePath = str_replace(
                $this->projectDir . '/public',
                '',
                $file->getPathname()
            );

            foreach ($this->filters as $filter) {
                try {
                    $this->filterService->getUrlOfFilteredImage($relativePath, $filter);
                    $io->writeln("✔ Warmed: {$relativePath} [{$filter}]");
                } catch (\Throwable $e) {
                    $io->warning("Failed: {$relativePath} ({$filter}) → " . $e->getMessage());
                }
            }

            $count++;
        }

        $io->success("Warmup completed. Processed {$count} images.");

        return Command::SUCCESS;
    }
}
