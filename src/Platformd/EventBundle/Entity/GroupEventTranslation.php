<?php

namespace Platformd\EventBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

use Platformd\SpoutletBundle\Entity\Site;

/**
 * @ORM\Entity
 * @ORM\Table(name="group_event_translation")
 */
class GroupEventTranslation extends EventTranslation
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="GroupEvent", inversedBy="translations")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Assert\NotNull
     */
    protected $translatable;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
}
