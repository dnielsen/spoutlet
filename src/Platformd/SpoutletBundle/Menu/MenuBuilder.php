<?php

namespace Platformd\SpoutletBundle\Menu;

use Symfony\Component\HttpFoundation\Request;
use Knp\Menu\FactoryInterface;
use Symfony\Component\Routing\Router;

class MenuBuilder
{
    private $factory;

    private $router;

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Routing\Router $router
     */
    public function __construct(FactoryInterface $factory, Router $router)
    {
        $this->factory = $factory;
        $this->router = $router;
    }

    public function createBreadcrumbs(Request $request)
    {
        $menu = $this->factory->createItem('root');
        $menu->setCurrentUri($request->getRequestUri());
        $menu->setAttribute('class', 'breadcrumb');
        
        $menu->addChild('Home', array('route' => 'default_index'));

        return $menu;
    }
}
