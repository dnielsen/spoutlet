<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SpoutletBundle\Entity\Site;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BackgroundAdType extends AbstractType
{
    private $isNew;

    public function __construct($isNew = false)
    {
        $this->isNew = $isNew;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('published', 'choice', array(
                'choices' => array(
                    '0' => 'Unpublished',
                    '1' => 'Published',
                ),
                'label' => 'Status',
            ))
//            ->add('sites', 'entity', array(
//                'class' => Site::class,
//                'expanded' => true,
//                'multiple' => true,
//                'property' => 'name',
//            ))
            ->add('adSites', 'collection', array(
                'type' => new BackgroundAdSiteType(),
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ))
            ->add('file', new MediaType(), array(
                'image_label' => 'Image',
                'image_help' => 'Recommended width: 2000px with the center being 970 pixels wide and pure black.',
                'with_remove_checkbox' => true,
            ))
            ->add('dateStart', 'datetime', array(
                'label' => 'Start date',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker',
                )
            ))
            ->add('dateEnd', 'datetime', array(
                'label' => 'End date',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker',
                )
            ))
            ->add('timezone', 'timezone');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => $this->isNew ? array('Default', 'New') : array('Default'),
        ]);
    }

    public function getName()
    {
        return 'admin_background_ad';
    }
}
