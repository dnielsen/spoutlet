<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\SpoutletBundle\Entity\Contest;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\Form\FormBuilderInterface;

class ContestType extends AbstractType
{
    const YES_NO = [
        'Yes' => 1,
        'No' => 0,
    ];

    const ALLOWED_ENTRIES = [
        0,
        1,
        2,
        3,
        4,
        5,
    ];

    private $contest;
    private $tagManager;

    public function __construct($contest, $tagManager)
    {
        $this->contest = $contest;
        $this->tagManager = $tagManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, array(
                'label' => 'Contest name',
            ))
            ->add('category', 'choice', array(
                'choices' => self::getCategoryChoices(),
                'choices_as_values' => true,
            ))
            ->add('game', 'entity', array(
                'class' => 'GameBundle:Game',
                'choice_label' => 'name',
                'empty_value' => 'N/A'
            ))
            ->add('slug', new SlugType(), array(
                'url_prefix' => '/contest/'
            ))
            ->add('sites', 'entity', array(
                'required' => true,
                'class' => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
            ))
            ->add('submissionStart', 'datetime', array(
                'label' => 'Submission Starts:',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker'
                )
            ))
            ->add('submissionEnd', 'datetime', array(
                'label' => 'Submission Ends:',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker'
                )
            ))
            ->add('votingStart', 'datetime', array(
                'label' => 'Voting Starts:',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker'
                )
            ))
            ->add('votingEnd', 'datetime', array(
                'label' => 'Voting Ends:',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker'
                )
            ))
            ->add('timezone', 'gmtTimezone')
            ->add('banner', new MediaType(), array(
                'image_label' => 'Banner Image',
                'image_help' => 'Recommended size: 950px x 160px with 40px on bottom of banner for submenu overlay.',
                'with_remove_checkbox' => true,
            ))
            ->add('rules', 'purifiedTextarea', array(
                'required' => true,
                'attr' => array('class' => 'ckeditor'),
            ))
            ->add('entryInstructions', 'purifiedTextarea', array(
                'label' => 'Instructions for contestants',
                'attr' => array('class' => 'ckeditor'),
            ))
            ->add('voteInstructions', 'purifiedTextarea', array(
                'label' => 'Instructions for voters',
                'attr' => array('class' => 'ckeditor'),
            ))
            ->add('redemptionInstructionsArray', 'collection', array(
                'type' => 'textarea',
                'label' => 'Redemption Instructions',
            ))
            ->add('maxEntries', 'choice', array(
                'label' => 'Entries allowed',
                'choices' => self::ALLOWED_ENTRIES,
            ))
            ->add('openGraphOverride', new OpenGraphOverrideType(), array('label' => 'Facebook Info'))
            ->add('status', 'choice', array(
                'choices' => $this->getStatusChoices(),
                'choices_as_values' => true,
            ))
            ->add('ruleset', new CountryAgeRestrictionRulesetType(), array(
                'label' => 'Restrictions',
            ))
            ->add('testOnly', 'choice', array(
                'choices' => self::YES_NO,
                'choices_as_values' => true,
                'label' => 'Allow admin testing?',
            ))
            ->add('hidden', 'checkbox', array(
                'label' => 'Do not display listing',
            ));

        $builder->add('tags', 'text', array(
            'label' => 'Tags',
            'mapped' => false,
            'data' => $this->contest ? $this->tagManager->getConcatenatedTagNames($this->contest) : null,
        ));
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_contesttype';
    }

    private static function getCategoryChoices()
    {
        $values = Contest::getValidCategories();

        $choices = [];

        foreach ($values as $value) {
            $choices[$value] = $value;
        }

        return $choices;
    }

    private static function getStatusChoices()
    {
        $choices = [];

        foreach (Contest::getValidStatuses() as $status) {
            $choices['status.' . $status] = $status;
        }

        return $choices;
    }
}
