<?php

namespace App\Entity;

use App\Repository\AnimeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnimeRepository::class)]
#[ORM\Table(name: 'anime')]
class Anime
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'title', length: 1024)]
    private string $title;

    #[ORM\Column(name: 'title_english', length: 1024, nullable: true)]
    private ?string $titleEnglish = null;

    #[ORM\Column(name: 'synopsis', type: 'text', nullable: true)]
    private ?string $synopsis = null;

    #[ORM\Column(name: 'img_url', length: 1024, nullable: true)]
    private ?string $imgUrl = null;         // NEW local path (/images/animes/123_cover.jpg)

    #[ORM\Column(name: 'old_img_url', length: 1024, nullable: true)]
    private ?string $oldImgUrl = null;      // ORIGINAL remote URL

    #[ORM\Column(name: 'episodes', type: 'integer', nullable: true)]
    private ?int $episodes = null;

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

    public function __construct()
    {
        $this->genres = new ArrayCollection();
        $this->producers = new ArrayCollection();
        $this->studios = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }

    public function getTitleEnglish(): ?string { return $this->titleEnglish; }
    public function setTitleEnglish(?string $titleEnglish): self { $this->titleEnglish = $titleEnglish; return $this; }

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
