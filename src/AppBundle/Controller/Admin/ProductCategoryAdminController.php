<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\ProductCategory;
use AppBundle\Form\ProductCategoryType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin")
 */
class ProductCategoryAdminController extends Controller
{
    /**
     * @Route("/category", name="admin_category_list")
     */
    public function indexAction()
    {
        $categories = $this->getDoctrine()
            ->getRepository('AppBundle:ProductCategory')
            ->findAll();

        return $this->render('admin/category/list.html.twig', array(
            'categories' => $categories,
        ));
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_CATEGORY')")
     * @Route("/category/new", name="admin_category_new")
     */
    public function newAction(Request $request)
    {
        $form = $this->createForm(ProductCategoryType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $category = $form->getData();

            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();

            $this->addFlash('success', 'Category added.');

            return $this->redirectToRoute('admin_category_list');
        }

        return $this->render('admin/category/new.html.twig', [
            'categoryForm' => $form->createView(),
        ]);
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_PRODUCT')")
     * @Route("/category/{id}/edit", name="admin_category_edit")
     */
    public function editAction(Request $request, ProductCategory $category)
    {
        $form = $this->createForm(ProductCategoryType::class, $category);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $product = $form->getData();

            $em = $this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Category updated.');

            return $this->redirectToRoute('admin_category_list');
        }

        return $this->render('admin/category/edit.html.twig', [
            'categoryForm' => $form->createView(),
        ]);
    }
}
