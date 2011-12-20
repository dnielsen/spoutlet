<?php

namespace Platformd\SpoutletBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\File\File;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;

class HomepageBannerType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('thumb_file', 'file', array('required' => false))
            ->add('banner_file', 'file', array('required' => false))
            ->add('position')
            ->add('url')
            ->add('locale', new SiteChoiceType())
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
