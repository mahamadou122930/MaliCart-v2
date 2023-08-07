<?php

namespace App\Controller;

use App\Repository\ShopProductRepository;
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

        return $this->render('cart/index.html.twig', [
            'items'=> $panierWithData,
            'totalPrice' => $totalPrice,
        ]);
    }



    #[Route('/panier/add/{id}', name: 'add_to_cart')]
    public function add($id, SessionInterface $session, Request $request)
    {
        $panier = $session->get('panier', []);
    
        $color = $request->request->get('color');
        $size = $request->request->get('size');
    
        if (!empty($panier[$id]) && ($panier[$id]['color'] === $color && $panier[$id]['size'] === $size)) {
            // Produit déjà existant avec la même couleur et taille, incrémenter la quantité
            $panier[$id]['quantity']++;
        } else {
            // Produit inexistant ou produit existant avec une couleur ou taille différente
            $newId = $id . '_' . $color . '_' . $size; // Génère un nouvel ID unique pour la nouvelle ligne
            if (!empty($panier[$newId])) {
                // Produit déjà existant avec la même couleur et taille, incrémenter la quantité
                $panier[$newId]['quantity']++;
            } else {
                // Nouveau produit, ajouter une nouvelle ligne
                $panier[$newId] = [
                    'quantity' => 1,
                    'color' => $color,
                    'size' => $size,
                ];
            }
        }    
        $session->set('panier', $panier);

        
        dd($panier);
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