<?php

namespace App\Entity;

use App\Repository\CharacterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CharacterRepository::class)]
#[ORM\Table(name: '`character`')]
class Character
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'anilist_id', type: 'integer', unique: true, nullable: true)]
    private ?int $anilistId = null;

    #[ORM\Column(name: 'name', length: 256)]
    private string $name;

    #[ORM\Column(name: 'image_url', length: 1024, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(name: 'old_image_url', length: 1024, nullable: true)]
    private ?string $oldImageUrl = null;

    #[ORM\Column(name: 'gender', length: 64, nullable: true)]
    private ?string $gender = null;

    /** @var Collection<int, AnimeCharacter> */
    #[ORM\OneToMany(mappedBy: 'character', targetEntity: AnimeCharacter::class)]
    private Collection $animeCharacters;

    public function __construct()
    {
        $this->animeCharacters = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getAnilistId(): ?int { return $this->anilistId; }
    public function setAnilistId(?int $v): self { $this->anilistId = $v; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $v): self { $this->name = $v; return $this; }
    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $v): self { $this->imageUrl = $v; return $this; }
    public function getOldImageUrl(): ?string { return $this->oldImageUrl; }
    public function setOldImageUrl(?string $v): self { $this->oldImageUrl = $v; return $this; }
    public function getGender(): ?string { return $this->gender; }
    public function setGender(?string $v): self { $this->gender = $v; return $this; }

    /** @return Collection<int, AnimeCharacter> */
    public function getAnimeCharacters(): Collection { return $this->animeCharacters; }
}
