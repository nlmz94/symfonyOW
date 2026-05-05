<?php

namespace App\Command;

use App\Entity\Anime;
use App\Entity\AnimeCharacter;
use App\Entity\AnimeStaff;
use App\Entity\Character;
use App\Entity\Genre;
use App\Entity\Producer;
use App\Entity\Staff;
use App\Entity\Studio;
use App\Repository\AnimeRepository;
use App\Repository\CharacterRepository;
use App\Repository\GenreRepository;
use App\Repository\ProducerRepository;
use App\Repository\StaffRepository;
use App\Repository\StudioRepository;
use App\Service\AniListClient;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Random\RandomException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

#[AsCommand(name: 'app:anime:scrape', description: 'Scrape anime data from AniList and upsert into the DB')]
class ScrapeAniListCommand extends Command
{
    private const string COVER_DIR = '/public/images/animes';

    /** @var array<int, Staff> */
    private array $staffByAnilistId = [];
    /** @var array<int, Character> */
    private array $characterByAnilistId = [];
    /** @var array<string, Genre> */
    private array $genreByName = [];
    /** @var array<string, Studio> */
    private array $studioByName = [];
    /** @var array<string, Producer> */
    private array $producerByName = [];

    /** @var array{inserted:int, updated:int, skipped:int, errors:int} */
    private array $stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

    private EntityManagerInterface $em;

    public function __construct(
        private readonly AniListClient        $anilist,
        private readonly ManagerRegistry      $registry,
        private readonly AnimeRepository      $animeRepo,
        private readonly GenreRepository      $genreRepo,
        private readonly StudioRepository     $studioRepo,
        private readonly ProducerRepository   $producerRepo,
        private readonly CharacterRepository  $characterRepo,
        private readonly StaffRepository      $staffRepo,
        private readonly HttpClientInterface  $httpClient,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string               $projectDir,
    ) {
        parent::__construct();
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManager();
        $this->em = $em;
    }

    private function clearIdentityMaps(): void
    {
        $this->staffByAnilistId = [];
        $this->characterByAnilistId = [];
        $this->genreByName = [];
        $this->studioByName = [];
        $this->producerByName = [];
    }

    private function ensureEmOpen(): void
    {
        if (!$this->em->isOpen()) {
            $this->registry->resetManager();
            /** @var EntityManagerInterface $em */
            $em = $this->registry->getManager();
            $this->em = $em;
            $this->clearIdentityMaps();
        }
    }

    protected function configure(): void
    {
        $this
            ->addOption('pages', null, InputOption::VALUE_REQUIRED, 'Number of pages to fetch (ignored if --all)', 1)
            ->addOption('all', null, InputOption::VALUE_NONE, 'Paginate until AniList has no more results')
            ->addOption('start-page', null, InputOption::VALUE_REQUIRED, 'First page (1-indexed)', 1)
            ->addOption('per-page', null, InputOption::VALUE_REQUIRED, 'Items per page (max 50)', 50)
            ->addOption('sort', null, InputOption::VALUE_REQUIRED, 'AniList sort: POPULARITY_DESC, TRENDING_DESC, SCORE_DESC, START_DATE_DESC', 'POPULARITY_DESC')
            ->addOption('year', null, InputOption::VALUE_REQUIRED, 'Filter by seasonYear (e.g. 2023)')
            ->addOption('all-strategies', null, InputOption::VALUE_NONE, 'Run multiple sort orders + per-year iteration (1940 → current) for full catalog coverage')
            ->addOption('year-from', null, InputOption::VALUE_REQUIRED, 'When --all-strategies, earliest year to iterate (default 1940)', 1940)
            ->addOption('year-to', null, InputOption::VALUE_REQUIRED, 'When --all-strategies, latest year to iterate (default current year)')
            ->addOption('update-existing', null, InputOption::VALUE_NONE, 'Update anime that already exist (matched by anilist_id)')
            ->addOption('skip-images', null, InputOption::VALUE_NONE, 'Do not download cover/banner files');
    }

