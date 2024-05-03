<?php

namespace App\Controller\Api;

use App\Entity\User;
use PhpParser\Comment;
use App\Entity\Product;
use Doctrine\ORM\Query;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    private ManagerRegistry $doctrine;
    private SerializerInterface $serializer;
    public function __construct(
        private readonly ProductRepository $productRepository,
        ManagerRegistry $doctrine,
        SerializerInterface $serializer
    ) {
        $this->doctrine = $doctrine;
        $this->serializer = $serializer;
    }

    /**
     * Retrieves a list of all products.
     * 
     * @Route("/api/products", name: "api_products", methods: ["GET"])
     * 
     * @param NormalizerInterface $normalizer Used to serialize the Product objects.
     * @return Response JSON response containing all products.
     */
    #[Route('/api/products', name: 'api_products', methods: ['GET'])]
    public function getProducts(NormalizerInterface $normalizer): Response
    {
        $products = $this->productRepository->findAll();

        try {
            $serializedProducts = $normalizer->normalize($products, 'json', ['groups' => 'product:read']);
        } catch (ExceptionInterface $e) {
            // Log the error or handle it as required
            return $this->json(['error' => 'An error occurred while serializing product data.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        // return $this->json($serializedProducts);
        return $this->json($serializedProducts, Response::HTTP_OK);
    }

    // #[Route('/api/products/{id}', name: 'get_product_with_comments', methods: ['GET'])]
    // public function getProductWithComments(int $id, EntityManagerInterface $entityManager): JsonResponse
    // {
    //     $product = $this->doctrine->getRepository(Product::class)->find($id);

    //     if (!$product) {
    //         return new JsonResponse(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
    //     }

    //     $comments = $product->getComments();    // Fetch comments for the product
    //     // Prepare the data for the response
    //     $productData = [
    //         'id' => $product->getId(),
    //         'name' => $product->getName(),
    //         'description' => $product->getDescription(),
    //         'price' => $product->getPrice(), // Add other product properties as needed
    //     ];

    //     $data = [
    //         'product' => $productData,
    //         'comments' => array_map(function ($comment) use ($entityManager) {
    //             $author = $comment->getAuthor();

    //             if ($author instanceof User) {
    //                 $entityManager->refresh($author);  // Fetch the UserProfile object associated with the user (author)
    //                 $userProfile = $author->getUserProfile();
    //                 // Include the user's prenom in the response
    //                 $prenom = $userProfile ? $userProfile->getPrenom() : null;
    //             } else {
    //                 $prenom = null;
    //             }

    //             return [
    //                 'id' => $comment->getId(),
    //                 'content' => $comment->getContent(),
    //                 'createdAt' => $comment->getCreatedAt(), // Include createdAt if needed
    //                 'author' => $author ? $author->getEmail() : null, // Handle potential null author and use getEmail() instead of getUsername()
    //                 'prenom' => $prenom, // Include the user's prenom
    //             ];
    //         }, $comments->toArray()),
    //     ];

    //     return new JsonResponse($data, Response::HTTP_OK);
    // }
    #[Route('/api/products/{id}', name: 'get_product_with_comments', methods: ['GET'])]
    public function getProductWithComments(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $product = $this->doctrine->getRepository(Product::class)->find($id);

        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        // Use a more concise JOIN approach with selection aliases
        $qb = $entityManager->createQueryBuilder();
        $qb->select('p', 'c', 'u', 'up')
            ->from(Product::class, 'p')
            ->innerJoin('p.comments', 'c')
            ->leftJoin('c.author', 'u')
            ->leftJoin('u.userProfile', 'up')
            ->where('p.id = :product')
            ->setParameter('product', $product);

        $data = $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);   // Get results as an array directly

        if (!$data) {
            $productData = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
                'imageName' => $product->getImageName(),
                '$category' => $product->getCategory(),
                'repas' => $product->getRepas(),
                'repasType' => $product->getRepasType(),
            ];
            return new JsonResponse([
                'product' => $productData,
                'comments' => [],
            ], Response::HTTP_OK);
        }

        $productData = [
            'id' => $data[0]['id'], // Assuming the first element is the product
            'name' => $data[0]['name'],
            'description' => $data[0]['description'],
            'price' => $data[0]['price'],
        ];

        $comments = [];
        // Loop through the comments nested within the first element of the $data array
        foreach ($data[0]['comments'] as $comment) {
            $comments[] = [
                'id' => $comment['id'],
                'content' => $comment['content'],
                'createdAt' => $comment['createdAt']->format('Y-m-d H:i:s'), // Assuming createdAt exists
                'author' => $comment['author'] ? $comment['author']['userProfile']['nom'] : null,
                'prenom' => $comment['author']['userProfile'] ? $comment['author']['userProfile']['prenom'] : null,
            ];
        }

        return new JsonResponse([
            'product' => $productData,
            'comments' => $comments,
        ], Response::HTTP_OK);
    }
}
