<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Platformd\SpoutletBundle\Form\Type\PurifiedTextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Platformd\GiveawayBundle\Entity\Deal;
use Platformd\SpoutletBundle\Form\Type\OpenGraphOverrideType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\SpoutletBundle\Form\Type\CountryAgeRestrictionRulesetType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class DealType extends AbstractType
{
    const YES_NO = [
        'Yes' => 1,
        'No' => 0,
    ];

    private $deal;
    private $tagManager;

    public function __construct($deal, $tagManager)
    {
        $this->deal         = $deal;
        $this->tagManager   = $tagManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, array('label' => 'Deal Name'))
            ->add('externalUrl', null, array(
                'label' => 'External URL',
                'help'  => '(Optional) If filled in, this URL will override the destination of any links that would normally point to this deal on this site.'
            ))
            ->add('game', EntityType::class, array(
                'class' => 'GameBundle:Game',
                'placeholder' => 'N/A',
                'query_builder' => function(\Platformd\GameBundle\Entity\GameRepository $er) {
                    return $er->createQueryBuilder('g')
                              ->orderBy('g.name', 'ASC');
                    }))
            ->add('slug', SlugType::class, array('url_prefix' => '/deal/{slug}'))
            ->add('startsAt', DateType::class, array(
                'widget' => 'single_text',
                'attr'   => array(
                    'class' => 'datetime-picker'
                ),
                'format' => 'yyyy-MM-dd HH:mm',
                'label'  => 'Starts At'
            ))
            ->add('endsAt', DateType::class, array(
                'widget' => 'single_text',
                'attr'   => array(
                    'class' => 'datetime-picker'
                ),
                'format' => 'yyyy-MM-dd HH:mm',
                'label'  => 'Ends At'
            ))
            ->add('timezone', 'gmtTimezone') //TODO fix timezone
            ->add('banner', MediaType::class, array(
                'image_label' => 'Banner Image',
                'image_help'  => 'Recommended size: 950x270',
                'with_remove_checkbox' => true
            ))
            ->add('thumbnailLarge', MediaType::class, array(
                'image_label' => 'Large Thumbnail',
                'image_help'  => 'Recommended size: 138x83',
                'with_remove_checkbox' => true
            ))
            ->add('claimCodeButton', MediaType::class, array(
                'image_label' => 'Claim Code Now',
                'image_help'  => 'Recommended size: 224x43',
                'with_remove_checkbox' => true
            ))
            ->add('visitWebsiteButton', MediaType::class, array(
                'image_label' => 'Visit Website Image',
                'image_help'  => 'Recommended size: 224x43',
                'with_remove_checkbox' => true
            ))
            ->add('openGraphOverride', OpenGraphOverrideType::class, array('label' => 'Facebook Info'))
            ->add('description', PurifiedTextareaType::class, array(
                'label' => 'Description',
                'attr'  => array('class' => 'ckeditor')
            ))
            ->add('redemptionInstructionsArray', CollectionType::class, array(
                'entry_type'  => 'textarea',
                'label' => 'Redemption Instructions'
            ))
            ->add('websiteUrl', null, array(
                'label' => 'Website URL',
                'help'  => 'ex: http://www.facebook.com'
            ))
            ->add('mediaGalleryMedias', CollectionType::class, array(
                'label'         => 'Screenshots',
                'help'          => 'Only upload 3 images.',
                'allow_add'     => true,
                'allow_delete'  => true,
                'entry_type'          => new MediaType(),
                'entry_options'       => array(
                    'image_label' => 'Screenshot',
                    'image_help'  => 'Recommended size 250x200',
                )
            ))
            ->add('status', ChoiceType::class, array(
                'choices' => $this->getStatusChoices(),
                'choices_as_values' => true,
            ))
            ->add('sites', EntityType::class, array(
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
            ))
            ->add('legalVerbiage', TextareaType::class, array('label' => 'Legal Verbiage'))
            ->add('topColor', HiddenType::class, array(
                'data' => '#000000',
            ))
            ->add('bottomColor', HiddenType::class, array(
                'label' => 'Bottom background color',
                'help' => 'Enter the color in hexadecimal format. Ex: #C030FF (must include hash symbol).'
            ))
            ->add('testOnly', ChoiceType::class, array(
                'choices' => self::YES_NO,
                'choices_as_values' => true,
                'label' => 'Allow admin testing?',
                'help'  => 'This allows admins to still test the operation of the deal IF it is unpublished',
            ));

            $builder->add('group', HiddenType::class, array(
                'property_path' => false,
            ));

            $builder->add('featured', null, array(
                'label' => 'Featured'
            ));

            $builder->add('ruleset', CountryAgeRestrictionRulesetType::class, array('label' => 'Restrictions'));

            $builder->add('tags', TextType::class, array(
                'label' => 'Tags',
                'help' => "Enter keywords to help people discover the deal.",
                'property_path' => false,
                'data' => $this->deal ? $this->tagManager->getConcatenatedTagNames($this->deal) : null,
                'required' => false,
            ));
    }

    public function getStatusChoices()
    {
        $choices = [
            'status.choose_status' => '',
        ];

        foreach (Deal::getValidStatuses() as $status) {
            $choices['status.'.$status] = $status;
        }

        return $choices;
    }

    public function getBlockPrefix()
    {
        return 'platformd_giveawaybundle_dealtype';
    }
}
