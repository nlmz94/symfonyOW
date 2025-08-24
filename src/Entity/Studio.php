<?php


namespace App\Entity;

use App\Repository\StudioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudioRepository::class)]
#[ORM\Table(name: 'studio')]
class Studio
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', length: 512, nullable: true)]
    private ?string $name = null;

    /** @var Collection<int, Anime> */
    #[ORM\ManyToMany(targetEntity: Anime::class, mappedBy: 'studios')]
    private Collection $animes;

    public function __construct()
    {
        $this->animes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /** @return Collection<int, Anime> */
    public function getAnimes(): Collection
    {
        return $this->animes;
    }

    public function addAnime(Anime $anime): self
    {
        if (!$this->animes->contains($anime)) {
            $this->animes->add($anime);
        }
        return $this;
    }

    public function removeAnime(Anime $anime): self
    {
        $this->animes->removeElement($anime);
        return $this;
    }

    public function __toString(): string
    {
        return (string)$this->name;
    }
}
