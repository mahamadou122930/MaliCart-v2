<?php

namespace App\Controller;

use App\Classe\Search;
use App\Entity\ShopProduct;
use App\Form\SearchType;
use App\Repository\ShopProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShopProductController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    #[Route('/shop/products', name: 'shop_products')]
    public function index(Request $request, ShopProductRepository $repository, PaginatorInterface $paginator): Response
    {
        $shopproducts = $this->entityManager->getRepository(ShopProduct::class)->findAll();

        $pagination = $paginator->paginate(
            $repository->paginationQuery(),
            $request->query->get('page', 1),
            30
        );

        $search = new Search();
        $form = $this->createForm(SearchType::class, $search);
        $form->handleRequest($request);
        [$min, $max] = $repository->findMinMax($search);
        if ($form->isSubmitted() && $form->isValid()) {
            $shopproducts = $this->entityManager->getRepository(ShopProduct::class)->findWithSearch($search);
        }

        return $this->render('shop_product/index.html.twig', [
            'shopproducts'=> $shopproducts,
            'form'=> $form->createView(),
            'pagination'=> $pagination,
            'min'=> $min,
            'max'=> $max,
        ]);
    }


    #[Route('/shop/product/{slug}', name: 'shop_product')]
    public function show($slug): Response
    {
        $shopproduct = $this->entityManager->getRepository(ShopProduct::class)->findOneBySlug($slug);

        if(!$shopproduct) {
            return $this->redirectToRoute('shop_products');
        }

        return $this->render('shop_product/show.html.twig', [
            'shopproduct'=> $shopproduct,
        ]);
    }
}