    /**
     * @throws RandomException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $all            = (bool) $input->getOption('all');
        $allStrategies  = (bool) $input->getOption('all-strategies');
        $pages          = $all ? PHP_INT_MAX : max(1, (int) $input->getOption('pages'));
        $startPage      = max(1, (int) $input->getOption('start-page'));
        $perPage        = min(50, max(1, (int) $input->getOption('per-page')));
        $sort           = (string) $input->getOption('sort');
        $year           = $input->getOption('year') !== null ? (int) $input->getOption('year') : null;
        $yearFrom       = (int) $input->getOption('year-from');
        $yearTo         = $input->getOption('year-to') !== null ? (int) $input->getOption('year-to') : (int) date('Y');
        $updateExisting = (bool) $input->getOption('update-existing');
        $skipImages     = (bool) $input->getOption('skip-images');
        $coverDir = $this->projectDir . self::COVER_DIR;

        if (!$skipImages && !is_dir($coverDir) && !mkdir($coverDir, 0775, true) && !is_dir($coverDir)) {
            $io->error("Cannot create cover dir: $coverDir");
            return Command::FAILURE;
        }

        $this->stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

        if ($allStrategies) {
            $io->title('AniList scrape (all strategies)');
            $io->writeln(sprintf('year-range=%d:%d  skip-images=%s', $yearFrom, $yearTo, $skipImages ? 'yes' : 'no'));

            // Phase 1: broad sweeps with 4 different sort orders
            $phase1Sorts = ['POPULARITY_DESC', 'SCORE_DESC', 'TRENDING_DESC', 'START_DATE_DESC'];
            foreach ($phase1Sorts as $i => $strategySort) {
                $io->section(sprintf('Phase 1.%d/%d — broad sweep sort=%s', $i + 1, count($phase1Sorts), $strategySort));
                $this->runStrategy($io, $output, $strategySort, null, true, PHP_INT_MAX, 1, $perPage,
                    $i === 0 ? $updateExisting : true, $skipImages);
            }

            // Phase 2: per-year deep sweep (catches the long tail beyond AniList's 5000-result cap)
            $totalYears = $yearTo - $yearFrom + 1;
            $i = 0;
            for ($y = $yearTo; $y >= $yearFrom; $y--) {
                $i++;
                $io->section(sprintf('Phase 2.%d/%d — year=%d', $i, $totalYears, $y));
                $this->runStrategy($io, $output, 'POPULARITY_DESC', $y, true, PHP_INT_MAX, 1, $perPage,
                    true, $skipImages);
            }

            $io->success(sprintf(
                'All strategies done. inserted=%d  updated=%d  skipped=%d  errors=%d',
                $this->stats['inserted'], $this->stats['updated'], $this->stats['skipped'], $this->stats['errors']
            ));
            return $this->stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
        }

        // Single-strategy mode
        $io->title('AniList scrape');
        $io->writeln(sprintf(
            'pages=%s  start-page=%d  per-page=%d  sort=%s  year=%s  update-existing=%s  skip-images=%s',
            $all ? 'ALL' : (string) $pages, $startPage, $perPage, $sort, $year ?? '-',
            $updateExisting ? 'yes' : 'no', $skipImages ? 'yes' : 'no'
        ));

        $this->runStrategy($io, $output, $sort, $year, $all, $pages, $startPage, $perPage, $updateExisting, $skipImages);

        $io->success(sprintf(
            'Done. inserted=%d  updated=%d  skipped=%d  errors=%d',
            $this->stats['inserted'], $this->stats['updated'], $this->stats['skipped'], $this->stats['errors']
        ));

        return $this->stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Runs a single (sort, year) strategy, paginating until exhaustion or fixed page count.
     * Updates $this->stats in place.
     * @throws RandomException
     */
    private function runStrategy(
        SymfonyStyle    $io,
        OutputInterface $output,
        string          $sort,
        ?int            $year,
        bool            $all,
        int             $pages,
        int             $startPage,
        int             $perPage,
        bool            $updateExisting,
        bool            $skipImages,
    ): void {
        $progress = null;

        for ($p = 0; $p < $pages; $p++) {
            $page = $startPage + $p;

            try {
                $result = $this->anilist->fetchPage($page, $perPage, $sort, $year);
            } catch (Throwable $e) {
                $msg = $e->getMessage();

                // AniList caps page numbers (~100 with perPage=50). Treat that as end-of-results.
                $progress?->clear();

                if (str_contains($msg, 'Page exceeds maximum')) {
                    $io->writeln("Page $page beyond AniList's public cap; ending this strategy.");
                    $progress?->display();
                    break;
                }

                $io->error("Page $page failed: " . $msg);
                $progress?->display();
                $this->stats['errors']++;
                sleep(60);
                continue;
            }

            $remaining = $result['rateLimitRemaining'];
            $mediaList = $result['data']['Page']['media'];
            $pageInfo  = $result['data']['Page']['pageInfo'];
            $hasNext   = (bool) $pageInfo['hasNextPage'];

            if (count($mediaList) === 0) {
                break;
            }

            if ($progress === null) {
                $total = $all ? (int) $pageInfo['total'] : ($pages * $perPage);
                $progress = new ProgressBar($output, max(1, $total));
                $progress->setFormat(
                    " %current%/%max% [%bar%] %percent:3s%%  %elapsed:6s%/%estimated:-6s% \n"
                    . " ins=%inserted% upd=%updated% skip=%skipped% err=%errors%  rate=%rate%/min  page=%page%/%lastpage%\n"
                    . " > %title%\n"
                );
                $progress->setBarCharacter('█');
                $progress->setEmptyBarCharacter('░');
                $progress->setProgressCharacter('▓');
                $progress->setBarWidth(40);
                $progress->setMessage('-', 'title');
                $this->updateStatsMessages($progress, $this->stats, $page, (int) $pageInfo['lastPage'], $remaining);
                $progress->start();
            } else {
                $this->updateStatsMessages($progress, $this->stats, $page, (int) $pageInfo['lastPage'], $remaining);
            }

            foreach ($mediaList as $media) {
                $titleForBar = $media['title']['english']
                    ?? $media['title']['romaji']
                    ?? $media['title']['native']
                    ?? '?';
                $progress->setMessage($this->truncate($titleForBar), 'title');
                $progress->display();

                try {
                    $this->ensureEmOpen();
                    $action = $this->upsertAnime($media, $updateExisting, $skipImages);
                    $this->stats[$action]++;
                } catch (Throwable $e) {
                    $this->stats['errors']++;
                    $progress->clear();
                    $io->warning(sprintf('media id=%s failed: %s', $media['id'] ?? '?', $e->getMessage()));
                    $progress->display();
                    $this->ensureEmOpen();
                }

                $this->updateStatsMessages($progress, $this->stats, $page, (int) $pageInfo['lastPage'], $remaining);
                $progress->advance();
            }

            try {
                $this->em->flush();
            } catch (Throwable $e) {
                $progress->clear();
                $io->warning('Page flush failed: ' . $e->getMessage());
                $progress->display();
            }

            $this->em->clear();
            $this->clearIdentityMaps();
            $this->politeDelay($remaining, $progress);

            if (!$hasNext) {
                break;
            }
        }

        $progress?->finish();
        $io->newLine(2);
    }

