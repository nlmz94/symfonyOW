<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class UserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
    ) {}

    #[Route('/user', name: 'app_profile')]
    public function index(): Response
    {
        return $this->render('user/profile.html.twig');
    }

    #[Route('/user/profile-image', name: 'app_profile_upload_image', methods: ['POST'])]
    public function uploadProfileImage(Request $request): Response
    {
        $file = $request->files->get('profile_picture');

        if ($file) {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/users/profilePics';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $file->move($uploadDir, $newFilename);
            $user = $this->getUser();

            if ($user instanceof User) {
                if ($user->getProfilePic() !== null && $user->getProfilePic() !== '') {
                    unlink($this->getParameter('kernel.project_dir') . '/public' . $user->getProfilePic());
                }

                $user->setProfilePic('/users/profilePics/' . $newFilename);
                $this->entityManager->flush();
            }
        }

        return $this->redirectToRoute('app_profile');
    }
}
