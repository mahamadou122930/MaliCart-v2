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
            $date = new \DateTimeImmutable();
            $delivery = $shippingselect->get('adresses')->getData();
            $delivery_content = $delivery->getFirstname().' '.$delivery->getLastname();
            $delivery_content .= '<br/>'.$delivery->getPhone();
            if ($delivery->getCompany()) {
                $delivery_content .= '<br/>'.$delivery->getCompany();
            }
    
            $delivery_content .= '<br/>'.$delivery->getAddress();
            $delivery_content .= '<br/>'.$delivery->getPostal().' '.$delivery->getCity();
            $delivery_content .= '<br/>'.$delivery->getCountry();
    
            // Créer la commande avec les détails et les stocker dans la session
            $order = $this->orderService->createOrder($panierWithData, $this->getUser(), $delivery_content);
            $session->set('order', $order);
            // Supprimer l'ancienne clé 'panier' de la session
            $session->remove('panier');

            return $this->redirectToRoute('checkout-shipping');
        }


        return $this->render('order/index.html.twig', [
            'form'=> $shippingselect->createView(),
            'items'=> $panierWithData,
            'total'=> $totalPrice,
            'discount'=> $discount,
            'shipping'=> $shippingPrice,
        ]);
    }

    #[Route('/order/checkout-shipping', name: 'checkout-shipping')]
    public function methodshipping(SessionInterface $session, Request $request): Response
    {
        // Récupérer l'objet Order depuis la session
        $order = $session->get('order');

        $carriers = $this->entityManager->getRepository(Carrier::class)->findAll();

        // Crée le formulaire en utilisant le OrderCarrierType
        $form = $this->createForm(OrderCarrierType::class);
        $form->handleRequest($request);

        if (!$order) {
            // Rediriger vers une autre page (par exemple, la page du panier) si l'objet Order n'est pas trouvé
            return $this->redirectToRoute('cart');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $carrier = $form->get('carrier')->getData();
             // Mettre à jour les informations du transporteur dans l'objet Order
            $order->setCarrierName($carrier->getName());
            $order->setCarrierPrice($carrier->getPrice());

            // Enregistrez les modifications dans la session
            $session->set('order', $order);
        }

        
        // Récupérer les produits associés à chaque OrderDetail
        $productRepository = $this->entityManager->getRepository(ShopProduct::class);
        $orderProducts = [];
        foreach ($order->getOrderDetails() as $orderDetail) {
            $productId = $orderDetail->getProduct();
            $product = $productRepository->find($productId);
            $orderProducts[] = $product;
        }
        dd($orderProducts);

        return $this->render('order/shipping_method.html.twig', [
            'order' => $order,
            'carriers' => $carriers,
            'form' => $form->createView(),
            'orderProducts' => $orderProducts, // Passer les produits à la vue
        ]);
    }

 
}
