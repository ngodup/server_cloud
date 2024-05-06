<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserProfile;
//use App\Form\UserType; // Consider using a form for registration
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserController extends AbstractController
{
    private EntityManagerInterface $manager;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $manager, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher)
    {
        $this->manager = $manager;
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/register', name: 'register_user', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        if (!$email || !$password) {
            return $this->json(['status' => false, 'message' => 'Email and password are required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $userRepository = $entityManager->getRepository(User::class);
        $emailExist = $userRepository->findOneBy(['email' => $email]);

        if ($emailExist) {
            return $this->json(['status' => false, 'message' => 'This email already exists, please use a different one'], JsonResponse::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $profile = new UserProfile();
        $profile->setUser($user);



        $profileData = ['prenom', 'nom', 'dateDeNaissance', 'phoneNumber', 'address', 'ville', 'codePostal', 'photoDeProfil'];


        foreach ($profileData as $field) {
            if ($request->request->has($field)) {
                $method = 'set' . ucfirst($field);
                if ($field === 'dateDeNaissance') {
                    $value = \DateTime::createFromFormat('Y-m-d', $request->request->get($field));
                    if ($value !== false) {
                        $profile->$method($value);
                    }
                } elseif ($field === 'codePostal') {
                    $value = intval($request->request->get($field));
                    if ($value !== 0) {
                        $profile->$method($value);
                    }
                } else {
                    $profile->$method($request->request->get($field));
                }
            }
        }

        //Check if Profile file is exist of not
        $photoDeProfilFile = $request->files->get('photoDeProfil');
        if ($photoDeProfilFile) {
            $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/images/profiles';
            $fileName = uniqid() . '.' . $photoDeProfilFile->guessExtension();
            $photoDeProfilFile->move($uploadsDirectory, $fileName);
            $profile->setPhotoDeProfil($fileName);
        }

        $entityManager->persist($user);
        $entityManager->persist($profile);
        $entityManager->flush();

        return $this->json([
            'status' => true,
            'message' => 'User registration successful',
            'userId' => $user->getId(),
            'profileId' => $profile->getId()
        ], JsonResponse::HTTP_CREATED);
    }



    #[Route('/api/me', name: 'api_user_profile', methods: ['GET'])]
    public function userProfile(TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager): JsonResponse
    {
        $token = $tokenStorage->getToken();
        if (null === $token) {
            return $this->json(['message' => 'No authentication token found.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $token->getUser();

        // Check if the user is an instance of your User entity
        if (!$user instanceof User) {  // Use your User entity class here
            return $this->json(['message' => 'Token does not contain a valid user.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Fetch the UserProfile entity related to the User entity
        $userProfile = $entityManager->getRepository(UserProfile::class)->findOneBy(['user' => $user]);

        // Check if the UserProfile entity exists
        if (!$userProfile instanceof UserProfile) {
            return $this->json(['message' => 'User profile not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Now it's safe to call User-specific and UserProfile-specific methods
        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'userProfile' => [
                    'id' => $userProfile->getId(),
                    'prenom' => $userProfile->getPrenom(),
                    'nom' => $userProfile->getNom(),
                    'phoneNumber' => $userProfile->getPhoneNumber(),
                    'address' => $userProfile->getAddress(),
                    'dateDeNaissance' => $userProfile->getDateDeNaissance(),
                    'phoneNumber' => $userProfile->getPhoneNumber(),
                    'address' => $userProfile->getAddress(),
                    'ville' => $userProfile->getVille(),
                    'codePostal' => $userProfile->getCodePostal(),
                    'photoDeProfil' => $userProfile->getPhotoDeProfil()
                ]
            ]
        ]);
    }
}
