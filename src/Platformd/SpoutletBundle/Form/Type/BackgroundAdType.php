<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SpoutletBundle\Entity\Site;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
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
            ->add('title', null, [
                'label' => 'Title',
            ])
            ->add('published', ChoiceType::class, [
                'label' => 'Status',
                'choices' => self::PUBLISHED,
                'choices_as_values' => true,
            ])
            ->add('sites', EntityType::class, array(
                'label' => 'Sites',
                'class' => Site::class,
                'expanded' => true,
                'multiple' => true,
                'choice_label' => 'name',
            ))
            ->add('adSites', CollectionType::class, array(
                'entry_type' => new BackgroundAdSiteType(),
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ))
            ->add('file', MediaType::class, array(
                'image_label' => 'Image',
                'image_help' => 'Recommended width: 2000px with the center being 970 pixels wide and pure black.',
                'with_remove_checkbox' => true,
            ))
            ->add('dateStart', DateTimeType::class, array(
                'label' => 'Start date',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker',
                )
            ))
            ->add('dateEnd', DateTimeType::class, array(
                'label' => 'End date',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker',
                )
            ))
            ->add('timezone', TimezoneType::class, [
                'label' => 'Timezone',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => $this->isNew ? ['Default', 'New'] : ['Default'],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'admin_background_ad';
    }
}
