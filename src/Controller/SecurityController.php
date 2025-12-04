<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $hasher
    ) {
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authUtils): Response
    {
        $error = $authUtils->getLastAuthenticationError();
        $lastUsername = $authUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // The firewall will intercept this. Never executed.
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $csrfToken = $request->request->get('_csrf_token');
            if (!$this->isCsrfTokenValid('register', $csrfToken)) {
                $this->addFlash('error', 'Invalid CSRF token. Please try again.');
                return $this->redirectToRoute('app_register');
            }

            $email = (string) $request->request->get('email');
            $plainPassword = (string) $request->request->get('password');

            if (empty($email) || empty($plainPassword)) {
                $this->addFlash('error', 'Email and password are required.');
                return $this->redirectToRoute('app_register');
            }

            $user = new User();
            $user->setEmail($email);
            $hashed = $this->hasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashed);

            try {
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $this->addFlash('success', 'Account created successfully! Please log in.');
                return $this->redirectToRoute('app_login');
            } catch (Exception) {
                $this->addFlash('error', 'Registration failed. Email may already be in use.');
                return $this->redirectToRoute('app_register');
            }
        }

        return $this->render('security/register.html.twig');
    }
}
