<?php

namespace App\Controller;

use App\Repository\AnimeRepository;
use App\Repository\GenreRepository;
use App\Repository\StudioRepository;
use App\Serializer\AnimeSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    /**
     * Everything the landing page needs in one round trip.
     */
    #[Route('/home', name: 'api_home', methods: ['GET'])]
    public function index(
        AnimeRepository $animes,
        GenreRepository $genres,
        StudioRepository $studios,
        AnimeSerializer $animeSerializer,
    ): JsonResponse {
        $response = $this->json([
            'data' => [
                'stats' => [
                    'animes' => $animes->count(),
                    'genres' => $genres->count(),
                    'studios' => $studios->count(),
                ],
                'trending' => array_map($animeSerializer->frontPageSerialize(...), $animes->findTrending()),
                'topRated' => array_map($animeSerializer->frontPageSerialize(...), $animes->findTopRated()),
                'recent' => array_map($animeSerializer->frontPageSerialize(...), $animes->findRecentlyAired()),
            ],
        ]);
        $response->setPublic();
        $response->setMaxAge(300);
        $response->setSharedMaxAge(600);

        return $response;
    }
}
