<?php

namespace App\Command;

use Liip\ImagineBundle\Service\FilterService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:liip:warmup:worker',
    description: 'Worker process for Liip warmup'
)]
class LiipWarmupWorkerCommand extends Command
{
    private array $filters = [
        'poster',
        'thumb',
        'profile',
        'poster_webp',
        'thumb_webp',
        'profile_webp',
    ];

    public function __construct(
        private readonly FilterService $filterService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('chunkFile', InputArgument::REQUIRED, 'File containing image list for this worker');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $chunkFile = $input->getArgument('chunkFile');

        if (!file_exists($chunkFile)) {
            $io->error("Chunk file not found: $chunkFile");
            return Command::FAILURE;
        }

        $images = file($chunkFile, FILE_IGNORE_NEW_LINES);

        foreach ($images as $img) {
            foreach ($this->filters as $filter) {
                try {
                    $this->filterService->getUrlOfFilteredImage($img, $filter);
                    $output->writeln("Worker warmed: {$img} [{$filter}]");
                } catch (\Throwable $e) {
                    $output->writeln("Error {$img} ({$filter}): " . $e->getMessage());
                }
            }
        }

        return Command::SUCCESS;
    }
}
