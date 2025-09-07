<?php

namespace App\Command;

use App\Entity\Anime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;
use function dirname;

#[AsCommand(
    name: 'app:import-anime-images',
    description: 'Downloads images from old_img_url -> /public/images/animes/{id}_cover.jpg; sets img_url to local path'
)]
class ImportAnimeImagesCommand extends Command
{
    private const string TARGET_WEB_PATH = '/images/animes';
    private const int BATCH_SIZE = 100;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private ?HttpClientInterface $http = null,
        private readonly Filesystem $fs = new Filesystem(),
    ) {
        parent::__construct();
        $this->http = $this->http ?? HttpClient::create([
            'timeout' => 20,
            'max_redirects' => 5,
            'headers' => ['User-Agent' => 'OnlyWeebsImageImporter/1.0'],
        ]);
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Re-download even if the local file already exists')
            ->addOption('only-missing', null, InputOption::VALUE_NONE, 'Only process rows where img_url is NULL and old_img_url looks remote')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of rows to process', null)
            ->addOption('start-id', null, InputOption::VALUE_REQUIRED, 'Start from this Anime ID (inclusive)', null)
            ->addOption('end-id', null, InputOption::VALUE_REQUIRED, 'End at this Anime ID (inclusive)', null)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not write files or update DB (log only)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io    = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');
        $onlyMissing = (bool) $input->getOption('only-missing');
        $limit = $input->getOption('limit') !== null ? (int) $input->getOption('limit') : null;
        $startId = $input->getOption('start-id') !== null ? (int) $input->getOption('start-id') : null;
        $endId   = $input->getOption('end-id') !== null ? (int) $input->getOption('end-id') : null;
        $dryRun  = (bool) $input->getOption('dry-run');
        $projectDir = dirname(__DIR__, 2);
        $publicDir  = $projectDir . '/public';
        $targetDir  = $publicDir . self::TARGET_WEB_PATH;

        if (!$dryRun && !$this->fs->exists($targetDir)) {
            $this->fs->mkdir($targetDir, 0775);
        }

        $qb = $this->em->createQueryBuilder()
            ->select('a')
            ->from(Anime::class, 'a');

        if ($startId !== null) {
            $qb->andWhere('a.id >= :startId')->setParameter('startId', $startId);
        }

        if ($endId !== null) {
            $qb->andWhere('a.id <= :endId')->setParameter('endId', $endId);
        }

        if ($onlyMissing) {
            // New local path missing, but we have an old remote URL to fetch.
            $qb->andWhere('a.imgUrl IS NULL AND a.oldImgUrl IS NOT NULL');
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        $iterable = $qb->getQuery()->toIterable();
        $io->title('Importing Anime images (old_img_url -> img_url)');
        $io->writeln(sprintf('Target dir: <info>%s</info>', $targetDir));

        if ($dryRun) {
            $io->warning('Running in --dry-run mode (no writes).');
        }

        $processed = 0;
        $downloaded = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($iterable as $anime) {
            /** @var Anime $anime */
            $processed++;
            $id  = $anime->getId();
            $remote = $anime->getOldImgUrl();

            // If old_img_url empty but img_url is still a remote http(s) (pre-migration data), use that as a source.
            if ((!$remote || !str_starts_with((string)$remote, 'http')) && $anime->getImgUrl() && preg_match('#^https?://#i', $anime->getImgUrl())) {
                $remote = $anime->getImgUrl();
            }

            $filename   = sprintf('%d_cover.jpg', $id);
            $webPath    = self::TARGET_WEB_PATH . '/' . $filename; // to store in DB
            $diskPath   = $targetDir . '/' . $filename;
            $alreadyLocal = ($anime->getImgUrl() && str_starts_with($anime->getImgUrl(), self::TARGET_WEB_PATH));

            if ($alreadyLocal && !$force && file_exists($diskPath)) {
                $skipped++;
                continue;
            }

            // Need a valid remote URL to download
            if (!$remote || !preg_match('#^https?://#i', $remote)) {
                $skipped++;
                continue;
            }

            try {
                $response = $this->http->request('GET', $remote);
                sleep(0.1);

                if (200 !== $response->getStatusCode()) {
                    throw new \RuntimeException('HTTP '.$response->getStatusCode());
                }

                $bytes = $response->getContent();
                $wrote = false;

                if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
                    $img = @imagecreatefromstring($bytes);

                    if ($img !== false) {
                        if (!$dryRun) {
                            imageinterlace($img, true);
                            ob_start();
                            imagejpeg($img, null, 85);
                            $jpegBytes = ob_get_clean();
                            imagedestroy($img);
                            $this->fs->dumpFile($diskPath, $jpegBytes);
                        }

                        $wrote = true;
                    }
                }

                if (!$wrote && !$dryRun) {
                    $this->fs->dumpFile($diskPath, $bytes);
                }

                if (!$dryRun) {
                    $anime->setImgUrl($webPath); // write LOCAL path to new column
                    // keep old_img_url as-is for provenance
                }

                $downloaded++;
            } catch (Throwable $e) {
                $failed++;
                $io->writeln(sprintf('<error>[%d]</error> %s -> %s', $id, (string)$remote, $e->getMessage()));
            }

            if (!$dryRun && $processed % self::BATCH_SIZE === 0) {
                $this->em->flush();
            }
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        $io->success(sprintf(
            'Done. Processed: %d, Downloaded: %d, Skipped: %d, Failed: %d',
            $processed, $downloaded, $skipped, $failed
        ));

        if ($failed > 0) {
            $io->note('Some downloads failed. Re-run with --start-id/--end-id or --force to retry specific ranges.');
        }

        return Command::SUCCESS;
    }
}
