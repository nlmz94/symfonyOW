<?php

namespace App\Controller;

use App\Api\AnimePresenter;
use App\Repository\AnimeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/animes')]
final class AnimeController extends AbstractController
{
    public function __construct(
        private readonly AnimeRepository $repo,
        private readonly AnimePresenter $presenter,
    ) {
    }

    /**
     * GET /api/animes?page=1&limit=50&searchTerm=naruto
     */
    #[Route('', name: 'api_anime_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $searchTerm = $request->query->getString('searchTerm');
        $result = $this->repo->paginateAll(
            $request->query->getInt('page', 1),
            $searchTerm !== '' ? $searchTerm : null,
            $request->query->getInt('limit', 50),
        );

        return $this->json([
            'data' => array_map($this->presenter->summary(...), $result['items']),
            'meta' => [
                'total' => $result['total'],
                'pages' => $result['pages'],
                'page' => $result['page'],
                'limit' => $result['limit'],
            ],
        ]);
    }

    #[Route('/{id<\d+>}', name: 'api_anime_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $anime = $this->repo->findDetail($id) ?? throw new NotFoundHttpException('Anime not found.');

        $response = $this->json(['data' => $this->presenter->detail($anime)]);
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(86400);

        return $response;
    }
}
