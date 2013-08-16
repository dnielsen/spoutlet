<?php

namespace Platformd\SweepstakesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Event\DataEvent;

use Platformd\SweepstakesBundle\Entity\SweepstakesAnswer;

class SweepstakesAnswerType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (DataEvent $event) use ($builder)
        {
            $form = $event->getForm();
            $data = $event->getData();

            /* Check we're looking at the right data/form */
            if ($data instanceof SweepstakesAnswer)
            {
                $label = $data->getQuestion()->getContent();
                $form->add($builder->getFormFactory()->createNamed(
                    'text',
                    'content',
                    null,
                    array('label' => $label)
                ));
            }
        });
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
