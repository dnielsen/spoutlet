<?php

namespace Platformd\CommentBundle\Pagerfanta\View;

use Pagerfanta\PagerfantaInterface;
use Pagerfanta\View\DefaultView;

class CommentView extends DefaultView
{
    public function render(PagerfantaInterface $pagerfanta, $routeGenerator, array $options = array())
    {
        $customRouteGenerator = function($name) use($routeGenerator) {
            $url = $routeGenerator($name);

            return $url.'#commentList';
        };

        return parent::render($pagerfanta, $customRouteGenerator, $options);
    }
}

