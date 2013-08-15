<?php

namespace Platformd\SweepstakesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class SweepstakesAnswerType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $question = $builder->getData()->getQuestion()->getContent();

        $builder
            ->add('content', 'text', array('label' => $question));
        ;
    }

    public function getName()
    {
        return 'sweepstakes_answer';
    }

    public function getDefaultOptions(array $options)
    {
        $options['data_class'] = 'Platformd\SweepstakesBundle\Entity\SweepstakesAnswer';

        return $options;
    }
}
