<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\Product;
use AppBundle\Form\ProductType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin")
 */
class ProductAdminController extends Controller
{
    /**
     * @Route("/product", name="admin_product_list")
     */
    public function indexAction()
    {
        $products = $this->getDoctrine()
            ->getRepository('AppBundle:Product')
            ->findAll();

        return $this->render('admin/product/list.html.twig', array(
            'products' => $products,
        ));
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_PRODUCT')")
     * @Route("/product/new", name="admin_product_new")
     */
    public function newAction(Request $request)
    {
        $form = $this->createForm(ProductType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $product = $form->getData();
            $product->setCreatedAt(date("Y-m-d"));
            $product->setUpdatedAt(date("Y-m-d"));

            $em = $this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product added!');

            return $this->redirectToRoute('admin_product_list');
        }

        return $this->render('admin/product/new.html.twig', [
            'productForm' => $form->createView(),
        ]);
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_PRODUCT')")
     * @Route("/product/{id}/edit", name="admin_product_edit")
     */
    public function editAction(Request $request, Product $product)
    {
        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $product = $form->getData();

            $em = $this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product updated.');

            return $this->redirectToRoute('admin_product_list');
        }

        return $this->render('admin/product/edit.html.twig', [
            'productForm' => $form->createView(),
        ]);
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_PRODUCT')")
     * @Route("/product/{id}/show", name="admin_product_show")
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
     * @Security("is_granted('ROLE_MANAGE_PRODUCT')")
     * @Route("/product/{id}/delete", name="admin_product_delete")
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $product = $this->getDoctrine()->getRepository('AppBundle:Product')
            ->find($id);

        $em->remove($product);
        $em->flush();

        $this->addFlash('success', 'Product deleted.');

        return $this->redirectToRoute('admin_product_list');
    }
}
