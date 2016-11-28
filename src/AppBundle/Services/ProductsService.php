<?php

namespace AppBundle\Services;

use AppBundle\Entity\ProductCategory;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

class ProductsService
{
    private $em;
    private $columnNames;
    private $processor;

    public function __construct(EntityManager $em, DataProcessor $processor)
    {
        $this->em = $em;
        $this->processor = $processor;
        $this->processor->setRepository('Product');
        $this->columnNames = array_values(
            array_diff(
                $this->em->getClassMetadata('AppBundle:Product')->getFieldNames(), ['isActive']
            )
        );
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

    public function getFullData($id)
    {
        if ($this->processor->isValidPage($id)) {
            return $this->em->getRepository('AppBundle:Product')->find($id);
        } else {
            return [];
        }
    }

    public function getData(Request $request, $adminAccess = false)
    {
        $params = $this->processor->handleParams($request);
        //$queryParams = $this->processor->getBasicQueryParams($params);
        $serviceResponse = $this->processor->getBasicDQL($request);
        $querySchema = $serviceResponse['query'];
        $lastID = $serviceResponse['last_id'];
        $this->setCategory($params, $params['category']);
        if ($params['category']) {
            $querySchema->andWhere(
               $querySchema->expr()->eq('d.category', $params['category'])
           );
        }
        if (!$adminAccess) {
            $querySchema->andWhere(
                $querySchema->expr()->eq('d.isActive', '1')
            );
        }
        $query = $querySchema->getQuery();
        $products = $query->getArrayResult();

        return [
            'admin_access' => $adminAccess,
            'prefix' => 'product',
            'last_id' => $lastID,
            'data' => $products,
            'errors' => $params['errors'],
            'headers' => $this->columnNames,
        ];
    }

    public function getErrors($products)
    {
        if ($products && count($products) > 0) {
            return null;
        } else {
            return 'Products with given params not found';
        }
    }

    public function setCategory($result, $category)
    {
        $result['category'] = $this->categoryExists($category) && $this->processor->isValidParam($category) ? $category : null;
    }

    public function getFilterLastID($start, $field, $pattern, $max)
    {
        $last = $this->em->createQuery(
            'SELECT p.id FROM AppBundle:Product p WHERE p.id > :startID AND p.'.$field.' LIKE :pattern'
        )
            ->setMaxResults($max)
            ->setParameters([
                'startID' => $start,
                'pattern' => $pattern,
            ])
            ->getArrayResult();

        return intval(array_pop($last)['id']);
    }

    public function getSortingLastID()
    {
    }
}
