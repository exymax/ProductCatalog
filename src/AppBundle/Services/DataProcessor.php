<?php

namespace AppBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;

class DataProcessor
{
    private $em;
    private $repository = null;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getStartID(array $params)
    {
        return ($params['page'] - 1) * $params['rows_per_page'];
    }

    public function getEndID(array $params)
    {
        $endPage = $this->getPageAmount($params['rows_per_page']);
        $curPage = $endPage - $params['page'];
        $end = $curPage * $params['rows_per_page'];
        if (isset($params['last_id'])) {
            $end = $params['last_id'] - $params['rows_per_page'];
        }

        return $end;
    }

    public function getPageAmount($rowsPerPage)
    {
        $rowsPerPage = intval($rowsPerPage);
        $rowsPerPage = $this->isValidPage($rowsPerPage) ? $rowsPerPage : 10;
        $pages = $this->em
            ->createQuery('SELECT COUNT(p) FROM AppBundle:Product p')
            ->getSingleScalarResult();
        $pages = intval($pages);

        return ceil($pages / $rowsPerPage);
    }

    public function getBasicQueryParams(array $params)
    {
        return [
            'start' => $this->getStartID($params),
        ];
    }

    public function getBasicDQL(Request $request)
    {
        $params = $this->handleParams($request);
        $p = [
            'start' => $this->getStartID($params),
        ];
        $queryParams = [];
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('d'))
            ->from('AppBundle:'.$this->repository, 'd');
        if ($this->needsComplexProcessing($params)) {
            if (!is_null($params['order_by'])) {
                $qb->orderBy('d.'.$params['sort_field'], $params['order_by']);
                if ($params['order_by'] == 'DESC') {
                    $p['start_id'] = $this->getEndID($params);
                }
            } elseif (!is_null($params['filter_field'])) {
                $qb->andWhere(
                    $qb->expr()->like('d.'.$params['filter_field'], ':like_expr')
                );
                $queryParams['like_expr'] = '%'.$params['filter_pattern'].'%';
            }
            $p['start'] = $this->getStartID($params);
        } else {
        }
        $qb->setParameters($queryParams)
            ->setFirstResult($p['start'])
            ->setMaxResults($params['rows_per_page']);

        return [
            'query' => $qb,
            'last_id' => $p['start'],
        ];
    }

    public function needsComplexProcessing($params)
    {
        return  $this->toBeFiltered($params['filter_field'], $params['filter_pattern']) ||
                $this->toBeSorted($params['sort_field'], $params['order_by']);
    }

    public function toBeSorted($field, $type)
    {
        $orders = ['ASC', 'DESC'];

        return  $this->isValidParam($field) &&
        $this->isValidParam($type) &&
        $field !== 'none' &&
        in_array(strtoupper($type), $orders);
    }

    public function toBeFiltered($field, $pattern)
    {
        $allowedFields = ['name', 'description', 'is_active'];

        return  $this->isValidParam($field) &&
        $this->isValidParam($pattern) &&
        $field !== 'none' &&
        in_array(strtolower($field), $allowedFields);
    }

    public function getLastID($start, $field, $pattern, $max)
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

    public function handleParams(Request $request)
    {
        $page = intval($request->query->get('page'));
        $category = intval($request->query->get('category'));
        $sortField = $request->query->get('sort_field');
        $orderBy = $request->query->get('order_by');
        $filterField = $request->query->get('filter_field');
        $filterPattern = $request->query->get('filter_pattern');
        $filterLastId = $request->query->get('filter_last_id');
        $rowsPerPage = $request->query->get('rows_per_page');

        $params = [
            'page' => 1,
            'sort_field' => null,
            'order_by' => null,
            'filter_field' => null,
            'filter_pattern' => null,
            'category' => null,
            'rows_per_page' => $rowsPerPage,
            'filter_last_id' => null,
            'errors' => [],
        ];

        if ($this->isValidParam($filterLastId) && intval($filterLastId) > 0) {
            $params['filter_last_id'] = intval($filterLastId);
        }

        $this->setFilterParams($params, $filterField, $filterPattern);
        $this->setSortingParams($params, $sortField, $orderBy);
        $this->setPage($params, $page);
        $this->setMax($params, $rowsPerPage);
        $this->setCategory($params, $category);

        return $params;
    }

    public function setCategory(&$result, $category)
    {
        $result['category'] = $this->isValidPage($category) ? $category : 1;
    }

    public function setRepository($repository)
    {
        $allowed = ['User', 'Product'];
        if (in_array($repository, $allowed)) {
            $this->repository = $repository;
        } else {
            throw new Exception('No repository '.$repository.' found');
        }
    }

    public function setMax(&$result, $max)
    {
        $max = intval($max);
        $result['rows_per_page'] = ($max == 0) ? 10 : $max;
    }

    public function setFilterParams(&$result, $field, $pattern)
    {
        if ($this->toBeFiltered($field, $pattern)) {
            $result['filter_field'] = $field;
            $result['filter_pattern'] = $pattern;
        } else {
            $result['filter_field'] = $result['filter_pattern'] = null;
        }
    }

    public function setSortingParams(&$result, $field, $type)
    {
        if ($this->toBeSorted($field, $type)) {
            $result['sort_field'] = $field;
            $result['order_by'] = strtoupper($type);
        } else {
            $result['sort_field'] = $result['order_by'] = null;
        }
    }

    public function setPage(&$result, $page)
    {
        $result['page'] = $this->isValidPage($page) ? $page : 1;
    }

    public function isValidParam($param)
    {
        return !is_null($param) && isset($param) && !empty($param);
    }

    public function isValidPage($page)
    {
        return filter_var($page, FILTER_VALIDATE_INT);
    }
}
