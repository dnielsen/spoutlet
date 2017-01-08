<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BackgroundAdType extends AbstractType
{
    const PUBLISHED = [
        'Unpublished' => 0,
        'Published' => 1,
    ];

    /**
     * @var bool
     */
    private $isNew;

    /**
     * BackgroundAdType constructor.
     *
     * @param bool $isNew
     */
    public function __construct($isNew = false)
    {
        $this->isNew = $isNew;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('published', 'choice', [
                'label' => 'Status',
                'choices' => self::PUBLISHED,
                'choices_as_values' => true,
            ])
//            ->add('sites', 'entity', array(
//                'class' => Site::class,
//                'expanded' => true,
//                'multiple' => true,
//                'choice_label' => 'name',
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => $this->isNew ? ['Default', 'New'] : ['Default'],
        ]);
    }

    public function getName()
    {
        return 'admin_background_ad';
    }
}
