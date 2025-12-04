<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/user', name: 'app_profile')]
    public function index(): Response
    {
        return $this->render('user/profile.html.twig');
    }

    #[Route('/user/profile-image', name: 'app_profile_upload_image', methods: ['POST'])]
    public function uploadProfileImage(Request $request): Response
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('profile_picture');

        if (!$file) {
            $this->addFlash('error', 'No file was uploaded.');

            return $this->redirectToRoute('app_profile');
        }

        // file validation
        $constraints = new Assert\Collection([
            'profile_picture' => [
                new Assert\NotNull(['message' => 'Please upload a file.']),
                new Assert\File([
                    'maxSize' => '5M',
                    'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                    'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, or WebP).',
                ]),
                new Assert\Image([
                    'maxWidth' => 2000,
                    'maxHeight' => 2000,
                    'maxWidthMessage' => 'Image width cannot exceed {{ max_width }}px.',
                    'maxHeightMessage' => 'Image height cannot exceed {{ max_height }}px.',
                ]),
            ],
        ]);

        $violations = $this->validator->validate(['profile_picture' => $file], $constraints);

        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $this->addFlash('error', $violation->getMessage());
            }
            return $this->redirectToRoute('app_profile');
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/users/profilePics';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        try {
            $file->move($uploadDir, $newFilename);
        } catch (Exception) {
            $this->addFlash('error', 'Failed to upload file. Please try again.');

            return $this->redirectToRoute('app_profile');
        }

        $user = $this->getUser();

        if ($user instanceof User) {
            if ($user->getProfilePic() !== null && $user->getProfilePic() !== '') {
                $oldPicPath = $this->getParameter('kernel.project_dir') . '/public' . $user->getProfilePic();
                $realUploadDir = realpath($uploadDir);

                if ($realUploadDir && file_exists($oldPicPath)) {
                    $realOldPicPath = realpath($oldPicPath);

                    if ($realOldPicPath && str_starts_with($realOldPicPath, $realUploadDir)) {
                        @unlink($oldPicPath);
                    }
                }
            }

            $user->setProfilePic('/users/profilePics/' . $newFilename);
            $this->entityManager->flush();
            $this->addFlash('success', 'Profile picture updated successfully!');
        }

        return $this->redirectToRoute('app_profile');
    }
}
