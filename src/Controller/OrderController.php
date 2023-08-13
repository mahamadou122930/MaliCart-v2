<?php

namespace App\Controller;

use App\Form\OrderCreateAdressType;
use App\Form\OrderType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    #[Route('/order/checkout-details', name: 'checkout-detail')]
    public function index(): Response
    {

        if (!$this->getUser()->getAddresses()->getValues())
        {
             return $this->redirectToRoute('account_address_add');
        }

        $shippingselect = $this->createForm(OrderType::class, null, [
            'user'=> $this->getUser()
        ]);

        return $this->render('order/index.html.twig', [
            'form'=> $shippingselect->createView(),
        ]);
    }
}
