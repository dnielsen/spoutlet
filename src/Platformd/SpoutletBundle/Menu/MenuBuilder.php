<?php

namespace Platformd\SpoutletBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
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
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     * @return \Knp\Menu\ItemInterface
     */
    public function createAdminBreadcrumbs(RequestStack $requestStack)
    {
        $menu = $this->factory->createItem('root');
        $menu->setUri($requestStack->getCurrentRequest()->getRequestUri());
        $menu->setAttribute('class', 'breadcrumb');

        $menu->addChild('Home', ['route' => 'admin_index']);

        return $menu;
    }
}
