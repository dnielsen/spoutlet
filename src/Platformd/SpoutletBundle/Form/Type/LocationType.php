<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Platformd\SpoutletBundle\Entity\Location;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class LocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('address1', null, array(
            'label' => 'Address Line 1',
        ))
        ->add('metroArea', null, array(
            'label' => 'Metro Area',
        ))
        ->add('city', null, array(
            'label' => 'City'
        ))
        ->add('state_province', null, array(
            'label' => 'State/Province'
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'platformd_spoutletbundle_locationtype';
    }
}
