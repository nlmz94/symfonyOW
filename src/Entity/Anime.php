<?php

namespace App\Entity;

use App\Repository\AnimeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnimeRepository::class)]
#[ORM\Table(name: 'anime')]
#[ORM\Cache("NONSTRICT_READ_WRITE")]
class Anime
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'anilist_id', type: 'integer', unique: true, nullable: true)]
    private ?int $anilistId = null;

    #[ORM\Column(name: 'mal_id', type: 'integer', nullable: true)]
    private ?int $malId = null;

    #[ORM\Column(name: 'title', length: 1024)]
    private string $title;

    #[ORM\Column(name: 'title_english', length: 1024, nullable: true)]
    private ?string $titleEnglish = null;

    #[ORM\Column(name: 'title_romaji', length: 1024, nullable: true)]
    private ?string $titleRomaji = null;

    #[ORM\Column(name: 'title_native', length: 1024, nullable: true)]
    private ?string $titleNative = null;

    #[ORM\Column(name: 'synopsis', type: 'text', nullable: true)]
    private ?string $synopsis = null;

    #[ORM\Column(name: 'img_url', length: 1024, nullable: true)]
    private ?string $imgUrl = null;

    #[ORM\Column(name: 'old_img_url', length: 1024, nullable: true)]
    private ?string $oldImgUrl = null;

    #[ORM\Column(name: 'banner_url', length: 1024, nullable: true)]
    private ?string $bannerUrl = null;

    #[ORM\Column(name: 'old_banner_url', length: 1024, nullable: true)]
    private ?string $oldBannerUrl = null;

    #[ORM\Column(name: 'cover_color', length: 16, nullable: true)]
    private ?string $coverColor = null;

    #[ORM\Column(name: 'episodes', type: 'integer', nullable: true)]
    private ?int $episodes = null;

    #[ORM\Column(name: 'duration', type: 'smallint', nullable: true)]
    private ?int $duration = null;

    #[ORM\Column(name: 'format', length: 32, nullable: true)]
    private ?string $format = null;

    #[ORM\Column(name: 'status', length: 32, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(name: 'source', length: 32, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(name: 'season', length: 16, nullable: true)]
    private ?string $season = null;

    #[ORM\Column(name: 'season_year', type: 'smallint', nullable: true)]
    private ?int $seasonYear = null;

    #[ORM\Column(name: 'start_date', type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(name: 'end_date', type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(name: 'country_of_origin', length: 4, nullable: true)]
    private ?string $countryOfOrigin = null;

    #[ORM\Column(name: 'is_adult', type: 'boolean', options: ['default' => false])]
    private bool $isAdult = false;

    #[ORM\Column(name: 'average_score', type: 'smallint', nullable: true)]
    private ?int $averageScore = null;

    #[ORM\Column(name: 'mean_score', type: 'smallint', nullable: true)]
    private ?int $meanScore = null;

    #[ORM\Column(name: 'popularity', type: 'integer', nullable: true)]
    private ?int $popularity = null;

    #[ORM\Column(name: 'favourites', type: 'integer', nullable: true)]
    private ?int $favourites = null;

    #[ORM\Column(name: 'trailer_youtube_id', length: 32, nullable: true)]
    private ?string $trailerYoutubeId = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Pegi::class, inversedBy: 'animes')]
    #[ORM\JoinColumn(name: 'pegi_id', referencedColumnName: 'id', nullable: true)]
    private ?Pegi $pegi = null;

    #[ORM\Column(name: 'airing', length: 1024, nullable: true)]
    private ?string $airing = null;

    #[ORM\Column(name: 'aired', type: 'boolean', nullable: true)]
    private ?bool $aired = null;

    #[ORM\ManyToMany(targetEntity: Genre::class, inversedBy: 'animes')]
    #[ORM\JoinTable(name: 'anime_genre')]
    #[ORM\JoinColumn(name: 'id_anime', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'id_genre', referencedColumnName: 'id')]
    private Collection $genres;

    #[ORM\ManyToMany(targetEntity: Producer::class, inversedBy: 'animes')]
    #[ORM\JoinTable(name: 'anime_producer')]
    #[ORM\JoinColumn(name: 'id_anime', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'id_producer', referencedColumnName: 'id')]
    private Collection $producers;

    #[ORM\ManyToMany(targetEntity: Studio::class, inversedBy: 'animes')]
    #[ORM\JoinTable(name: 'anime_studio')]
    #[ORM\JoinColumn(name: 'id_anime', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'id_studio', referencedColumnName: 'id')]
    private Collection $studios;

    /** @var Collection<int, AnimeCharacter> */
    #[ORM\OneToMany(mappedBy: 'anime', targetEntity: AnimeCharacter::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $characters;

    /** @var Collection<int, AnimeStaff> */
    #[ORM\OneToMany(mappedBy: 'anime', targetEntity: AnimeStaff::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $staff;

    public function __construct()
    {
        $this->genres = new ArrayCollection();
        $this->producers = new ArrayCollection();
        $this->studios = new ArrayCollection();
        $this->characters = new ArrayCollection();
        $this->staff = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getAnilistId(): ?int { return $this->anilistId; }
    public function setAnilistId(?int $id): self { $this->anilistId = $id; return $this; }

    public function getMalId(): ?int { return $this->malId; }
    public function setMalId(?int $id): self { $this->malId = $id; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }

    public function getTitleEnglish(): ?string { return $this->titleEnglish; }
    public function setTitleEnglish(?string $titleEnglish): self { $this->titleEnglish = $titleEnglish; return $this; }

    public function getTitleRomaji(): ?string { return $this->titleRomaji; }
    public function setTitleRomaji(?string $v): self { $this->titleRomaji = $v; return $this; }

    public function getTitleNative(): ?string { return $this->titleNative; }
    public function setTitleNative(?string $v): self { $this->titleNative = $v; return $this; }

    public function getBannerUrl(): ?string { return $this->bannerUrl; }
    public function setBannerUrl(?string $v): self { $this->bannerUrl = $v; return $this; }

    public function getOldBannerUrl(): ?string { return $this->oldBannerUrl; }
    public function setOldBannerUrl(?string $v): self { $this->oldBannerUrl = $v; return $this; }

    public function getCoverColor(): ?string { return $this->coverColor; }
    public function setCoverColor(?string $v): self { $this->coverColor = $v; return $this; }

    public function getDuration(): ?int { return $this->duration; }
    public function setDuration(?int $v): self { $this->duration = $v; return $this; }

    public function getFormat(): ?string { return $this->format; }
    public function setFormat(?string $v): self { $this->format = $v; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $v): self { $this->status = $v; return $this; }

    public function getSource(): ?string { return $this->source; }
    public function setSource(?string $v): self { $this->source = $v; return $this; }

    public function getSeason(): ?string { return $this->season; }
    public function setSeason(?string $v): self { $this->season = $v; return $this; }

    public function getSeasonYear(): ?int { return $this->seasonYear; }
    public function setSeasonYear(?int $v): self { $this->seasonYear = $v; return $this; }

    public function getStartDate(): ?\DateTimeImmutable { return $this->startDate; }
    public function setStartDate(?\DateTimeImmutable $v): self { $this->startDate = $v; return $this; }

    public function getEndDate(): ?\DateTimeImmutable { return $this->endDate; }
    public function setEndDate(?\DateTimeImmutable $v): self { $this->endDate = $v; return $this; }

    public function getCountryOfOrigin(): ?string { return $this->countryOfOrigin; }
    public function setCountryOfOrigin(?string $v): self { $this->countryOfOrigin = $v; return $this; }

    public function isAdult(): bool { return $this->isAdult; }
    public function setIsAdult(bool $v): self { $this->isAdult = $v; return $this; }

    public function getAverageScore(): ?int { return $this->averageScore; }
    public function setAverageScore(?int $v): self { $this->averageScore = $v; return $this; }

    public function getMeanScore(): ?int { return $this->meanScore; }
    public function setMeanScore(?int $v): self { $this->meanScore = $v; return $this; }

    public function getPopularity(): ?int { return $this->popularity; }
    public function setPopularity(?int $v): self { $this->popularity = $v; return $this; }

    public function getFavourites(): ?int { return $this->favourites; }
    public function setFavourites(?int $v): self { $this->favourites = $v; return $this; }

    public function getTrailerYoutubeId(): ?string { return $this->trailerYoutubeId; }
    public function setTrailerYoutubeId(?string $v): self { $this->trailerYoutubeId = $v; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $v): self { $this->updatedAt = $v; return $this; }

    /** @return Collection<int, AnimeCharacter> */
    public function getCharacters(): Collection { return $this->characters; }

    /** @return Collection<int, AnimeStaff> */
    public function getStaff(): Collection { return $this->staff; }

    public function getSynopsis(): ?string { return $this->synopsis; }
    public function setSynopsis(?string $synopsis): self { $this->synopsis = $synopsis; return $this; }

    public function getImgUrl(): ?string { return $this->imgUrl; }
    public function setImgUrl(?string $imgUrl): self { $this->imgUrl = $imgUrl; return $this; }

    public function getOldImgUrl(): ?string { return $this->oldImgUrl; }
    public function setOldImgUrl(?string $old): self { $this->oldImgUrl = $old; return $this; }

    public function getEpisodes(): ?int { return $this->episodes; }
    public function setEpisodes(?int $episodes): self { $this->episodes = $episodes; return $this; }

    public function getPegi(): ?Pegi { return $this->pegi; }
    public function setPegi(?Pegi $pegi): self { $this->pegi = $pegi; return $this; }

    public function getAiring(): ?string { return $this->airing; }
    public function setAiring(?string $airing): self { $this->airing = $airing; return $this; }

    public function isAired(): ?bool { return $this->aired; }
    public function setAired(?bool $aired): self { $this->aired = $aired; return $this; }

    /** @return Collection<int, Genre> */
    public function getGenres(): Collection { return $this->genres; }
    public function addGenre(Genre $genre): self {
        if (!$this->genres->contains($genre)) {
            $this->genres->add($genre);
            $genre->addAnime($this);
        }
        return $this;
    }
    public function removeGenre(Genre $genre): self {
        if ($this->genres->removeElement($genre)) {
            $genre->removeAnime($this);
        }
        return $this;
    }

    /** @return Collection<int, Producer> */
    public function getProducers(): Collection { return $this->producers; }
    public function addProducer(Producer $producer): self {
        if (!$this->producers->contains($producer)) {
            $this->producers->add($producer);
            $producer->addAnime($this);
        }
        return $this;
    }
    public function removeProducer(Producer $producer): self {
        if ($this->producers->removeElement($producer)) {
            $producer->removeAnime($this);
        }
        return $this;
    }

    /** @return Collection<int, Studio> */
    public function getStudios(): Collection { return $this->studios; }
    public function addStudio(Studio $studio): self {
        if (!$this->studios->contains($studio)) {
            $this->studios->add($studio);
            $studio->addAnime($this);
        }
        return $this;
    }
    public function removeStudio(Studio $studio): self {
        if ($this->studios->removeElement($studio)) {
            $studio->removeAnime($this);
        }
        return $this;
    }
}
