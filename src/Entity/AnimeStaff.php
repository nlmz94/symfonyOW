<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'anime_staff')]
#[ORM\UniqueConstraint(name: 'uniq_anime_staff_role', columns: ['anime_id', 'staff_id', 'role'])]
class AnimeStaff
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Anime::class, inversedBy: 'staff')]
    #[ORM\JoinColumn(name: 'anime_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Anime $anime;

    #[ORM\ManyToOne(targetEntity: Staff::class, inversedBy: 'animeStaff')]
    #[ORM\JoinColumn(name: 'staff_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Staff $staff;

    #[ORM\Column(name: 'role', length: 128)]
    private string $role;

    public function getId(): ?int { return $this->id; }

    public function getAnime(): Anime { return $this->anime; }
    public function setAnime(Anime $v): self { $this->anime = $v; return $this; }

    public function getStaff(): Staff { return $this->staff; }
    public function setStaff(Staff $v): self { $this->staff = $v; return $this; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $v): self { $this->role = $v; return $this; }
}
