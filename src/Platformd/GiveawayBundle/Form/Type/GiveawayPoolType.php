<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

/**
*
*/
class GiveawayPoolType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            // this is a long field, but it's mean for users not to put much here
            ->add('description', 'text', array('required' => false))
            ->add('maxKeysPerIp', 'integer', array('required' => false))
            ->add('upperLimit', 'integer', array('required' => false))
            ->add('lowerLimit', 'integer', array('required' => false))
            ->add('isActive', 'checkbox', array('required' => false))
            ->add('keysfile', 'file');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {

        return 'giveway_pool';
    }
}
