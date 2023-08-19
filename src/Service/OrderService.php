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

    private function generateOrderReference($length = 12)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $reference = '';
    
        for ($i = 0; $i < $length; $i++) {
            $reference .= $characters[rand(0, strlen($characters) - 1)];
        }
    
        return $reference;
    }

    public function createOrder(array $panierWithData, $user, $delivery_content): void
    {
        $order = new Order();
        $date = new \DateTimeImmutable();
        $reference = $date->format('dmY').'-'.uniqid();

        $order->setReference($reference);
        $order->setUser($user);
        $order->setCreatedAt($date);
        $order->setDelivery($delivery_content);
        $order->setDiscount(0);
        $order->setState(0);
        

        $this->entityManager->persist($order);

        foreach ($panierWithData as $product) {
            $orderDetails = new OrderDetails();
            $orderDetails->setMyOrder($order);
            $orderDetails->setProduct($product['product']->getId());
            $orderDetails->setQuantity($product['quantity']);
            $orderDetails->setColor($product['color']);
            $orderDetails->setSize($product['size']);
            $orderDetails->setPrice($product['product']->getPrice());
            $orderDetails->setTotal($product['product']->getPrice() * $product['quantity']);

            $this->entityManager->persist($orderDetails);
        }

        $this->entityManager->flush();
    }

}
