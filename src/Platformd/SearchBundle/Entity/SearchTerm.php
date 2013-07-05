<?php

namespace Platformd\SearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="pd_search_term")
 * @ORM\Entity(repositoryClass="Platformd\SearchBundle\Entity\SearchTermRepository")
 */
class SearchTerm
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $criteria;

    /**
     * @ORM\Column(name="datetime", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $datetime;

    function __construct($criteria = null) {
        $this->criteria = $criteria;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCriteria()
    {
        return $this->criteria;
    }

    public function getDateTime()
    {
        return $this->datetime;
    }
}
