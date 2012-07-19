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

    /**
     * The admin breadcrumbs
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Knp\Menu\ItemInterface
     */
    public function createAdminBreadcrumbs(Request $request)
    {
        $menu = $this->factory->createItem('root');
        $menu->setCurrentUri($request->getRequestUri());
        $menu->setAttribute('class', 'breadcrumb');

        $menu->addChild('Home', array('route' => 'admin_index'));

        return $menu;
    }
}
