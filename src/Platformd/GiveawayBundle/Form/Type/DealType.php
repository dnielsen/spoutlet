<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\GiveawayBundle\Entity\Deal;
use Platformd\SpoutletBundle\Form\Type\OpenGraphOverrideType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\SpoutletBundle\Form\Type\CountryAgeRestrictionRulesetType;

class DealType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('name', null, array('label' => 'Deal Name'))
            ->add('externalUrl', null, array(
                'label' => 'External URL',
                'help'  => '(Optional) If filled in, this URL will override the destination of any links that would normally point to this deal on this site.'
            ))
            ->add('game', 'entity', array('class' => 'GameBundle:Game', 'empty_value' => 'N/A',
                'query_builder' => function(\Platformd\GameBundle\Entity\GameRepository $er) {
                    return $er->createQueryBuilder('g')
                              ->orderBy('g.name', 'ASC');
                    }))
            ->add('slug', new SlugType(), array('url_prefix' => '/deal/{slug}'))
            ->add('startsAt', 'date', array(
                'widget' => 'single_text',
                'attr'   => array(
                    'class' => 'datetime-picker'
                ),
                'format' => 'yyyy-MM-dd HH:mm',
                'label'  => 'Starts At'
            ))
            ->add('endsAt', 'date', array(
                'widget' => 'single_text',
                'attr'   => array(
                    'class' => 'datetime-picker'
                ),
                'format' => 'yyyy-MM-dd HH:mm',
                'label'  => 'Ends At'
            ))
            ->add('timezone', 'gmtTimezone')
            ->add('banner', new MediaType(), array(
                'image_label' => 'Banner Image',
                'image_help'  => 'Recommended size: 950x270',
                'with_remove_checkbox' => true
            ))
            ->add('thumbnailLarge', new MediaType(), array(
                'image_label' => 'Large Thumbnail',
                'image_help'  => 'Recommended size: 138x83',
                'with_remove_checkbox' => true
            ))
            ->add('claimCodeButton', new MediaType(), array(
                'image_label' => 'Claim Code Now',
                'image_help'  => 'Recommended size: 224x43',
                'with_remove_checkbox' => true
            ))
            ->add('visitWebsiteButton', new MediaType(), array(
                'image_label' => 'Visit Website Image',
                'image_help'  => 'Recommended size: 224x43',
                'with_remove_checkbox' => true
            ))
            ->add('openGraphOverride', new OpenGraphOverrideType(), array('label' => 'Facebook Info'))
            ->add('description', 'purifiedTextarea', array(
                'label' => 'Description',
                'attr'  => array('class' => 'ckeditor')
            ))
            ->add('redemptionInstructionsArray', 'collection', array(
                'type'  => 'textarea',
                'label' => 'Redemption Instructions'
            ))
            ->add('websiteUrl', null, array(
                'label' => 'Website URL',
                'help'  => 'ex: http://www.facebook.com'
            ))
            ->add('mediaGalleryMedias', 'collection', array(
                'label'         => 'Screenshots',
                'help'          => 'Only upload 3 images.',
                'allow_add'     => true,
                'allow_delete'  => true,
                'type'          => new MediaType(),
                'options'       => array(
                    'image_label' => 'Screenshot',
                    'image_help'  => 'Recommended size 250x200',
                )
            ))
            ->add('status', 'choice', array(
                'choices' => $this->getStatusChoices(),
            ))
            ->add('sites', 'entity', array(
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'property' => 'name',
            ))
            ->add('legalVerbiage', 'textarea', array('label' => 'Legal Verbiage'))
            ->add('topColor', 'hidden', array(
                'data' => '#000000',
            ))
            ->add('bottomColor', 'hidden', array(
                'label' => 'Bottom background color',
                'help' => 'Enter the color in hexadecimal format. Ex: #C030FF (must include hash symbol).'
            ))
            ->add('testOnly', 'choice', array(
                'choices' => array(
                    1 => 'Yes',
                    0 => 'No',
                ),
                'label' => 'Allow admin testing?',
                'help'  => 'This allows admins to still test the operation of the deal IF it is unpublished',
            ));

            $builder->add('ruleset', new CountryAgeRestrictionRulesetType(), array('label' => 'Restrictions'));
    }

    public function getStatusChoices()
    {
        $choices = array(
            '' => 'status.choose_status',
        );

        foreach (Deal::getValidStatuses() as $status) {
            $choices[$status] = 'status.'.$status;
        }

        return $choices;
    }

    public function getName()
    {
        return 'platformd_giveawaybundle_dealtype';
    }
}
