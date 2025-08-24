<?php

namespace App\Controller;

use App\Entity\Anime;
use App\Repository\AnimeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AnimeController extends AbstractController
{
    #[Route('/anime', name: 'app_anime')]
    public function index(Request $request, AnimeRepository $repo): Response
    {
        $page  = max(1, $request->query->getInt('page', 1));
        $data = $repo->paginateAll($page);

        return $this->render('anime/index.html.twig', $data);
    }

    #[Route('/anime/{id<\d+>}', name: 'anime_show', methods: ['GET'])]
    public function show(Anime $anime): Response
    {
        return $this->render('anime/show.html.twig', ['anime' => $anime]);
    }
}
