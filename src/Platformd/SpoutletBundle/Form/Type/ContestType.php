<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Platformd\SpoutletBundle\Entity\Contest;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
            ->add('category', ChoiceType::class, array(
                'label' => 'Category',
                'choices' => self::getCategoryChoices(),
                'choices_as_values' => true,
            ))
            ->add('game', EntityType::class, array(
                'label' => 'Game',
                'class' => 'GameBundle:Game',
                'choice_label' => 'name',
                'placeholder' => 'N/A'
            ))
            ->add('slug', SlugType::class, array(
                'label' => 'URL string - /contest/',
            ))
            ->add('sites', EntityType::class, array(
                'label' => 'Sites',
                'required' => true,
                'class' => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
            ))
            ->add('submissionStart', DateTimeType::class, array(
                'label' => 'Submission Starts:',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker'
                )
            ))
            ->add('submissionEnd', DateTimeType::class, array(
                'label' => 'Submission Ends:',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker'
                )
            ))
            ->add('votingStart', DateTimeType::class, array(
                'label' => 'Voting Starts:',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker'
                )
            ))
            ->add('votingEnd', DateTimeType::class, array(
                'label' => 'Voting Ends:',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker'
                )
            ))
            ->add('timezone', GmtOffsetTimezoneType::class, [
                'label' => 'Timezone',
            ])
            ->add('banner', MediaType::class, array(
                'image_label' => 'Banner Image',
                'image_help' => 'Recommended size: 950px x 160px with 40px on bottom of banner for submenu overlay.',
                'with_remove_checkbox' => true,
            ))
            ->add('rules', PurifiedTextareaType::class, array(
                'label' => 'Rules',
                'attr' => array('class' => 'ckeditor'),
            ))
            ->add('entryInstructions', PurifiedTextareaType::class, array(
                'label' => 'Instructions for contestants',
                'attr' => array('class' => 'ckeditor'),
            ))
            ->add('voteInstructions', PurifiedTextareaType::class, array(
                'label' => 'Instructions for voters',
                'attr' => array('class' => 'ckeditor'),
            ))
            ->add('redemptionInstructionsArray', CollectionType::class, array(
                'entry_type' => TextareaType::class,
                'label' => 'Redemption Instructions',
            ))
            ->add('maxEntries', ChoiceType::class, array(
                'label' => 'Entries allowed',
                'choices' => self::ALLOWED_ENTRIES,
            ))
            ->add('openGraphOverride', OpenGraphOverrideType::class, array('label' => 'Facebook Info'))
            ->add('status', 'choice', array(
                'label' => 'Status',
                'choices' => $this->getStatusChoices(),
                'choices_as_values' => true,
            ))
            ->add('ruleset', CountryAgeRestrictionRulesetType::class, array(
                'label' => 'Restrictions',
            ))
            ->add('testOnly', ChoiceType::class, array(
                'choices' => self::YES_NO,
                'choices_as_values' => true,
                'label' => 'Allow admin testing?',
            ))
            ->add('hidden', CheckboxType::class, array(
                'label' => 'Do not display listing',
            ));

        $builder->add('tags', TextType::class, array(
            'label' => 'Tags',
            'mapped' => false,
            'data' => $this->contest ? $this->tagManager->getConcatenatedTagNames($this->contest) : null,
        ));
    }

    public function getBlockPrefix()
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