    /**
     * AniList ToS allows ~90 req/min; we target ~50 req/min sustained.
     * Sleeps ~1.2s + random 0–400ms jitter between API requests, longer if rate budget is low.
     * @throws RandomException
     */
    private function politeDelay(?int $remaining, ?ProgressBar $progress): void
    {
        if ($remaining !== null && $remaining <= 10) {
            if ($progress) {
                $progress->setMessage('rate-limit low, sleeping 60s', 'title');
                $progress->display();
            }
            sleep(60);
            return;
        }
        // ~50 req/min with jitter
        usleep(1_200_000 + random_int(0, 400_000));
    }

    /** @param array{inserted:int, updated:int, skipped:int, errors:int} $stats */
    private function updateStatsMessages(ProgressBar $bar, array $stats, int $page, int $lastPage, ?int $remaining): void
    {
        $bar->setMessage((string) $stats['inserted'], 'inserted');
        $bar->setMessage((string) $stats['updated'],  'updated');
        $bar->setMessage((string) $stats['skipped'],  'skipped');
        $bar->setMessage((string) $stats['errors'],   'errors');
        $bar->setMessage($remaining === null ? '?' : (string) $remaining, 'rate');
        $bar->setMessage((string) $page, 'page');
        $bar->setMessage($lastPage > 0 ? (string) $lastPage : '?', 'lastpage');
    }

