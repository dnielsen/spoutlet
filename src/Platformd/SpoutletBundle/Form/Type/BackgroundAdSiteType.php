<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class BackgroundAdSiteType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('siteId', 'hidden', array(
                'attr' => array('class' => 'adSiteId'),
            ))
            ->add('url', 'url', array(
                'attr' => array('class' => 'adSiteUrl'),
                'help' => 'If background ad does not link to a page, leave this field blank.',
            ))
        ;
    }

    public function getName()
    {
        return 'admin_background_ad_site';
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Platformd\SpoutletBundle\Entity\BackgroundAdSite',
        );
    }
}
