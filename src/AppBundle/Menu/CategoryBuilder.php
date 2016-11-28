<?php
/**
 * Created by PhpStorm.
 * User: xbmst
 * Date: 24.11.16
 * Time: 17.13.
 */
namespace AppBundle\Menu;

use Knp\Menu\MenuFactory;
use Knp\Menu\NodeInterface;

class CategoryBuilder implements NodeInterface
{
    public function categoryMenu()
    {
        $factory = new MenuFactory();
        $menu = $factory->createFromNode($node);
    }
}
