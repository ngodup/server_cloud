<?php

namespace App\Controller\Api;

use App\Entity\Order;
use DateTime;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
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
    public function __construct(
        TokenStorageInterface $tokenStorage,
        ManagerRegistry $doctrine,
        SerializerInterface $serializer
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->doctrine = $doctrine;
        $this->serializer = $serializer;
    }

    #[Route('/api/orders', name: 'add_order', methods: ['POST'])]
    public function addOrder(Request $request): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return $this->json(['message' => 'No authentication token found.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $order = $this->serializer->deserialize($request->getContent(), Order::class, 'json');
        // dd($order);
        $dateTime = new DateTimeImmutable();
        $dateTimeMutable = DateTime::createFromImmutable($dateTime);

        $order->setCreatedAt($dateTimeMutable);

        $order->setStatus($order->getStatus());
        $order->setPaymentMethod($order->getPaymentMethod());

        $user = $token->getUser();
        if (null !== $user = $this->tokenStorage->getToken()->getUser()) {
            $order->setCustomer($user);
        } else {
            // Handle the case when the user is not authenticated (e.g., return an error or set a default value)
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $products = $order->getProducts();
        foreach ($products as $product) {
            $order->addProduct($product);
        }

        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($order);
        $entityManager->flush();

        $serializedOrder = $this->serializer->serialize($order, 'json', ['groups' => ['order:detail', 'product']]);
        return new JsonResponse($serializedOrder, Response::HTTP_CREATED, [], true);
    }
}
