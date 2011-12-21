<?php

namespace Platformd\SpoutletBundle\Menu;

use Knp\Menu\MenuItem;
use Knp\Menu\Renderer\ListRenderer; 
use Knp\Menu\ItemInterface;

class BreadcrumbsRenderer extends ListRenderer
{
    /**
     * Renders the link in a a tag with link attributes or
     * the label in a span tag with label attributes
     *
     * Tests if item has a an uri and if not tests if it's
     * the current item and if the text has to be rendered
     * as a link or not.
     *
     * @param MenuItem $item The item to render the link or label for
     * @return string
     */
    public function renderLink(ItemInterface $item, array $options = array())
    {
        $text = '';
        if (!$item->getUri()) {
            $text = sprintf('<span%s>%s</span>', $this->renderHtmlAttributes($item->getLabelAttributes()), $item->getLabel());
        } elseif (!$item->isCurrent()) {
            $text = sprintf('<a href="%s"%s>%s</a>', $item->getUri(), $this->renderHtmlAttributes($item->getLinkAttributes()), $item->getLabel());
        } else {
            $text = sprintf('<span%s>%s</span>', $this->renderHtmlAttributes($item->getLabelAttributes()), $item->getLabel());
        }

        // Add the &gt; if not the last item
        $text .= $item->isLast() ? '' : '&nbsp; &gt;';

        return $this->format($text, 'link', $item->getLevel());
    }
}
