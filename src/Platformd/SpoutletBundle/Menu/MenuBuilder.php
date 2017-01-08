<?php

namespace Platformd\SpoutletBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class MenuBuilder
{
    private $factory;

    /**
     * MenuBuilder constructor.
     *
     * @param FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
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
        $menu->setChildrenAttribute('class', 'breadcrumb');

        $menu->addChild('Home', ['route' => 'admin_index']);

        return $menu;
    }
}
