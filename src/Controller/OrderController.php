<?php

namespace App\Controller;

use App\Entity\Carrier;
use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Entity\ShopProduct;
use App\Form\OrderCarrierType;
use App\Form\OrderType;
use App\Repository\ShopProductRepository;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    private $orderService;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager,OrderService $orderService)
    {
        $this->entityManager = $entityManager;
        $this->orderService = $orderService;  
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
            $delivery = $shippingselect->get('adresses')->getData();
            $delivery_content = $delivery->getFirstname().' '.$delivery->getLastname();
            $delivery_content .= '<br/>'.$delivery->getPhone();
            if ($delivery->getCompany()) {
                $delivery_content .= '<br/>'.$delivery->getCompany();
            }
    
            $delivery_content .= '<br/>'.$delivery->getAddress();
            $delivery_content .= '<br/>'.$delivery->getPostal().' '.$delivery->getCity();
            $delivery_content .= '<br/>'.$delivery->getCountry();
    
            // Créer la commande avec les détails
            $this->orderService->createOrder($panierWithData, $this->getUser(), $delivery_content);

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
