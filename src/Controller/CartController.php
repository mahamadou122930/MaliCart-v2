<?php

namespace App\Controller;

use App\Entity\ShopProductColor;
use App\Repository\ShopProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    #[Route('/panier', name: 'cart')]
    public function index(SessionInterface $session, ShopProductRepository $shopProductRepository): Response
    {
        $panier = $session->get('panier', []);

        $panierWithData = [];
        $totalPrice = 0;
        
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

        dd($panierWithData);
        
        return $this->render('cart/index.html.twig', [
            'items'=> $panierWithData,
            'totalPrice' => $totalPrice,
        ]);
    }

    #[Route('/panier/add/{id}', name: 'add_to_cart')]
    public function add($id, SessionInterface $session, Request $request, EntityManagerInterface $entityManager)
    {
        $panier = $session->get('panier', []);
    
        $colorId = $request->request->get('color'); // Récupération de l'identifiant de couleur
        $color =$entityManager->getRepository(ShopProductColor::class)->find($colorId); // Remplacer 'Color' par le nom de votre entité Color

        $size = $request->request->get('size');
        $quantity = $request->request->getInt('quantity');

        // Vérifier si le produit avec cet identifiant existe déjà dans le panier
        $productId = $id . '_' . $color->getName() . '_' . $size;

        if (!empty($panier[$productId])) {
            // Produit déjà existant avec la même couleur et taille, incrémenter la quantité
            $panier[$productId]['quantity'] += $quantity;
        } else {
            // Nouveau produit, ajouter une nouvelle ligne avec l'id du produit et le nom de la couleur
            $panier[$productId] = [
                'product_id' => $id,
                'color' => $color->getName(), // Récupérer le nom de la couleur
                'size' => $size,
                'quantity' => $quantity,
            ];
        }


        $session->set('panier', $panier);
        
        return $this->redirectToRoute('cart');
    }
    

    #[Route('/panier/remove/{id}', name: 'remove_to_cart')]
    public function remove($id, SessionInterface $session, Request $request)
    {
        $panier = $session->get('panier', []);
    
        // Vérifier si l'élément du panier existe avant de le supprimer
        if (isset($panier[$id])) {
            unset($panier[$id]);
            $session->set('panier', $panier);
        }
    
        return $this->redirectToRoute('cart');
    }

}