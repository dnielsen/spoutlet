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
            ->add('giveaway', 'entity', array(
                'class'         => 'GiveawayBundle:Giveaway',
                'empty_value'   => ''
            ))
            ->add('description', 'textarea')
            ->add('maxKeysPerIp', 'integer')
            ->add('upperLimit', 'integer')
            ->add('lowerLimit', 'integer')
            ->add('isActive', 'checkbox');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        
        return 'giveway_pool';
    }
}