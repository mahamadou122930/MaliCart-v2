<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderDetails;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OrderService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createOrder(array $panierWithData, $user, $deliveryContent): Order
    {
        $order = new Order();
        $date = new \DateTimeImmutable();
        $reference = $date->format('dmY').'-'.uniqid();

        $order->setReference($reference);
        $order->setUser($user);
        $order->setCreatedAt($date);
        $order->setDelivery($deliveryContent);
        $order->setDiscount(0);
        $order->setState(0);

        foreach ($panierWithData as $product) {
            $orderDetails = new OrderDetails();
            $orderDetails->setMyOrder($order);
            $orderDetails->setProduct($product['product']->getId());
            $orderDetails->setQuantity($product['quantity']);
            $orderDetails->setColor($product['color']);
            $orderDetails->setSize($product['size']);
            $orderDetails->setPrice($product['product']->getPrice());
            $orderDetails->setTotal($product['product']->getPrice() * $product['quantity']);
            $order->addOrderDetail($orderDetails); // Associer le détail à la commande
        }

        // Ne pas enregistrer en base de données ici

        return $order;
    }
}
