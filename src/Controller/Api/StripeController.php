<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Model\ShoppingCart;
use App\Service\StripeService;
use App\Model\ShoppingCartItem;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface; // Import EntityManagerInterface

class StripeController extends AbstractController
{
    private $stripeService;
    private $serializer;
    private $entityManager; // Inject EntityManagerInterface

    public function __construct(StripeService $stripeService, SerializerInterface $serializer, EntityManagerInterface $entityManager)
    {
        $this->stripeService = $stripeService;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws ApiErrorException
     */
    #[Route('/api/stripe/checkout-sessions', name: 'app_api_stripe_index', methods: ['POST'])]
    public function index(Request $request): JsonResponse
    {
        $cartData = json_decode($request->getContent(), true);

        if (!isset($cartData['items'])) {
            return new JsonResponse(['error' => 'Invalid cart data'], 400);
        }

        $shoppingCart = new ShoppingCart();
        foreach ($cartData['items'] as $item) {
            $productId = isset($item['productId']) ? $item['productId'] : null;
            $quantity = isset($item['quantity']) ? $item['quantity'] : null;

            if ($productId === null || $quantity === null) {
                return new JsonResponse(['error' => 'Invalid cart item data'], 400);
            }

            $product = $this->entityManager->getRepository(Product::class)->find($productId);

            if (!$product) {
                return new JsonResponse(['error' => 'Product not found'], 404);
            }

            // Create ShoppingCartItem object
            $shoppingCartItem = new ShoppingCartItem($product, $quantity);

            $shoppingCart->addItem($shoppingCartItem);
        }


        try {
            $checkoutSession = $this->stripeService->createCheckoutSession($shoppingCart);

            return new JsonResponse(['url' => $checkoutSession->url]);
        } catch (ApiErrorException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