    private function truncate(string $s): string
    {
        return mb_strlen($s) <= 70 ? $s : mb_substr($s, 0, 70 - 1) . '…';
    }

    /** @param array<string, mixed> $m  @return 'inserted'|'updated'|'skipped' */
    private function upsertAnime(array $m, bool $updateExisting, bool $skipImages): string
    {
        $anilistId = (int) $m['id'];
        $existing = $this->animeRepo->findOneBy(['anilistId' => $anilistId]);

        if ($existing && !$updateExisting) {
            return 'skipped';
        }

        $anime = $existing ?? new Anime();
        $isNew = !$existing;
        $titles = $m['title'] ?? [];
        $primary = $titles['english'] ?? $titles['romaji'] ?? $titles['native'] ?? 'Untitled';
        $anime->setAnilistId($anilistId);
        $anime->setMalId(isset($m['idMal']) ? (int) $m['idMal'] : null);
        $anime->setTitle($primary);
        $anime->setTitleEnglish($titles['english'] ?? null);
        $anime->setTitleRomaji($titles['romaji'] ?? null);
        $anime->setTitleNative($titles['native'] ?? null);
        $anime->setSynopsis(isset($m['description']) ? html_entity_decode(strip_tags($m['description'])) : null);
        $anime->setEpisodes($m['episodes'] ?? null);
        $anime->setDuration($m['duration'] ?? null);
        $anime->setFormat($m['format'] ?? null);
        $anime->setStatus($m['status'] ?? null);
        $anime->setSource($m['source'] ?? null);
        $anime->setSeason($m['season'] ?? null);
        $anime->setSeasonYear($m['seasonYear'] ?? null);
        $anime->setStartDate($this->parseFuzzyDate($m['startDate'] ?? null));
        $anime->setEndDate($this->parseFuzzyDate($m['endDate'] ?? null));
        $anime->setCountryOfOrigin($m['countryOfOrigin'] ?? null);
        $anime->setIsAdult((bool) ($m['isAdult'] ?? false));
        $anime->setAverageScore($m['averageScore'] ?? null);
        $anime->setMeanScore($m['meanScore'] ?? null);
        $anime->setPopularity($m['popularity'] ?? null);
        $anime->setFavourites($m['favourites'] ?? null);
        $anime->setCoverColor($m['coverImage']['color'] ?? null);
        $anime->setOldImgUrl($m['coverImage']['large'] ?? $m['coverImage']['extraLarge'] ?? null);
        $anime->setOldBannerUrl($m['bannerImage'] ?? null);
        $anime->setUpdatedAt(new DateTimeImmutable());

        if (!empty($m['trailer']) && ($m['trailer']['site'] ?? null) === 'youtube') {
            $anime->setTrailerYoutubeId($m['trailer']['id'] ?? null);
        }

        $this->syncGenres($anime, $m['genres'] ?? []);
        $this->syncStudios($anime, $m['studios']['edges'] ?? []);
        $this->syncCharacters($anime, $m['characters']['edges'] ?? []);
        $this->syncStaff($anime, $m['staff']['edges'] ?? []);

        if ($isNew) {
            $this->em->persist($anime);
            $this->em->flush(); // get the id for cover filename
        }

        if (!$skipImages) {
            $this->downloadImages($anime);
        }

        return $isNew ? 'inserted' : 'updated';
    }

    /** @param array<int, string> $genreNames */
    private function syncGenres(Anime $anime, array $genreNames): void
    {
        $existing = [];
        foreach ($anime->getGenres() as $g) { $existing[(string) $g->getName()] = $g; }

        foreach ($genreNames as $name) {
            if (isset($existing[$name])) { continue; }
            $genre = $this->getOrCreateGenre($name);
            $anime->addGenre($genre);
        }
    }

    private function getOrCreateGenre(string $name): Genre
    {
        if (isset($this->genreByName[$name])) {
            return $this->genreByName[$name];
        }
        $genre = $this->genreRepo->findOneBy(['name' => $name]);
        if (!$genre) {
            $genre = new Genre()->setName($name);
            $this->em->persist($genre);
        }
        $this->genreByName[$name] = $genre;
        return $genre;
    }

