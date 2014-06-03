<?php

namespace Platformd\IdeaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="registration_field")
 */
class RegistrationField {

    const TYPE_TEXT     = 'text';
    const TYPE_CHECKBOX = 'checkbox';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\GroupBundle\Entity\Group", inversedBy="registrationFields")
     */
    protected $group;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\EventBundle\Entity\GroupEvent", inversedBy="registrationFields")
     */
    protected $event;

    /**
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * @ORM\Column(type="string")
     */
    protected $question;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\IdeaBundle\Entity\RegistrationAnswer", mappedBy="field", cascade={"remove", "persist"})
     */
    protected $answers;


    public function __construct()
    {
        $this->answers = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $answer
     */
    public function addAnswer($answer)
    {
        $this->answers->add($answer);
    }

    /**
     * @return mixed
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * @param mixed $event
     */
    public function setEvent($event)
    {
        $this->event = $event;
    }

    /**
     * @return mixed
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param mixed $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return mixed
     */
    public function getGroup()
    {
        return $this->group;
    }

    public function setType($type)
    {
        $this->type = $type;
    }
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $question
     */
    public function setQuestion($question)
    {
        $this->question = $question;
    }

    /**
     * @return mixed
     */
    public function getQuestion()
    {
        return $this->question;
    }

}