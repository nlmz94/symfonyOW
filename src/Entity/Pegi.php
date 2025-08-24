<?php

namespace App\Entity;

use App\Repository\PegiRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PegiRepository::class)]
#[ORM\Table(name: 'pegi')]
class Pegi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'pegi', length: 1024, nullable: true)]
    private ?string $pegi = null;

    /** @var Collection<int, Anime> */
    #[ORM\OneToMany(mappedBy: 'pegi', targetEntity: Anime::class)]
    private Collection $animes;

    public function __construct()
    {
        $this->animes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getPegi(): ?string { return $this->pegi; }
    public function setPegi(?string $pegi): self { $this->pegi = $pegi; return $this; }

    /** @return Collection<int, Anime> */
    public function getAnimes(): Collection { return $this->animes; }
    public function addAnime(Anime $anime): self {
        if (!$this->animes->contains($anime)) {
            $this->animes->add($anime);
            $anime->setPegi($this);
        }
        return $this;
    }
    public function removeAnime(Anime $anime): self {
        if ($this->animes->removeElement($anime)) {
            if ($anime->getPegi() === $this) {
                $anime->setPegi(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->pegi;
    }
}
