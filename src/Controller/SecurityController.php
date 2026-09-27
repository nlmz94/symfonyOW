<?php

namespace App\Controller;

use App\Api\Dto\RegisterRequest;
use App\Api\UserPresenter;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/auth')]
final class SecurityController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly UserPresenter $presenter,
    ) {
    }

    /**
     * The json_login authenticator handles the credentials. Bad credentials never
     * reach this method (the firewall answers 401); a successful login does, and
     * gets the user back along with the session cookie.
     */
    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(
                ['title' => 'Unauthorized', 'status' => 401, 'detail' => 'Missing credentials.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        return $this->json(['data' => $this->presenter->present($user)]);
    }

    /**
     * POST only, so a cross-site link or <img> cannot log the user out.
     * The firewall intercepts it; see LogoutResponseListener for the response.
     */
    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): never
    {
        throw new \LogicException('Intercepted by the logout key on the main firewall.');
    }

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterRequest $payload, UserRepository $users): JsonResponse
    {
        if ($users->findOneBy(['email' => strtolower($payload->email)]) !== null) {
            return $this->emailTaken();
        }

        $user = new User();
        $user->setEmail($payload->email);
        $user->setPassword($this->hasher->hashPassword($user, $payload->password));

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            // Lost a race with a concurrent registration for the same email.
            return $this->emailTaken();
        }

        return $this->json(['data' => $this->presenter->present($user)], Response::HTTP_CREATED);
    }

    private function emailTaken(): JsonResponse
    {
        return $this->json([
            'title' => 'Validation Failed',
            'status' => 422,
            'detail' => 'This email is already registered.',
            'violations' => [
                ['propertyPath' => 'email', 'title' => 'This email is already registered.'],
            ],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
