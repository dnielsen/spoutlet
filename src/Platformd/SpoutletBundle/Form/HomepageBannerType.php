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
            ->add('thumb_file', 'file')
            ->add('banner_file', 'file')
            ->add('position')
            ->add('url');
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'platformd_spoutletbundle_homepagebannertype';
    }
}
