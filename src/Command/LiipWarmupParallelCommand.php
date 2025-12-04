<?php

namespace App\Command;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:liip:warmup:parallel',
    description: 'Warm up Liip cache using multiple parallel workers.'
)]
class LiipWarmupParallelCommand extends Command
{
    private int $workers = 8; // adjust as needed

    public function __construct(
        private KernelInterface $kernel
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $projectDir = $this->kernel->getProjectDir();
        $imagesDir = $projectDir . '/public/images';

        if (!is_dir($imagesDir)) {
            $io->error("Directory not found: $imagesDir");
            return Command::FAILURE;
        }

        // Collect all images
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($imagesDir, FilesystemIterator::SKIP_DOTS)
        );

        $images = [];
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $images[] = str_replace($projectDir . '/public', '', $file->getPathname());
            }
        }

        $io->text("Found " . count($images) . " images.");
        $io->text("Splitting into {$this->workers} workers...");
        $io->newLine();

        // Split images into worker chunks
        $chunks = array_chunk($images, ceil(count($images) / $this->workers));

        $processes = [];

        foreach ($chunks as $index => $chunk) {
            // Save chunk file
            $chunkFile = $projectDir . "/var/liip_chunk_{$index}.txt";
            file_put_contents($chunkFile, implode("\n", $chunk));

            // Worker command
            $cmd = [
                'php', 'bin/console',
                'app:liip:warmup:worker',
                $chunkFile
            ];

            $process = new Process($cmd, $projectDir);
            $process->start();

            $processes[$index] = $process;
        }

        // Live log worker output
        while (count($processes)) {
            foreach ($processes as $index => $process) {
                if ($process->isRunning()) {
                    $output->write($process->getIncrementalOutput());
                    $output->write($process->getIncrementalErrorOutput());
                } else {
                    unset($processes[$index]);
                }
            }
            usleep(100000); // 100ms
        }

        $io->success("Parallel warmup completed with {$this->workers} workers.");

        return Command::SUCCESS;
    }
}
