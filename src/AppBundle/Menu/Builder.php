<?php

namespace AppBundle\Menu;


use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('Home');

        $menu->addChild('Home', ['route' => 'homepage']);

        $menu->addChild('Products', [
            'route' => 'products_all',
            ]);

        $menu->addChild('Categories', [
            'route' => 'admin_category_list'
        ]);

        $menu->addChild('Users', [
            'route' => 'admin_user_list',
        ]);
        return $menu;
    }

    public function modMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('Home');

        $menu->addChild('Home', ['route' => 'homepage']);

        $menu->addChild('Products', [
            'route' => 'products_all',
        ]);

        $menu['Products']->addChild('Add product', [
            'route' => 'admin_product_new',
        ]);

        $menu->addChild('Categories', [
            'route' => 'admin_category_list'
        ]);

        return $menu;
    }

    public function userMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('Home');

        $menu->addChild('Home', ['route' => 'homepage']);

        $em = $this->container->get('doctrine')->getManager();
        $category = $em->getRepository('AppBundle:ProductCategory')->findAll();

        $menu->addChild('Products', [
            'route' => 'products_all',
        ]);

        return $menu;
    }
}