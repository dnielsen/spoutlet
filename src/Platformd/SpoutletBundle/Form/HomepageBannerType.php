<?php

namespace Platformd\SpoutletBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\File\File;

class HomepageBannerType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('thumb_file', 'file', array(
                'required' => false,
                'help'  => 'Recommended Size: 120x60px',
            ))
            ->add('banner_file', 'file', array(
                'required' => false,
                'help'  => 'Recommended Size: Size: 634x183px',
            ))
            ->add('url')
            ->add('sites', 'entity', array(
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'property' => 'name'
            ))
            ->add('newWindow', 'choice', array(
                'choices'   => array(1 => 'Yes', 0 => 'No',),
                'label'     => 'Open In New Window?',
            ))
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'platformd_spoutletbundle_homepagebannertype';
    }
}
