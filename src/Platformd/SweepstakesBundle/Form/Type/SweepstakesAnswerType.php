<?php

namespace Platformd\SweepstakesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Platformd\SweepstakesBundle\Entity\SweepstakesAnswer;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SweepstakesAnswerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($builder)
        {
            $form = $event->getForm();
            $data = $event->getData();

            /* Check we're looking at the right data/form */
            if ($data instanceof SweepstakesAnswer) {
                $label = $data->getQuestion()->getContent();
                $form->add($builder->getFormFactory()->createNamed(
                    'text',
                    'content',
                    null,
                    ['label' => $label]
                ));
            }
        });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SweepstakesAnswer::class,
        ]);
    }

    public function getName()
    {
        return 'sweepstakes_answer';
    }
}