    private function getOrCreateStudio(string $name): Studio
    {
        if (isset($this->studioByName[$name])) {
            return $this->studioByName[$name];
        }
        $studio = $this->studioRepo->findOneBy(['name' => $name]);
        if (!$studio) {
            $studio = new Studio()->setName($name);
            $this->em->persist($studio);
        }
        $this->studioByName[$name] = $studio;
        return $studio;
    }

    private function getOrCreateProducer(string $name): Producer
    {
        if (isset($this->producerByName[$name])) {
            return $this->producerByName[$name];
        }
        $producer = $this->producerRepo->findOneBy(['name' => $name]);
        if (!$producer) {
            $producer = new Producer()->setName($name);
            $this->em->persist($producer);
        }
        $this->producerByName[$name] = $producer;
        return $producer;
    }

    /** @param array<int, array<string, mixed>> $edges */
    private function syncStudios(Anime $anime, array $edges): void
    {
        foreach ($edges as $edge) {
            $node = $edge['node'] ?? null;
            if (!$node) { continue; }
            $name = (string) ($node['name'] ?? '');
            if ($name === '') { continue; }
            $isMain = (bool) ($edge['isMain'] ?? false);

            if ($isMain) {
                $studio = $this->getOrCreateStudio($name);
                if (!$anime->getStudios()->contains($studio)) {
                    $anime->addStudio($studio);
                }
            } else {
                $producer = $this->getOrCreateProducer($name);
                if (!$anime->getProducers()->contains($producer)) {
                    $anime->addProducer($producer);
                }
            }
        }
    }

    /** @param array<int, array<string, mixed>> $edges */
    private function syncCharacters(Anime $anime, array $edges): void
    {
        // Wipe existing AnimeCharacter joins for this anime and rebuild — simpler than diffing.
        // Doctrine flushes INSERTs before DELETEs, so we must flush deletes first when re-adding
        // joins with the same unique key (anime_id, character_id).
        $hadExistingJoins = !$anime->getCharacters()->isEmpty();
        foreach ($anime->getCharacters() as $ac) {
            $this->em->remove($ac);
        }
        $anime->getCharacters()->clear();
        if ($hadExistingJoins) {
            $this->em->flush();
        }

        $seenCharacterIds = [];
        foreach ($edges as $edge) {
            $node = $edge['node'] ?? null;
            if (!$node) { continue; }
            $aid = (int) ($node['id'] ?? 0);
            if ($aid === 0) { continue; }
            if (isset($seenCharacterIds[$aid])) { continue; }
            $seenCharacterIds[$aid] = true;

            $character = $this->getOrCreateCharacter($aid, $node);

            $voiceActor = null;
            $vaList = $edge['voiceActors'] ?? [];
            if (!empty($vaList)) {
                $vaNode = $vaList[0];
                $voiceActor = $this->upsertStaff([
                    'id'        => $vaNode['id'] ?? null,
                    'name'      => $vaNode['name'] ?? null,
                    'image'     => $vaNode['image'] ?? null,
                    'languageV2'=> $vaNode['languageV2'] ?? null,
                ]);
            }

            $ac = new AnimeCharacter()
                ->setAnime($anime)
                ->setCharacter($character)
                ->setVoiceActor($voiceActor)
                ->setRole($edge['role'] ?? null);
            $this->em->persist($ac);
            $anime->getCharacters()->add($ac);
        }
    }

