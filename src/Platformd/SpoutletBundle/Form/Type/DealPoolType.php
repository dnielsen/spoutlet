<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

/**
*
*/
class DealPoolType extends AbstractType
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
            ->add('allowedCountries', 'entity', array('multiple' => true, 'expanded' => true, 'class' => 'Platformd\SpoutletBundle\Entity\Country', 'property' => 'name'))
            ->add('keysfile', 'file');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {

        return 'deal_pool';
    }
}
