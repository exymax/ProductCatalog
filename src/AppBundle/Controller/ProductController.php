<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Product;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/products")
 */
class ProductController extends Controller
{
    /**
     * @Route("/get")
     */
    public function getProductsAction(Request $request)
    {
        $productsService = $this->get('products_service');
        $adminAccess = $this->isGranted(['ROLE_MOD', 'ROLE_ADMIN']) ? true : false;
        $response = $productsService->getData($request, $adminAccess);

        return new JsonResponse($response);
    }

    /**
     * @Route("/get-pages-amount")
     */
    public function getPageAmountAction(Request $request)
    {
        $rowsPerPage = $request->query->get('rows_per_page');

        return new JsonResponse($this->get('data_processor')->getPageAmount($rowsPerPage));
    }

    /**
     * @Route("/get-template")
     */
    public function getTemplateAction(Request $request)
    {
        return $this->render('grid-template.html');
    }

    /**
     * @Security("is_granted('ROLE_BROWSE_CATALOG')")
     * @Route("/{id}/show", name="admin_product_show")
     */
    public function showAction($id)
    {
        $product = $this->getDoctrine()
            ->getRepository('AppBundle:Product')
            ->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        return $this->render('admin/product/show.html.twig', array(
            'product' => $product,
        ));
    }

    /**
     * @Route("/", name="products_all")
     * @Route("/{category}")
     * @Route("/{category}/{page}")
     */
    public function showProductsAction($category, $page)
    {
        $categories = $this->get('category_service')->getFirstLevel();

        return $this->render('catalog.html.twig', [
            'user' => $this->getUser(),
            'errors' => null,
            'categories' => $categories,
        ]);
        /*if ($productsService->categoryExists($category)) {
            $products = $this->getDoctrine()->getRepository('AppBundle:Product')->findAll();
        } else {
            $message = 'Category';
        }

        return $this->render('admin/product/list.html.twig', [
            'products' => $products,
            'message' => $message,
        ]);*/
    }
}
