<?php

namespace Platformd\SweepstakesBundle\Form\Type;

use Platformd\SweepstakesBundle\Entity\SweepstakesQuestion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SweepstakesQuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('content', TextType::class, array('label' => 'Question'));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SweepstakesQuestion::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'sweepstakes_question';
    }
}
