<?php

namespace AppBundle\Services;

use AppBundle\Entity\ProductCategory;
use Doctrine\ORM\EntityManager;

class ProductsService
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function categoryExists($categoryName)
    {
        $category = $this->em->getRepository('AppBundle:ProductCategory')->findOneBy(['name' => $categoryName]);
        if ($category instanceof ProductCategory) {
            return true;
        } else {
            return false;
        }
    }
}
