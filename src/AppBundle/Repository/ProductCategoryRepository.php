<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class ProductCategoryRepository extends EntityRepository
{
    public function createAlphabeticalQueryBuilder()
    {
        return $this->createQueryBuilder('category')
            ->orderBy('category.name', 'ASC');
    }

    public function getCategories()
    {
        return $this->createQueryBuilder('category')
            ->where('category.parent is null')
            ->leftJoin('category.children', 'children', 'WITH', 'children.parent = category')
            ->orderBy('category.name', 'DESC');
    }

    public function getOrderedCategories()
    {
        $entity = $this->getCategories();
        $arr = array();

        foreach ($entity as $cat) {
            $arr[] = $cat;

            foreach ($cat->$this->getChildren() as $child) {
                $arr[] = $child;
            }
        }
    }

    public function createOrderedByParentQueryBuilder()
    {
        return $this->createQueryBuilder('category')
            ->orderBy('category.parent', 'ASC');
    }
}
