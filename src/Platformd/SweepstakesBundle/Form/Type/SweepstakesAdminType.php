<?php

namespace Platformd\SweepstakesBundle\Form\Type;

use Platformd\SpoutletBundle\Form\Type\GmtOffsetTimezoneType;
use Platformd\SpoutletBundle\Form\Type\PurifiedTextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SweepstakesAdminType extends AbstractType
{
    const YES_NO = [
        'Yes' => 1,
        'No' => 0,
    ];

    const PUBLISHED = [
        'status.published' => 1,
        'status.unpublished' => 0,
    ];

    private $sweepstakes;
    private $tagManager;

    public function __construct($sweepstakes, $tagManager)
    {
        $this->sweepstakes = $sweepstakes;
        $this->tagManager = $tagManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isSweeps = ($this->sweepstakes->getEventType() === Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES);

        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
            ])
            ->add('slug', SlugType::class, array(
                'label' => 'URL string -' . ($isSweeps ? '/contest-sweeps/' : '/promocode/'),
            ))
            ->add('sites', EntityType::class, array(
                'label' => 'Sites',
                'class' => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
            ))
            ->add('timezone', GmtOffsetTimezoneType::class, [
                'label' => 'Timezone',
            ])
            ->add('backgroundImage', new MediaType(), array(
                'image_label' => 'Background image',
                'image_help' => 'sweepstakes.admin.form.background_image.help',
                'with_remove_checkbox' => true
            ))
            ->add('officialRules', PurifiedTextareaType::class, array(
                'attr' => array('class' => 'ckeditor'),
                'required' => false,
            ))
            ->add('content', PurifiedTextareaType::class, array(
                'attr' => array('class' => 'ckeditor'),
                'required' => false,
            ))
            ->add('testOnly', ChoiceType::class, array(
                'choices' => self::YES_NO,
                'choices_as_values' => true,
                'label' => 'Allow admin testing?',
            ))
            ->add('hidden', CheckboxType::class, array(
                'label' => 'Do not display listing',
            ))
            ->add('startsAt', DateTimeType::class, array(
                'label' => 'Entry begins',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker',
                )
            ))
            ->add('endsAt', DateTimeType::class, array(
                'label' => 'Entry ends',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker',
                )
            ))
            ->add('published', ChoiceType::class, array(
                'choices' => self::PUBLISHED,
                'label' => 'status.choose_status',
                'choices_as_values' => true,
            ))
            ->add('tags', TextType::class, array(
                'label' => 'Tags',
//                'help' => 'sweepstakes.admin.form.tags.help',
                'required' => false,
                'mapped' => false,
                'data' => $this->sweepstakes ? $this->tagManager->getConcatenatedTagNames($this->sweepstakes) : null,
            ))
            ->add('metaDescription', TextType::class, array(
                'label' => 'Meta description',
                'required' => false,
//                'help' => 'sweepstakes.admin.form.meta_description.help',
            ))
            ->add('hasOptionalCheckbox', CheckboxType::class, array(
                'label' => 'Add checkbox',
                'required' => false,
                'attr' => array(
                    'class' => 'has-optional-checkbox',
                ),
            ))
            ->add('optionalCheckboxLabel', TextType::class, array(
                'label' => 'Custom checkbox label',
//                'help' => 'Can be HTML or plain text',
                'attr' => array(
                    'class' => 'span8',
                ),
            ));

        if ($isSweeps) {
            $builder
                ->add('externalUrl', null, array(
                    'label' => 'External URL',
                ))
                ->add('group', HiddenType::class, array(
                    'mapped' => false,
                ))
                ->add('questions', CollectionType::class, array(
                    'entry_type' => new SweepstakesQuestionType(),
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'attr' => array(
                        'class' => 'sweepstakes_question',
                    ),
                ));
        } else {
            $builder
                ->add('winningCodesFile', FileType::class, array(
                    'label' => 'Winning Codes CSV',
                    'error_bubbling' => false,
//                    'help' => 'Recommended format: CSV, 1 code per line.' . ($this->sweepstakes->getWinningCodesCount() > 0 ? '<br /><br />Codes added: ' . $this->sweepstakes->getWinningCodesCount() : ''),
                ))
                ->add('consolationCodesFile', FileType::class, array(
                    'label' => 'Consolation Codes CSV',
//                    'help' => 'Recommended format: CSV, 1 code per line.' . ($this->sweepstakes->getConsolationCodesCount() > 0 ? '<br /><br />Codes added: ' . $this->sweepstakes->getConsolationCodesCount() : ''),
                ))
                ->add('affidavit', new MediaType(), array(
                    'image_label' => 'Affidavit Form',
                ))
                ->add('w9form', new MediaType(), array(
                    'image_label' => 'W9 Form',
                ))
                ->add('winnerMessage', PurifiedTextareaType::class, array(
                    'label' => 'Winner Message',
                    'attr' => array('class' => 'ckeditor')
                ))
                ->add('loserMessage', PurifiedTextareaType::class, array(
                    'label' => 'Loser Message',
                    'attr' => array('class' => 'ckeditor')
                ))
                ->add('backupLoserMessage', PurifiedTextareaType::class, array(
                    'label' => 'Secondary Loser Message',
                    'required' => false,
                    'attr' => array('class' => 'ckeditor')
                ));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Sweepstakes::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'sweepstakes';
    }
}
