<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class SiteTakeoverType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('startsAt', 'date', array(
                'widget' => 'single_text',
                'attr'   => array(
                    'class' => 'datetime-picker'
                ),
                'format' => 'yyyy-MM-dd HH:mm',
                'label'  => 'Starts At'
            ))
            ->add('endsAt', 'date', array(
                'widget' => 'single_text',
                'attr'   => array(
                    'class' => 'datetime-picker'
                ),
                'format' => 'yyyy-MM-dd HH:mm',
                'label'  => 'Ends At'
            ))
            ->add('timezone', 'timezone', array('label' => 'Timezone'))
            ->add('sites', 'entity', array(
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'property' => 'name',
            ))
            ->add('content', 'textarea', array(
                'attr'  => array('class' => 'ckeditor'),#
                'help'  => 'Press the "Source" button if you want to paste in HTML code directly. Please ensure that the code is valid.',
            ));

    }

    public function getName()
    {
        return 'platformd_spoutletbundle_sitetakeovertype';
    }
}
