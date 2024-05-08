<?php

namespace App\Controller\Api;

use DateTime;
use DateTimeImmutable;
use App\Entity\Comment;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
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
    private EntityManagerInterface $entityManager;

    public function __construct(TokenStorageInterface $tokenStorage, ManagerRegistry $doctrine, SerializerInterface $serializer, EntityManagerInterface $entityManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->doctrine = $doctrine;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
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
        $serializedComment = $this->serializer->serialize($comment, 'json', ['groups' => ['product:detail', 'comment']]);
        return new JsonResponse($serializedComment, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/comments/user', name: 'user_comments', methods: ['GET'])]
    public function commentsByUser(Request $request, TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $token = $tokenStorage->getToken();
        if (null === $token) {
            return $this->json(['message' => 'No authentication token found.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Get the email from the query parameter
        $email = $request->query->get('email');

        if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Invalid email provided.'], Response::HTTP_BAD_REQUEST);
        }

        $comments = $entityManager->getRepository(Comment::class)->findByUserEmail($email);

        // Serialize and return the comments as JSON
        $serializedComments = $serializer->serialize($comments, 'json', ['groups' => 'product:detail', 'comment']);
        return new JsonResponse($serializedComments, Response::HTTP_OK, [], true);
    }

    #[Route('/api/comments/{commentId}', name: 'update_comment', methods: ['PATCH'])]
    public function updateComment(Request $request, int $commentId): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return $this->json(['message' => 'No authentication token found.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $comment = $this->entityManager->getRepository(Comment::class)->find($commentId);

        if (!$comment) {
            return new JsonResponse(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the user is the author of the comment before updating
        $user = $token->getUser();
        if ($user !== $comment->getAuthor()) {
            return new JsonResponse(['error' => 'User not authorized to update this comment'], Response::HTTP_UNAUTHORIZED);
        }

        // Deserialize the JSON request body into a Comment object
        $updatedCommentData = $this->serializer->deserialize($request->getContent(), Comment::class, 'json');

        // Update only the content field with the new data
        $comment->setContent($updatedCommentData->getContent());

        // Persist and flush the updated comment
        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($comment);
        $entityManager->flush();

        // Serialize and return the updated comment as JSON
        $serializedComment = $this->serializer->serialize($comment, 'json', ['groups' => 'product:detail', 'comment']);
        return new JsonResponse($serializedComment, Response::HTTP_OK, [], true);
    }


    #[Route('/api/comments/{commentId}', name: 'delete_comment', methods: ['DELETE'])]
    public function deleteComment(int $commentId): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return $this->json(['message' => 'No authentication token found.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $comment = $this->doctrine->getRepository(Comment::class)->find($commentId);

        if (!$comment) {
            return new JsonResponse(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the user is the author of the comment before deleting
        $user = $token->getUser();
        if ($user !== $comment->getAuthor()) {
            return new JsonResponse(['error' => 'User not authorized to delete this comment'], Response::HTTP_UNAUTHORIZED);
        }

        // Remove and flush the comment
        $entityManager = $this->doctrine->getManager();
        $entityManager->remove($comment);
        $entityManager->flush();

        // Return a success message
        return new JsonResponse(['message' => 'Comment deleted successfully'], Response::HTTP_OK);
    }
}
