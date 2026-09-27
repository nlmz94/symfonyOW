<?php

namespace App\Controller;

use App\Entity\User;
use App\Serializer\UserSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The authenticated user's own account. access_control requires ROLE_USER on
 * ^/api/me, so anonymous requests get a 401 before reaching these methods.
 */
#[Route('/me')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
        private readonly ValidatorInterface $validator,
        private readonly UserSerializer $userSerializer,
    ) {
    }

    #[Route('', name: 'api_me', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json(['data' => $this->userSerializer->accountSerialize($user)]);
    }

    /**
     * multipart/form-data with a "profile_picture" file field.
     */
    #[Route('/profile-picture', name: 'api_me_profile_picture', methods: ['POST'])]
    public function uploadProfilePicture(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $file = $request->files->get('profile_picture');
        $file = $file instanceof UploadedFile ? $file : null;

        $violations = $this->validator->validate($file, [
            new Assert\NotNull(message: 'Please upload a file.'),
            new Assert\Image(
                maxSize: '5M',
                mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                mimeTypesMessage: 'Please upload a valid image (JPEG, PNG, or WebP).',
                maxWidth: 2000,
                maxHeight: 2000,
                maxWidthMessage: 'Image width cannot exceed {{ max_width }}px.',
                maxHeightMessage: 'Image height cannot exceed {{ max_height }}px.',
            ),
        ]);

        if (count($violations) > 0 || $file === null) {
            $messages = array_map(
                static fn (ConstraintViolationInterface $v) => ['propertyPath' => 'profile_picture', 'title' => (string) $v->getMessage()],
                iterator_to_array($violations),
            );

            return $this->json([
                'title' => 'Validation Failed',
                'status' => 422,
                'detail' => implode("\n", array_column($messages, 'title')),
                'violations' => array_values($messages),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
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
        } catch (FileException) {
            return $this->json(
                ['title' => 'Upload Failed', 'status' => 500, 'detail' => 'Failed to upload file. Please try again.'],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        $this->removeOldProfilePicture($user, $uploadDir);

        $user->setProfilePic('/users/profilePics/' . $newFilename);
        $this->entityManager->flush();

        return $this->json(['data' => $this->userSerializer->accountSerialize($user)]);
    }

    private function removeOldProfilePicture(User $user, string $uploadDir): void
    {
        if ($user->getProfilePic() === null || $user->getProfilePic() === '') {
            return;
        }

        $oldPicPath = $this->getParameter('kernel.project_dir') . '/public' . $user->getProfilePic();
        $realUploadDir = realpath($uploadDir);
        $realOldPicPath = realpath($oldPicPath);

        // Only ever delete files inside the upload directory.
        if ($realUploadDir && $realOldPicPath && str_starts_with($realOldPicPath, $realUploadDir . DIRECTORY_SEPARATOR)) {
            @unlink($realOldPicPath);
        }
    }
}
