<?php

namespace App\Entity;

use App\Repository\StaffRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StaffRepository::class)]
#[ORM\Table(name: 'staff')]
class Staff
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

    #[ORM\Column(name: 'language', length: 32, nullable: true)]
    private ?string $language = null;

    /** @var Collection<int, AnimeStaff> */
    #[ORM\OneToMany(mappedBy: 'staff', targetEntity: AnimeStaff::class)]
    private Collection $animeStaff;

    /** @var Collection<int, AnimeCharacter> */
    #[ORM\OneToMany(mappedBy: 'voiceActor', targetEntity: AnimeCharacter::class)]
    private Collection $voicedCharacters;

    public function __construct()
    {
        $this->animeStaff = new ArrayCollection();
        $this->voicedCharacters = new ArrayCollection();
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
    public function getLanguage(): ?string { return $this->language; }
    public function setLanguage(?string $v): self { $this->language = $v; return $this; }
}
