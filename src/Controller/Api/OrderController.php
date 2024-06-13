<?php

namespace App\Controller\Api;

use DateTime;
use App\Entity\Order;
use DateTimeImmutable;
use App\Entity\Product;
use App\Entity\OrderProduct;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class OrderController extends AbstractController
{

    private TokenStorageInterface $tokenStorage;
    private ManagerRegistry $doctrine;
    private SerializerInterface $serializer;
    private EntityManagerInterface $entityManager;
    private $orderRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        ManagerRegistry $doctrine,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        OrderRepository $orderRepository
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->doctrine = $doctrine;
        $this->serializer = $serializer;
        $this->orderRepository = $orderRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/orders', name: 'add_order', methods: ['POST'])]
    public function addOrder(Request $request): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return $this->json(['message' => 'No authentication token found.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $jsonData = $request->getContent();
        // $order = $this->serializer->deserialize($request->getContent(), Order::class, 'json');
        $order = $this->serializer->deserialize($jsonData, Order::class, 'json');


        $dateTime = new DateTimeImmutable();
        $dateTimeMutable = DateTime::createFromImmutable($dateTime);

        $order->setCreatedAt($dateTimeMutable);

        $order->setStatus($order->getStatus());
        $order->setPaymentMethod($order->getPaymentMethod());

        $user = $token->getUser();
        if (null !== $user = $this->tokenStorage->getToken()->getUser()) {
            $order->setCustomer($user);
        } else {
            // Handle the case when the user is not authenticated 
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // $productIds = $request->query->get('productIds');
        $requestData = json_decode($request->getContent(), true);
        $products = $requestData['products'];


        foreach ($products as $product) {
            $productEntity = $this->doctrine->getRepository(Product::class)->findOneBy(['id' => $product['id']]);

            if (!$product) {
                // Handle the case when the product is not found
                continue;
            }

            $orderProduct = new OrderProduct();
            $orderProduct->setProduct($productEntity);
            $orderProduct->setOrderReference($order);
            $orderProduct->setQuantity($product['quantity']);
            $order->addOrderProduct($orderProduct);
        }

        $this->entityManager = $this->doctrine->getManager();
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $serializedOrder = $this->serializer->serialize($order, 'json', ['groups' => ['order:detail', 'product']]);
        return new JsonResponse($serializedOrder, Response::HTTP_CREATED, [], true);
    }



    #[Route('/api/orders/user', name: 'user_orders', methods: ['GET'])]
    public function getUserOrders(Request $request): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return $this->json(['message' => 'No authentication token found.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $token->getUser();

        if (null === $user) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $orders = $this->orderRepository->findBy(['customer' => $user]);

        $serializedOrders = [];

        foreach ($orders as $order) {
            $serializedOrder = [
                'id' => $order->getId(),
                'createdAt' => $order->getCreatedAt()->format('Y-m-d H:i:s'),
                'status' => $order->getStatus(),
                'totalPrice' => $order->getTotalPrice(),
                'paymentMethod' => $order->getPaymentMethod(),
                'products' => []
            ];

            foreach ($order->getOrderProducts() as $orderProduct) {
                $product = $orderProduct->getProduct();
                $serializedOrder['products'][] = [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'imageName' => $product->getImageName(),
                    'price' => $product->getPrice(),
                    'quantity' => $orderProduct->getQuantity(),
                ];
            }

            $serializedOrders[] = $serializedOrder;
        }

        return new JsonResponse($serializedOrders, Response::HTTP_OK);
    }
}
