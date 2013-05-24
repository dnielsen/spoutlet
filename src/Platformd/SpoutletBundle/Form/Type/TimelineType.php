<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class TimelineType extends AbstractType
{

    /**
     * Configures a Timeline form.
     *
     * @param FormBuilder $builder
     * @param array $options
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('googleDocUrl', 'text', array(
                'label' => 'Data Source URL:',
                'help' => 'Currently, only Google Docs is supported as a datasource',
                'required' => true,
            ))
            ->add('name', 'text', array(
                'label' => 'Name',
                'required' => true,
            ))
        ;
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_timelinetype';
    }

}
