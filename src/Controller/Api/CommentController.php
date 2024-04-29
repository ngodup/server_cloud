<?php

namespace App\Controller\Api;

use DateTime;
use DateTimeImmutable;
use App\Entity\Comment;
use App\Entity\Product;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class CommentController extends AbstractController
{
    private TokenStorageInterface $tokenStorage;
    private ManagerRegistry $doctrine;
    private SerializerInterface $serializer;

    public function __construct(TokenStorageInterface $tokenStorage, ManagerRegistry $doctrine, SerializerInterface $serializer)
    {
        $this->tokenStorage = $tokenStorage;
        $this->doctrine = $doctrine;
        $this->serializer = $serializer;
    }

    #[Route('/api/products/{productId}/comments', name: 'add_comment', methods: ['POST'])]
    public function addComment(Request $request, int $productId): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return $this->json(['message' => 'No authentication token found.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $product = $this->doctrine->getRepository(Product::class)->find($productId);

        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        // Deserialize the JSON request body into a Comment object
        // $commentData = json_decode($request->getContent(), true);
        // $comment = $this->serializer->deserialize(json_encode($commentData), Comment::class, 'json');
        $comment = $this->serializer->deserialize($request->getContent(), Comment::class, 'json');

        $dateTime = new DateTimeImmutable();
        $dateTimeMutable = DateTime::createFromImmutable($dateTime);

        $comment->setCreatedAt($dateTimeMutable);

        // Set the product and author properties
        $comment->setProduct($product);

        // Check if the user is authenticated before setting the author
        $user = $token->getUser();
        if (null !== $user = $this->tokenStorage->getToken()->getUser()) {
            $comment->setAuthor($user);
        } else {
            // Handle the case when the user is not authenticated (e.g., return an error or set a default value)
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // Persist and flush the new comment
        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($comment);
        $entityManager->flush();

        // Serialize and return the created comment as JSON
        $serializedComment = $this->serializer->serialize($comment, 'json', ['groups' => 'comment']);
        return new JsonResponse($serializedComment, Response::HTTP_CREATED, [], true);
    }
}