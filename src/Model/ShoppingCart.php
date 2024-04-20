<?php

namespace App\Model;

use Doctrine\Common\Collections\ArrayCollection;

class ShoppingCart
{
    public function __construct(
        public ArrayCollection $items = new ArrayCollection()
    ) {
    }

    public function addItem(ShoppingCartItem $item): void
    {
        $existingItem = $this->items->filter(function (ShoppingCartItem $cartItem) use ($item) {
            return $cartItem->product->getId() === $item->product->getId();
        })->first();

        if ($existingItem) {
            $existingItem->quantity += $item->quantity;
        } else {
            $this->items->add($item);
        }
    }
}
