<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_product')]
class OrderProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id')]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'orderProducts')]
    #[ORM\JoinColumn(name: 'order_reference_id', referencedColumnName: 'id')]
    private ?Order $orderReference = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getOrderReference(): ?Order
    {
        return $this->orderReference;
    }

    public function setOrderReference(?Order $orderReference): static
    {
        $this->orderReference = $orderReference;

        return $this;
    }
}
