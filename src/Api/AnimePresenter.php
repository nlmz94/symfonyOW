<?php

namespace App\Api;

use App\Entity\Anime;
use App\Entity\AnimeCharacter;
use App\Entity\AnimeStaff;
use App\Entity\Genre;
use App\Entity\Producer;
use App\Entity\Studio;

/**
 * JSON shape of anime resources. Kept as explicit arrays rather than serializer
 * groups so the contract the Nuxt app depends on is visible in one place.
 */
final readonly class AnimePresenter
{
    public function __construct(private ImageUrlGenerator $images)
    {
    }

    /**
     * Light shape for lists and cards.
     *
     * @return array<string, mixed>
     */
    public function summary(Anime $anime): array
    {
        return [
            'id' => $anime->getId(),
            'title' => $anime->getTitle(),
            'titleEnglish' => $anime->getTitleEnglish(),
            'format' => $anime->getFormat(),
            'status' => $anime->getStatus(),
            'episodes' => $anime->getEpisodes(),
            'season' => $anime->getSeason(),
            'seasonYear' => $anime->getSeasonYear(),
            'averageScore' => $anime->getAverageScore(),
            'popularity' => $anime->getPopularity(),
            'isAdult' => $anime->isAdult(),
            'pegi' => $anime->getPegi()?->getPegi(),
            'coverColor' => $anime->getCoverColor(),
            'genres' => array_map(static fn (Genre $g) => $g->getName(), $anime->getGenres()->toArray()),
            'images' => [
                'thumb' => $this->images->filtered($anime->getImgUrl(), 'thumb'),
                'poster' => $this->images->filtered($anime->getImgUrl(), 'poster'),
            ],
        ];
    }

    /**
     * Full shape for the detail page.
     *
     * @return array<string, mixed>
     */
    public function detail(Anime $anime): array
    {
        return [
            ...$this->summary($anime),
            'anilistId' => $anime->getAnilistId(),
            'malId' => $anime->getMalId(),
            'titleRomaji' => $anime->getTitleRomaji(),
            'titleNative' => $anime->getTitleNative(),
            'synopsis' => $anime->getSynopsis(),
            'duration' => $anime->getDuration(),
            'source' => $anime->getSource(),
            'startDate' => $anime->getStartDate()?->format('Y-m-d'),
            'endDate' => $anime->getEndDate()?->format('Y-m-d'),
            'countryOfOrigin' => $anime->getCountryOfOrigin(),
            'meanScore' => $anime->getMeanScore(),
            'favourites' => $anime->getFavourites(),
            'airing' => $anime->getAiring(),
            'aired' => $anime->isAired(),
            'trailerYoutubeId' => $anime->getTrailerYoutubeId(),
            'updatedAt' => $anime->getUpdatedAt()?->format(\DATE_ATOM),
            'bannerUrl' => $this->images->absolute($anime->getBannerUrl() ?? $anime->getOldBannerUrl()),
            'producers' => array_map(
                static fn (Producer $p) => ['id' => $p->getId(), 'name' => $p->getName()],
                $anime->getProducers()->toArray(),
            ),
            'studios' => array_map(
                static fn (Studio $s) => ['id' => $s->getId(), 'name' => $s->getName()],
                $anime->getStudios()->toArray(),
            ),
            'characters' => array_map($this->character(...), $anime->getCharacters()->toArray()),
            'staff' => array_map($this->staff(...), $anime->getStaff()->toArray()),
        ];
    }

    /** @return array<string, mixed> */
    private function character(AnimeCharacter $link): array
    {
        $character = $link->getCharacter();
        $va = $link->getVoiceActor();

        return [
            'id' => $character->getId(),
            'name' => $character->getName(),
            'role' => $link->getRole(),
            'gender' => $character->getGender(),
            'image' => $this->images->absolute($character->getImageUrl() ?? $character->getOldImageUrl()),
            'voiceActor' => $va === null ? null : [
                'id' => $va->getId(),
                'name' => $va->getName(),
                'language' => $va->getLanguage(),
                'image' => $this->images->absolute($va->getImageUrl() ?? $va->getOldImageUrl()),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function staff(AnimeStaff $link): array
    {
        $staff = $link->getStaff();

        return [
            'id' => $staff->getId(),
            'name' => $staff->getName(),
            'role' => $link->getRole(),
            'language' => $staff->getLanguage(),
            'image' => $this->images->absolute($staff->getImageUrl() ?? $staff->getOldImageUrl()),
        ];
    }
}
