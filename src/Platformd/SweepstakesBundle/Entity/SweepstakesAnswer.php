<?php

namespace Platformd\SweepstakesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContext;

/**
 * Platformd\SweepstakesBundle\Entity\SweepstakesAnswer
 * @ORM\Table(name="pd_sweepstakes_answer")
 * @ORM\Entity(repositoryClass="Platformd\SweepstakesBundle\Entity\SweepsetakesAnswerRepository")
 * @Assert\Callback(methods={"validateAnswers"})
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
     * @ORM\Column(name="content", type="string", length=255)
     */
    protected $content;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SweepstakesBundle\Entity\SweepstakesQuestion", inversedBy="answers", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $question;

    /**
     * @ORM\ManyToOne(targetEntity="Platformd\SweepstakesBundle\Entity\SweepstakesEntry", inversedBy="answers")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $entry;

    public function __construct(SweepstakesQuestion $question, SweepstakesEntry $entry)
    {
        $this->question = $question;
        $this->entry    = $entry;
    }

    public function getId()                { return $this->id; }
    public function getContent()           { return $this->content; }
    public function setContent($value)     { $this->content = $value; }
    public function getQuestion()          { return $this->question; }
    public function setQuestion($value)    { $this->question = $value; }
    public function getEntry()             { return $this->entry; }
    public function setEntry($value)       { $this->entry = $value; }

    public function validateAnswers(ExecutionContext $executionContext)
    {
        $isPromoCode = $this->question->getSweepstakes()->getEventType() == Sweepstakes::SWEEPSTAKES_TYPE_PROMO_CODE;
        $message = $isPromoCode ? 'sweepstakes.errors.promo_code_blank' : 'sweepstakes.errors.answer_blank';

        $content = $this->getContent();

        if (empty($content)) {
            $oldPath = $executionContext->getPropertyPath();
            $propertyPath = $oldPath . '.content';
            $executionContext->setPropertyPath($propertyPath);

            $executionContext->addViolation(
                $message,
                array(),
                "answer"
            );

            $executionContext->setPropertyPath($oldPath);
        }
    }
}
