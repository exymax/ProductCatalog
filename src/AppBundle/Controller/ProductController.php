<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class ProductController extends Controller
{
    /**
     * @Route("/products/{category}/{page}", name="user_products_list")
     */
    public function showProducts($category, $page, Request $request)
    {
        $products = $message = null;
        $productsService = $this->get('products_service');
        if ($productsService->categoryExists($category)) {
            $products = $this->getDoctrine()->getRepository('AppBundle:Product')->findAll();
        } else {
            $message = 'Category';
        }

        return $this->render('admin/product/list.html.twig', [
            'products' => $products,
            'message' => $message,
        ]);
    }
}
