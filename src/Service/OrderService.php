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

    public function createOrder(array $panierWithData, $user, $deliveryContent, $totalPrice, $shippingPrice): Order
    {
        $order = new Order();
        $date = new \DateTimeImmutable();

        $reference = $this->generateOrderReference(12); // Utiliser la fonction de génération personnalisée
        $order->setReference($reference);
        $order->setUser($user);
        $order->setCarrierPrice($shippingPrice);
        $order->setCreatedAt($date);
        $order->setDelivery($deliveryContent);
        $order->setDiscount(0);
        $order->setState(0);
        $order->setOrderTotal($totalPrice);

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
