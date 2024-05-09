<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;

class UserController extends AbstractController
{
    private TokenStorageInterface $tokenStorage;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private ManagerRegistry $doctrine;
    private $serializer;

    public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher,  ManagerRegistry $doctrine, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->doctrine = $doctrine;
        $this->tokenStorage = $tokenStorage;
        $this->serializer = $serializer;
    }

    #[Route('/register', name: 'register_user', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        if (!$email || !$password) {
            return $this->json(['status' => false, 'message' => 'Email and password are required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $userRepository = $this->entityManager->getRepository(User::class);
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

        $this->entityManager->persist($user);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        return $this->json([
            'status' => true,
            'message' => 'User registration successful',
            'userId' => $user->getId(),
            'profileId' => $profile->getId()
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/me', name: 'api_user_profile', methods: ['GET'])]
    public function userProfile(EntityManagerInterface $entityManager): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
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


    #[Route('/api/user-profiles/{id}', name: 'patch_user_profile', methods: ['PATCH'])]
    public function patchUserProfile(Request $request, int $id): JsonResponse
    {
        // Retrieve the UserProfile object from the database
        $userProfile = $this->entityManager->getRepository(UserProfile::class)->find($id);

        if (!$userProfile) {
            return $this->json(['message' => 'User profile not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Check if the user is authenticated
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return $this->json(['message' => 'No authentication token found.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $token->getUser();
        if ($user !== $userProfile->getUser()) {
            return $this->json(['message' => 'User not authorized to modify this profile.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $allowedFields = ['prenom', 'nom', 'phoneNumber', 'address', 'ville', 'codePostal']; // Define allowed fields for update

        $data = json_decode($request->getContent(), true); // Decode JSON data

        $updateData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updateData[$key] = $value;
            }
        }

        $userProfile->setPrenom($updateData['prenom'] ?? $userProfile->getPrenom()); // Use null coalescing operator for existing values
        $userProfile->setNom($updateData['nom'] ?? $userProfile->getNom());
        $userProfile->setPhoneNumber($updateData['phoneNumber'] ?? $userProfile->getPhoneNumber()); // Use null coalescing operator for existing values
        $userProfile->setAddress($updateData['address'] ?? $userProfile->getAddress());
        $userProfile->setVille($updateData['ville'] ?? $userProfile->getVille()); // Use null coalescing operator for existing values
        $userProfile->setCodePostal($updateData['codePostal'] ?? $userProfile->getCodePostal());


        $photoDeProfilFile = $request->files->get('photoDeProfil');
        if ($photoDeProfilFile) {
            if ($photoDeProfilFile) {
                $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/images/profiles';
                $fileName = uniqid() . '.' . $photoDeProfilFile->guessExtension();
                $photoDeProfilFile->move($uploadsDirectory, $fileName);
                $userProfile->setPhotoDeProfil($fileName);
            }
        } else {
            $userProfile->setPhotoDeProfil($userProfile->getPhotoDeProfil());
        }

        $this->entityManager->persist($userProfile);
        $this->entityManager->flush();

        $serializedComment = $this->serializer->serialize($userProfile, 'json', ['groups' => 'profile']);
        return new JsonResponse($serializedComment, Response::HTTP_OK, [], true);
    }
}
