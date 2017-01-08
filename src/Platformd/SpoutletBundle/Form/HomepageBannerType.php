<?php

namespace Platformd\SpoutletBundle\Form;

use Platformd\SpoutletBundle\Entity\Site;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class HomepageBannerType extends AbstractType
{
    const YES_NO = [
        'Yes' => 1,
        'No' => 0,
    ];

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('thumb_file', 'file', array(
                'required' => false,
            ))
            ->add('banner_file', 'file', array(
                'required' => false,
            ))
            ->add('url')
            ->add('sites', 'entity', array(
                'class' => Site::class,
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name'
            ))
            ->add('newWindow', 'choice', array(
                'choices' => self::YES_NO,
                'choices_as_values' => true,
                'label' => 'Open In New Window?',
            ));
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'platformd_spoutletbundle_homepagebannertype';
    }
}