    /** @param array<int, array<string, mixed>> $edges */
    private function syncStaff(Anime $anime, array $edges): void
    {
        // Same flush-before-rebuild concern as syncCharacters
        $hadExistingJoins = !$anime->getStaff()->isEmpty();
        foreach ($anime->getStaff() as $as) {
            $this->em->remove($as);
        }
        $anime->getStaff()->clear();
        if ($hadExistingJoins) {
            $this->em->flush();
        }

        // Dedupe by (anilist staff id, role) — AniList sometimes lists the same person
        // twice with the same role and our unique constraint would reject the second insert.
        $seen = [];
        foreach ($edges as $edge) {
            $node = $edge['node'] ?? null;
            if (!$node) { continue; }
            $role = substr((string) ($edge['role'] ?? ''), 0, 128);
            if ($role === '') { continue; }

            $anilistStaffId = (int) ($node['id'] ?? 0);
            if ($anilistStaffId === 0) { continue; }

            $key = $anilistStaffId . '|' . $role;
            if (isset($seen[$key])) { continue; }
            $seen[$key] = true;

            $staff = $this->upsertStaff([
                'id'         => $anilistStaffId,
                'name'       => $node['name'] ?? null,
                'image'      => $node['image'] ?? null,
                'languageV2' => $node['languageV2'] ?? null,
            ]);
            if (!$staff) { continue; }

            $as = new AnimeStaff()
                ->setAnime($anime)
                ->setStaff($staff)
                ->setRole($role);
            $this->em->persist($as);
            $anime->getStaff()->add($as);
        }
    }

    /** @param array<string, mixed> $data */
    private function upsertStaff(array $data): ?Staff
    {
        $aid = (int) ($data['id'] ?? 0);
        if ($aid === 0) { return null; }

        if (isset($this->staffByAnilistId[$aid])) {
            return $this->staffByAnilistId[$aid];
        }

        $staff = $this->staffRepo->findOneByAnilistId($aid);
        if (!$staff) {
            $language = $data['languageV2'] ?? null;
            $staff = new Staff()
                ->setAnilistId($aid)
                ->setName(mb_substr((string) ($data['name']['full'] ?? 'Unknown'), 0, 256))
                ->setOldImageUrl($data['image']['large'] ?? null)
                ->setLanguage($language !== null ? mb_substr((string) $language, 0, 32) : null);
            $this->em->persist($staff);
        }
        $this->staffByAnilistId[$aid] = $staff;
        return $staff;
    }

    /** @param array<string, mixed> $node */
    private function getOrCreateCharacter(int $aid, array $node): Character
    {
        if (isset($this->characterByAnilistId[$aid])) {
            return $this->characterByAnilistId[$aid];
        }

        $character = $this->characterRepo->findOneByAnilistId($aid);
        if (!$character) {
            $gender = $node['gender'] ?? null;
            $character = new Character()
                ->setAnilistId($aid)
                ->setName(mb_substr((string) ($node['name']['full'] ?? 'Unknown'), 0, 256))
                ->setOldImageUrl($node['image']['large'] ?? null)
                ->setGender($gender !== null ? mb_substr((string) $gender, 0, 64) : null);
            $this->em->persist($character);
        }
        $this->characterByAnilistId[$aid] = $character;
        return $character;
    }

    private function downloadImages(Anime $anime): void
    {
        $id = $anime->getId();
        if (!$id) { return; }

        $coverPath = sprintf('%s/images/animes/%d_cover.jpg', $this->projectDir . '/public', $id);
        $bannerPath = sprintf('%s/images/animes/%d_banner.jpg', $this->projectDir . '/public', $id);

        if ($anime->getOldImgUrl() && !file_exists($coverPath)) {
            if ($this->downloadFile($anime->getOldImgUrl(), $coverPath)) {
                $anime->setImgUrl(sprintf('/images/animes/%d_cover.jpg', $id));
            }
        }
        if ($anime->getOldBannerUrl() && !file_exists($bannerPath)) {
            if ($this->downloadFile($anime->getOldBannerUrl(), $bannerPath)) {
                $anime->setBannerUrl(sprintf('/images/animes/%d_banner.jpg', $id));
            }
        }
    }

    private function downloadFile(string $url, string $destination): bool
    {
        try {
            $response = $this->httpClient->request('GET', $url, ['timeout' => 20]);
            if ($response->getStatusCode() !== 200) { return false; }
            file_put_contents($destination, $response->getContent(false));
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /** @param array{year: ?int, month: ?int, day: ?int}|null $d */
    private function parseFuzzyDate(?array $d): ?DateTimeImmutable
    {
        if (!$d || empty($d['year'])) { return null; }
        $month = (int) ($d['month'] ?? 1);
        $day   = (int) ($d['day']   ?? 1);
        try {
            return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $d['year'], $month, $day));
        } catch (Throwable) {
            return null;
        }
    }
}
