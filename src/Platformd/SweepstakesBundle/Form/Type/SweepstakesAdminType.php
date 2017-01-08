<?php

namespace Platformd\SweepstakesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
    private $sweepstakesManager;
    private $tagManager;

    public function __construct($sweepstakes, $sweepstakesManager, $tagManager)
    {
        $this->sweepstakes = $sweepstakes;
        $this->sweepstakesManager = $sweepstakesManager;
        $this->tagManager = $tagManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isSweeps = ($this->sweepstakes->getEventType() === Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES);

        $builder
            ->add('name', 'text')
            ->add('slug', new SlugType(), array(
                'url_prefix' => ($isSweeps ? '/contest-sweeps/' : '/promocode/'),
            ))
            ->add('sites', 'entity', array(
                'class' => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
            ))
            ->add('timezone', 'gmtTimezone')
            ->add('backgroundImage', new MediaType(), array(
                'image_label' => 'Background image',
                'image_help' => 'sweepstakes.admin.form.background_image.help',
                'with_remove_checkbox' => true
            ))
            ->add('officialRules', 'purifiedTextarea', array(
                'attr' => array('class' => 'ckeditor'),
                'required' => false,
            ))
            ->add('content', 'purifiedTextarea', array(
                'attr' => array('class' => 'ckeditor'),
                'required' => false,
            ))
            ->add('testOnly', 'choice', array(
                'choices' => self::YES_NO,
                'choices_as_values' => true,
                'label' => 'Allow admin testing?',
            ))
            ->add('hidden', 'checkbox', array(
                'label' => 'Do not display listing',
            ))
            ->add('startsAt', 'datetime', array(
                'label' => 'Entry begins',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker',
                )
            ))
            ->add('endsAt', 'datetime', array(
                'label' => 'Entry ends',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker',
                )
            ))
            ->add('published', 'choice', array(
                'choices' => self::PUBLISHED,
                'label' => 'status.choose_status',
            ))
            ->add('tags', 'text', array(
                'label' => 'Tags',
//                'help' => 'sweepstakes.admin.form.tags.help',
                'required' => false,
                'mapped' => false,
                'data' => $this->sweepstakes ? $this->tagManager->getConcatenatedTagNames($this->sweepstakes) : null,
            ))
            ->add('metaDescription', 'text', array(
                'label' => 'Meta description',
                'required' => false,
//                'help' => 'sweepstakes.admin.form.meta_description.help',
            ))
            ->add('hasOptionalCheckbox', 'checkbox', array(
                'label' => 'Add checkbox',
                'required' => false,
                'attr' => array(
                    'class' => 'has-optional-checkbox',
                ),
            ))
            ->add('optionalCheckboxLabel', 'text', array(
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
                ->add('group', 'hidden', array(
                    'mapped' => false,
                ))
                ->add('questions', 'collection', array(
                    'type' => new SweepstakesQuestionType(),
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'attr' => array(
                        'class' => 'sweepstakes_question',
                    ),
                ));
        } else {
            $builder
                ->add('winningCodesFile', 'file', array(
                    'label' => 'Winning Codes CSV',
                    'error_bubbling' => false,
//                    'help' => 'Recommended format: CSV, 1 code per line.' . ($this->sweepstakes->getWinningCodesCount() > 0 ? '<br /><br />Codes added: ' . $this->sweepstakes->getWinningCodesCount() : ''),
                ))
                ->add('consolationCodesFile', 'file', array(
                    'label' => 'Consolation Codes CSV',
//                    'help' => 'Recommended format: CSV, 1 code per line.' . ($this->sweepstakes->getConsolationCodesCount() > 0 ? '<br /><br />Codes added: ' . $this->sweepstakes->getConsolationCodesCount() : ''),
                ))
                ->add('affidavit', new MediaType(), array(
                    'image_label' => 'Affidavit Form',
                ))
                ->add('w9form', new MediaType(), array(
                    'image_label' => 'W9 Form',
                ))
                ->add('winnerMessage', 'purifiedTextarea', array(
                    'label' => 'Winner Message',
                    'attr' => array('class' => 'ckeditor')
                ))
                ->add('loserMessage', 'purifiedTextarea', array(
                    'label' => 'Loser Message',
                    'attr' => array('class' => 'ckeditor')
                ))
                ->add('backupLoserMessage', 'purifiedTextarea', array(
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

    public function getName()
    {
        return 'sweepstakes';
    }
}
