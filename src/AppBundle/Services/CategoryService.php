<?php

namespace AppBundle\Services;

use Doctrine\ORM\EntityManager;

class CategoryService
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getFirstLevel()
    {
        return $this->em->getRepository('AppBundle:ProductCategory')->findBy(['parent' => null]);
    }
}
