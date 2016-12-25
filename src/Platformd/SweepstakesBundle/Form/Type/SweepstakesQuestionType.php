<?php

namespace Platformd\SweepstakesBundle\Form\Type;

use Platformd\SweepstakesBundle\Entity\SweepstakesQuestion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SweepstakesQuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('content', 'text', array('label' => 'Question'));
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SweepstakesQuestion::class,
        ]);
    }

    public function getName()
    {
        return 'sweepstakes_question';
    }
}
