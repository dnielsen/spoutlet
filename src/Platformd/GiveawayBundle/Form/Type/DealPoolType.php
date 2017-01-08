<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class DealPoolType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // this is a long field, but it's mean for users not to put much here
            ->add('description', 'text', array('required' => false))
            ->add('maxKeysPerIp', 'integer', array('required' => false))
            ->add('isActive', 'checkbox', array('required' => false))
            ->add('keysAreUrls', 'checkbox', array('required' => false, 'label' => 'Keys are URLS?'))
            ->add('allowedCountries', 'entity', array(
                'multiple' => true,
                'expanded' => false,
                'class' => 'Platformd\SpoutletBundle\Entity\Country',
                'choice_label' => 'name'
            ))
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
