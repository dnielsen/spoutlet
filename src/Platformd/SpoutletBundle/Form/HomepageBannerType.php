<?php

namespace Platformd\SpoutletBundle\Form;

use Platformd\SpoutletBundle\Entity\Site;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
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
            ->add('thumb_file', FileType::class, array(
                'label' => 'Thumb File',
                'required' => false,
            ))
            ->add('banner_file', FileType::class, array(
                'label' => 'Banner File',
                'required' => false,
            ))
            ->add('url', null, [
                'label' => 'Url',
            ])
            ->add('sites', EntityType::class, array(
                'label' => 'Sites',
                'class' => Site::class,
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name'
            ))
            ->add('newWindow', ChoiceType::class, array(
                'choices' => self::YES_NO,
                'choices_as_values' => true,
                'label' => 'Open In New Window?',
            ));
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return 'platformd_spoutletbundle_homepagebannertype';
    }
}
