<?php

namespace Platformd\SweepstakesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\SweepstakesBundle\Entity\SweepstakesAnswer
 * @ORM\Table(name="pd_sweepstakes_answer")
 * @ORM\Entity(repositoryClass="Platformd\SweepstakesBundle\Entity\SweepsetakesAnswerRepository")
 */
class SweepstakesAnswer
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Assert\NotBlank(message="sweepstakes.errors.answer_blank")
     * @ORM\Column(name="content", type="string", length=255)
     */
    protected $content;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SweepstakesBundle\Entity\SweepstakesQuestion", inversedBy="answers", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $question;

    public function __construct(SweepstakesQuestion $question)
    {
        $this->question = $question;
    }

    public function getId()                { return $this->id; }
    public function getContent()           { return $this->content; }
    public function setContent($value)     { $this->content = $value; }
    public function getQuestion()          { return $this->question; }
    public function setQuestion($value)    { $this->question = $value; }
}
