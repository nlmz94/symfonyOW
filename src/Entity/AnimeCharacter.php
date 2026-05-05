<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'anime_character')]
#[ORM\UniqueConstraint(name: 'uniq_anime_character', columns: ['anime_id', 'character_id'])]
class AnimeCharacter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Anime::class, inversedBy: 'characters')]
    #[ORM\JoinColumn(name: 'anime_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Anime $anime;

    #[ORM\ManyToOne(targetEntity: Character::class, inversedBy: 'animeCharacters')]
    #[ORM\JoinColumn(name: 'character_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Character $character;

    #[ORM\ManyToOne(targetEntity: Staff::class, inversedBy: 'voicedCharacters')]
    #[ORM\JoinColumn(name: 'voice_actor_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Staff $voiceActor = null;

    #[ORM\Column(name: 'role', length: 16, nullable: true)]
    private ?string $role = null;

    public function getId(): ?int { return $this->id; }

    public function getAnime(): Anime { return $this->anime; }
    public function setAnime(Anime $v): self { $this->anime = $v; return $this; }

    public function getCharacter(): Character { return $this->character; }
    public function setCharacter(Character $v): self { $this->character = $v; return $this; }

    public function getVoiceActor(): ?Staff { return $this->voiceActor; }
    public function setVoiceActor(?Staff $v): self { $this->voiceActor = $v; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(?string $v): self { $this->role = $v; return $this; }
}
