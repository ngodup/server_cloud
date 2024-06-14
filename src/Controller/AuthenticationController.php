<?php

namespace App\Controller;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AuthenticationController extends AbstractController
{
    private JWTTokenManagerInterface $jwtManager;
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(JWTTokenManagerInterface $jwtManager, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->jwtManager = $jwtManager;
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }


    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            return new JsonResponse(['status' => false, 'message' => 'Email and password are required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $userRepository = $this->entityManager->getRepository(User::class);
            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
                return new JsonResponse(['status' => false, 'message' => 'Invalid email or password'], JsonResponse::HTTP_UNAUTHORIZED);
            }

            $token = $this->jwtManager->create($user);
            $request->getSession()->set("JWT", $token); //store the jwt in the session in symfony
            return new JsonResponse(['status' => true, 'token' => $token], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->error('Login failed: ' . $e->getMessage());
            return new JsonResponse(['status' => false, 'message' => 'Server error: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/password_reset_link', name: 'password_reset_link', methods: ['POST'])]
    public function passwordReset(Request $request, MailerInterface $mailer, LoggerInterface $logger)
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['status' => 'error', 'message' => 'Email is required and must be valid']);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['status' => 'error', 'message' => 'User not found']);
        }

        $token = $this->jwtManager->create($user);

        // send an email to the user with the token
        $email = (new Email())
            ->from('contact@tibetkart.com')
            ->to($user->getEmail())
            ->subject('Time for Symfony Mailer!')
            ->html($this->renderView('auth/password_reset.html.twig', [
                'token' => $token,
                'app_url' => 'http://127.0.0.1:8000/',
            ]));

        try {
            $mailer->send($email);
            return new JsonResponse(['status' => 'success', 'message' => 'Un courriel a été envoyé à ' . $user->getEmail() . ' avec des instructions pour réinitialiser votre mot de passe.']);
        } catch (TransportExceptionInterface $e) {
            $logger->error('Error sending password reset email: ' . $e->getMessage());
            return new JsonResponse(['status' => 'error', 'message' => 'Lenvoi de le-mail de réinitialisation du mot de passe a posé un problème. Veuillez réessayer plus tard.']);
        }
    }

    //     #[Route('/api/reset_password', name: 'reset_password_link', methods: ['POST'])]
    //     public function resetPassword(Request $request, UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    //     {
    //         $data = json_decode($request->getContent(), true);

    //         $newPassword = $data['newPassword'] ?? null;
    //         $token = $data['token'] ?? null;

    //         if (!$newPassword || !$token) {
    //             return new JsonResponse(['status' => 'error', 'message' => 'New password and token are required']);
    //         }

    //         $resetPasswordToken = $this->entityManager->getRepository(ResetPasswordToken::class)->findOneBy(['token' => $token]);

    //         if (!$resetPasswordToken) {
    //             return new JsonResponse(['status' => 'error', 'message' => 'Invalid or expired token']);
    //         }

    //         $user = $resetPasswordToken->getUser();

    //         $user->setPassword($userPasswordHasher->hashPassword($user, $newPassword));

    //         $this->entityManager->flush();

    //         return new JsonResponse(['status' => 'success', 'message' => 'Password has been successfully reset']);
    //     }
}
