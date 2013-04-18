<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\SpoutletBundle\Form\Type\CountryAgeRestrictionRulesetType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\GiveawayBundle\Entity\GiveawayTranslation;

class GiveawayType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
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
                'allow_add'      => true,
                'allow_delete'   => true,
                'by_reference' => false,
                'options' => array(
                    'data' => new GiveawayTranslation,
                ),
            ))
            ->add('slug', new SlugType(), array('label' => 'URL'))
            ->add('giveawayType', 'choice', array(
                'choices' => Giveaway::getTypeChoices(),
                'label' => 'Giveaway Type'
            ))
            ->add('bannerImageFile', 'file')
            ->add('removeBannerImage', 'checkbox', array(
                'label' => 'Remove Banner',
                'property_path' => false,
            ))
            ->add('backgroundImage', 'file', array(
                'label' => 'Background file',
            ))
            ->add('removeBackgroundImage', 'checkbox', array(
                'label' => 'Remove Background',
                'property_path' => false,
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
            ))
            ->add('game', null, array('empty_value' => 'N/A'))
            ->add('sites', 'entity', array(
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'property' => 'name',
            ))
            ->add('externalUrl', null, array(
                'help' => '(Optional) If filled in, this URL will override the destination of any links that would normally point to the GiveAway page.')
            )
            ->add('testOnly', 'choice', array(
                'choices' => array(
                    1 => 'Yes',
                    0 => 'No',
                ),
                'label' => 'Allow admin testing?',
                'help'  => 'This allows admins to still test the operation of the giveaway IF it is unpublished',
            ))
            ->add('displayRemainingKeysNumber', null, array(
                'label' => 'Show key count'
            ))
            ->add('ruleset', new CountryAgeRestrictionRulesetType(), array(
                'label' => 'Restrictions'
            ));

        $builder->add('group', 'hidden', array(
            'property_path' => false,
        ));

        $builder->add('featured', null, array(
            'label' => 'Featured'
        ));

        $builder->add('thumbnail', new MediaType(), array(
                'image_label' => 'Thumbnail',
                'image_help'  => 'Recommended size: 138x83',
                'with_remove_checkbox' => true
        ));
    }

    public function getName()
    {
        return 'giveaway';
    }

    public function getDefaultOptions(array $options)
    {
        $options = parent::getDefaultOptions($options);

        $options['data_class'] = 'Platformd\GiveawayBundle\Entity\Giveaway';

        return $options;
    }

    public function buildViewBottomUp(FormView $view, FormInterface $form)
    {
        if ($data = $form->getData()) {
            $view->set('mediaObjects', $data);
        }
    }

}
