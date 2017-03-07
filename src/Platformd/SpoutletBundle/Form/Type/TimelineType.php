<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TimelineType extends AbstractType
{
    /**
     * Configures a Timeline form.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('googleDocUrl', TextType::class, array(
                'label' => 'Data Source URL:',
            ))
            ->add('name', TextType::class, array(
                'label' => 'Name',
            ))
        ;
    }

    public function getBlockPrefix()
    {
        return 'platformd_spoutletbundle_timelinetype';
    }
}
