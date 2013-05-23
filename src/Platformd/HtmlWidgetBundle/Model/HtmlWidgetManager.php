<?php

namespace Platformd\HtmlWidgetBundle\Model;

use Platformd\HtmlWidgetBundle\Entity\HtmlWidget;
use Doctrine\ORM\EntityManager;

class HtmlWidgetManager
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function saveWidget(HtmlWidget $widget)
    {
        $this->em->persist($widget);
        $this->em->flush();
    }

    public function findAllForSite($site)
    {
        return $this->getRepository()->findAllForSite($site);
    }

    public function findAllBy(array $criteria)
    {
        return $this->getRepository()->findAllBy($criteria);
    }

    public function find($slug)
    {
        return $this->getRepository()->findOneBySlug($slug);
    }

    private function getRepository()
    {
        return $this->em->getRepository('HtmlWidgetBundle:HtmlWidget');
    }
}
