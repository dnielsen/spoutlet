<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Platformd\SpoutletBundle\Entity\BackgroundAdSite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BackgroundAdSiteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('siteId', HiddenType::class, array(
                'attr' => array('class' => 'adSiteId'),
            ))
            ->add('url', UrlType::class, array(
                'attr' => array('class' => 'adSiteUrl'),
//                'help' => 'If background ad does not link to a page, leave this field blank.',
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BackgroundAdSite::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'admin_background_ad_site';
    }
}
