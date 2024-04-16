<?php

namespace App\Controller\Api;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository
    ) {
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
}
