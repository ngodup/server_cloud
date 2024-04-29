<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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

    #[Route('/api/products/{id}', name: 'get_product_with_comments', methods: ['GET'])]
    public function getProductWithComments(int $id, SerializerInterface $serializer): JsonResponse
    {
        $product = $this->doctrine->getRepository(Product::class)->find($id);

        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        // Fetch comments for the product
        // $comments = $product->getComments();

        // Prepare the data for the response
        $data = [
            'product' => $product,
            // 'comments' => $comments,
        ];

        // Use the 'product:detail' serialization group to control the output
        $context = ['groups' => ['product:read', 'product:detail']];

        // Serialize the data
        $serializedData = $serializer->serialize($data, 'json', $context);

        return new JsonResponse($serializedData, Response::HTTP_OK, [], true);
    }
}
