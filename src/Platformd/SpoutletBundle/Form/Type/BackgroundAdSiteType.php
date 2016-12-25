<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Platformd\SpoutletBundle\Entity\BackgroundAdSite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BackgroundAdSiteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('siteId', 'hidden', array(
                'attr' => array('class' => 'adSiteId'),
            ))
            ->add('url', 'url', array(
                'attr' => array('class' => 'adSiteUrl'),
//                'help' => 'If background ad does not link to a page, leave this field blank.',
            ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BackgroundAdSite::class,
        ]);
    }

    public function getName()
    {
        return 'admin_background_ad_site';
    }
}
