<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
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
            ->add('googleDocUrl', 'text', array(
                'label' => 'Data Source URL:',
            ))
            ->add('name', 'text', array(
                'label' => 'Name',
            ))
        ;
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_timelinetype';
    }
}
