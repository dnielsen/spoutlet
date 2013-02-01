<?php

namespace Platformd\SpoutletBundle\Pagerfanta\View;

use Pagerfanta\PagerfantaInterface;
use Pagerfanta\View\DefaultView;

class SpoutletView extends DefaultView
{
    public function render(PagerfantaInterface $pagerfanta, $routeGenerator, array $options = array())
    {
        $customRouteGenerator = function($name) use($routeGenerator) {
            $url = $routeGenerator($name);

            return $url.'#commentList';
        };

                $options = array_merge(array(
            'proximity'          => 2,
            'previous_message'   => 'Previous',
            'next_message'       => 'Next',
            'css_disabled_class' => 'disabled',
            'css_dots_class'     => 'dots',
            'css_current_class'  => 'current',
        ), $options);

        $currentPage = $pagerfanta->getCurrentPage();

        $startPage = $currentPage - $options['proximity'];
        $endPage = $currentPage + $options['proximity'];

        if ($startPage < 1) {
            $endPage = min($endPage + (1 - $startPage), $pagerfanta->getNbPages());
            $startPage = 1;
        }
        if ($endPage > $pagerfanta->getNbPages()) {
            $startPage = max($startPage - ($endPage - $pagerfanta->getNbPages()), 1);
            $endPage = $pagerfanta->getNbPages();
        }

        $pages = array();

        // previous
/*        if ($pagerfanta->hasPreviousPage()) {
            $pages[] = array($pagerfanta->getPreviousPage(), $options['previous_message']);
        } else {
            $pages[] = sprintf('<span class="%s">%s</span>', $options['css_disabled_class'], $options['previous_message']);
        }*/

        // first
        if ($startPage > 1) {
            $pages[] = array(1, 1);
            if (3 == $startPage) {
                $pages[] = array(2, 2);
            } elseif (2 != $startPage) {
                $pages[] = sprintf('<span class="%s">...</span>', $options['css_dots_class']);
            }
        }

        // pages
        for ($page = $startPage; $page <= $endPage; $page++) {
            if ($page == $currentPage) {
                $pages[] = sprintf('<span class="%s">%s</span>', $options['css_current_class'], $page);
            } else {
                $pages[] = array($page, $page);
            }
        }

        // last
        if ($pagerfanta->getNbPages() > $endPage) {
            if ($pagerfanta->getNbPages() > ($endPage + 1)) {
                if ($pagerfanta->getNbPages() > ($endPage + 2)) {
                    $pages[] = sprintf('<span class="%s">...</span>', $options['css_dots_class']);
                } else {
                    $pages[] = array($endPage + 1, $endPage + 1);
                }
            }

            $pages[] = array($pagerfanta->getNbPages(), $pagerfanta->getNbPages());
        }

        // next
/*        if ($pagerfanta->hasNextPage()) {
            $pages[] = array($pagerfanta->getNextPage(), $options['next_message']);
        } else {
            $pages[] = sprintf('<span class="%s">%s</span>', $options['css_disabled_class'], $options['next_message']);
        }*/

        // process
        $pagesHtml = '';
        foreach ($pages as $page) {
            if (is_string($page)) {
                $pagesHtml .= $page;
            } else {
                $pagesHtml .= '<a href="'.$routeGenerator($page[0]).'">'.$page[1].'</a>';
            }
        }

        return '<nav class="spoutlet-pager">'.$pagesHtml.'</nav>';
        //return parent::render($pagerfanta, $customRouteGenerator, $options);
    }
}

