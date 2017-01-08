<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\GiveawayBundle\Entity\Giveaway;
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
            ->add('name', 'textarea')
            ->add('content', 'purifiedTextarea', array(
                'label' => 'Description',
                'attr' => array(
                    'class' => 'ckeditor'
                )
            ))
            ->add('translations', 'collection', array(
                'type' => new GiveawayTranslationType,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'options' => array(
                    'data' => new GiveawayTranslation,
                ),
            ))
            ->add('slug', new SlugType(), array('label' => 'URL'))
            ->add('giveawayType', 'choice', array(
                'choices' => Giveaway::getTypeChoices(),
                'label' => 'Giveaway Type',
                'choices_as_values' => true,
            ))
            ->add('bannerImageFile', 'file')
            ->add('removeBannerImage', 'checkbox', array(
                'label' => 'Remove Banner',
                'mapped' => false,
            ))
            ->add('backgroundImage', 'file', array(
                'label' => 'Background file',
            ))
            ->add('removeBackgroundImage', 'checkbox', array(
                'label' => 'Remove Background',
                'mapped' => false,
            ))
            ->add('backgroundLink', 'text', array(
                'label' => 'Background link'
            ))
            ->add('redemptionInstructionsArray', 'collection', array(
                'type' => 'textarea',
                'label' => 'Redemption Instructions'
            ))
            ->add('status', 'choice', array(
                'choices' => Giveaway::getValidStatusesMap(),
                'empty_value' => 'platformd.giveaway.status.blank_value',
                'choices_as_values' => true,
            ))
            ->add('game', null, array('empty_value' => 'N/A'))
            ->add('sites', 'entity', array(
                'class' => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
            ))
            ->add('externalUrl', null, array(
//                'help' => '(Optional) If filled in, this URL will override the destination of any links that would normally point to the GiveAway page.'
                )
            )
            ->add('testOnly', 'choice', array(
                'choices' => self::YES_NO,
                'choices_as_values' => true,
                'label' => 'Allow admin testing?',
//                'help' => 'This allows admins to still test the operation of the giveaway IF it is unpublished',
            ))
            ->add('displayRemainingKeysNumber', null, array(
                'label' => 'Show key count'
            ))
            ->add('ruleset', new CountryAgeRestrictionRulesetType(), array(
                'label' => 'Restrictions'
            ));

        $builder->add('group', 'hidden', array(
            'mapped' => false,
        ));

        $builder->add('featured', null, array(
            'label' => 'Featured'
        ));

        $builder->add('thumbnail', new MediaType(), array(
            'image_label' => 'Thumbnail',
            'image_help' => 'Recommended size: 138x83',
            'with_remove_checkbox' => true
        ));

        $builder->add('tags', 'text', array(
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

    public function getName()
    {
        return 'giveaway';
    }
}
