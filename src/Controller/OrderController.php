<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Form\OrderType;
use App\Repository\ShopProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;  
    }

    #[Route('/order/checkout-details', name: 'checkout-detail')]
    public function index(SessionInterface $session, Request $request, ShopProductRepository $shopProductRepository): Response
    {
        $panier = $session->get('panier', []);

        $panierWithData = [];
        $totalPrice = 0;
        $shippingPrice = 0;
        $discount = 0;
        
        foreach($panier as $id => $item) {
            $color = $item['color'];
            $size = $item['size'];
            $product = $shopProductRepository->find($id);
            $quantity = $item['quantity'];
            $subtotal = $product->getPrice() * $quantity;
            $totalPrice += $subtotal;
            $panierWithData [] = [
                'product' => $product,
                'quantity' => $quantity,
                'color' => $color,
                'size' => $size,
                'subtotal' => $subtotal,
            ];
        }

        if (!$this->getUser()->getAddresses()->getValues())
        {
             return $this->redirectToRoute('account_address_add');
        }

        $shippingselect = $this->createForm(OrderType::class, null, [
            'user'=> $this->getUser()
        ]);
        $shippingselect->handleRequest($request);

        if ($shippingselect->isSubmitted() && $shippingselect->isValid()) {
            $date = new \DateTimeImmutable();
            $delivery = $shippingselect->get('adresses')->getData();
            $delivery_content = $delivery->getFirstname().' '.$delivery->getLastname();
            $delivery_content .= '<br/>'.$delivery->getPhone();
            if ($delivery->getCompany())
            {
                $delivery_content .= '<br/>'.$delivery->getCompany();
            }
            
            $delivery_content .= '<br/>'.$delivery->getAddress();
            $delivery_content .= '<br/>'.$delivery->getPostal().' '.$delivery->getCity();
            $delivery_content .= '<br/>'.$delivery->getCountry();
            // Créer une instance de l'entité Order avec les données du formulaire et du panier
            $order = new Order();
            $reference = $date->format('dmY').'-'.uniqid();
            $order->setReference($reference);
            $order->setUser($this->getUser());
            $order->setCreatedAt($date);
            $order->setCarrierPrice(0);
            $order->setDelivery($delivery_content);
            $order->setDiscount(0);
            $order->setState(0);
            
            // Création de l'OrderDetails
            foreach ($panierWithData as $product) {
                $orderDetails = new OrderDetails();
                $orderDetails->setMyOrder($order);
                $orderDetails->setProduct($product['product']->getName());
                $orderDetails->setQuantity($product['quantity']);
                $orderDetails->setColor($product['color']);
                $orderDetails->setSize($product['size']);
                // Formater le prix et le total avec number_format
                $orderDetails->setPrice($product['product']->getPrice());
                $orderDetails->setTotal($product['product']->getPrice() * $product['quantity']);
                dd($orderDetails);
                $this->entityManager->persist($orderDetails);
              }
             // Stockez l'instance Order dans la session pour la remplir plus tard
             $session->set('incomplete_order', $order);

        }

        return $this->render('order/index.html.twig', [
            'form'=> $shippingselect->createView(),
            'items'=> $panierWithData,
            'total'=> $totalPrice,
            'discount'=> $discount,
            'shipping'=> $shippingPrice,
        ]);
    }

 
}
