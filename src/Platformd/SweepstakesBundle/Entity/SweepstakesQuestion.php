<?php

namespace Platformd\SweepstakesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Platformd\SweepstakesBundle\Entity\SweepstakesQuestion
 * @ORM\Table(name="pd_sweepstakes_question")
 * @ORM\Entity(repositoryClass="Platformd\SweepstakesBundle\Entity\SweepsetakesQuestionRepository")
 */
class SweepstakesQuestion
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="content", type="string", length=255)
     */
    protected $content;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SweepstakesBundle\Entity\Sweepstakes", inversedBy="questions", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $sweepstakes;

    /**
     * @ORM\OneToMany(targetEntity="Platformd\SweepstakesBundle\Entity\SweepstakesAnswer", mappedBy="question", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $answers;

    public function getId()                { return $this->id; }
    public function getContent()           { return $this->content; }
    public function setContent($value)     { $this->content = $value; }
    public function getSweepstakes()       { return $this->sweepstakes; }
    public function setSweepstakes($value) { $this->sweepstakes = $value; }
    public function getAnswers()           { return $this->answers; }
    public function setAnswers($value)     { $this->answers = $value; }
}
