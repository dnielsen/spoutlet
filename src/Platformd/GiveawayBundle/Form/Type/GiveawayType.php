<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Platformd\SpoutletBundle\Form\Type\PurifiedTextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\SpoutletBundle\Form\Type\CountryAgeRestrictionRulesetType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\GiveawayBundle\Entity\GiveawayTranslation;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GiveawayType extends AbstractType
{
    const YES_NO = [
        'Yes' => 1,
        'No' => 0,
    ];

    private $giveaway;
    private $tagManager;

    public function __construct($giveaway, $tagManager)
    {
        $this->giveaway = $giveaway;
        $this->tagManager = $tagManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextareaType::class, [
                'label' => 'Name',
            ])
            ->add('content', PurifiedTextareaType::class, array(
                'label' => 'Description',
                'attr' => array(
                    'class' => 'ckeditor'
                )
            ))
            ->add('translations', CollectionType::class, array(
                'entry_type' => new GiveawayTranslationType,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_options' => array(
                    'data' => new GiveawayTranslation,
                ),
            ))
            ->add('slug', SlugType::class, array(
                'label' => 'URL',
            ))
            ->add('giveawayType', ChoiceType::class, array(
                'choices' => Giveaway::getTypeChoices(),
                'label' => 'Giveaway Type',
                'choices_as_values' => true,
            ))
            ->add('bannerImageFile', FileType::class, [
                'label' => 'Banner Image',
            ])
            ->add('removeBannerImage', CheckboxType::class, array(
                'label' => 'Remove Banner',
                'mapped' => false,
            ))
            ->add('backgroundImage', FileType::class, array(
                'label' => 'Background file',
            ))
            ->add('removeBackgroundImage', CheckboxType::class, array(
                'label' => 'Remove Background',
                'mapped' => false,
            ))
            ->add('backgroundLink', TextType::class, array(
                'label' => 'Background link'
            ))
            ->add('redemptionInstructionsArray', CollectionType::class, array(
                'entry_type' => TextareaType::class,
                'label' => 'Redemption Instructions'
            ))
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => Giveaway::getValidStatusesMap(),
                'placeholder' => 'platformd.giveaway.status.blank_value',
                'choices_as_values' => true,
            ])
            ->add('game', null, [
                'label' => 'Game',
                'empty_value' => 'N/A',
            ])
            ->add('sites', EntityType::class, [
                'label' => 'Sites',
                'class' => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
            ])
            ->add('externalUrl', null, [
                'label' => 'External Url',
//                'help' => '(Optional) If filled in, this URL will override the destination of any links that would normally point to the GiveAway page.'
            ])
            ->add('testOnly', ChoiceType::class, array(
                'choices' => self::YES_NO,
                'choices_as_values' => true,
                'label' => 'Allow admin testing?',
//                'help' => 'This allows admins to still test the operation of the giveaway IF it is unpublished',
            ))
            ->add('displayRemainingKeysNumber', null, array(
                'label' => 'Show key count'
            ))
            ->add('ruleset', CountryAgeRestrictionRulesetType::class, array(
                'label' => 'Restrictions'
            ));

        $builder->add('group', HiddenType::class, array(
            'mapped' => false,
        ));

        $builder->add('featured', null, array(
            'label' => 'Featured'
        ));

        $builder->add('thumbnail', MediaType::class, array(
            'image_label' => 'Thumbnail',
            'image_help' => 'Recommended size: 138x83',
            'with_remove_checkbox' => true
        ));

        $builder->add('tags', TextType::class, array(
            'label' => 'Tags',
//            'help' => "Enter keywords to help people discover the giveaway.",
            'mapped' => false,
            'data' => $this->giveaway ? $this->tagManager->getConcatenatedTagNames($this->giveaway) : null,
            'required' => false,
        ));
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if ($data = $form->getData()) {
            $view->vars = array_replace($view->vars, [
                'mediaObjects' => $data,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Giveaway::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'giveaway';
    }
}
