<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Entity\Location;

class LocationType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
        ->add('address1', null, array(
            'label' => 'Address Line 1',
        ))
        ->add('address2', null, array(
            'label' => 'Address Line 2',
        ))
        ->add('city', null, array(
            'label' => 'City',
        ))
        ->add('state_province', null, array(
            'label' => 'State/Province'
        ));
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_locationtype';
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Platformd\SpoutletBundle\Entity\Location',
        );
    }
}
