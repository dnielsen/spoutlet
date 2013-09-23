<?php

namespace Platformd\SweepstakesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SweepstakesBundle\Form\Type\SweepstakesQuestionType;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;

class SweepstakesAdminType extends AbstractType
{
    private $sweepstakes;
    private $sweepstakesManager;
    private $tagManager;

    public function __construct($sweepstakes, $sweepstakesManager, $tagManager)
    {
        $this->sweepstakes        = $sweepstakes;
        $this->sweepstakesManager = $sweepstakesManager;
        $this->tagManager         = $tagManager;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        $isSweeps = ($this->sweepstakes->getEventType() == Sweepstakes::SWEEPSTAKES_TYPE_SWEEPSTAKES);

        $builder
            ->add('name', 'text')
            ->add('slug', new SlugType(), array(
                'url_prefix' => ($isSweeps ? '/contest-sweeps/' : '/promocode/'),
            ))
            ->add('sites', 'entity', array(
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'property' => 'name',
            ))
            ->add('timezone', 'gmtTimezone')
            ->add('backgroundImage', new MediaType(), array(
                'image_label'          => 'Background image',
                'image_help'           => 'sweepstakes.admin.form.background_image.help',
                'with_remove_checkbox' => true
            ))
            ->add('officialRules', 'purifiedTextarea', array(
                'attr'     => array('class' => 'ckeditor'),
                'required' => false,
            ))
            ->add('content', 'purifiedTextarea', array(
                'attr'     => array('class' => 'ckeditor'),
                'required' => false,
            ))
            ->add('testOnly', 'choice', array(
                'choices' => array(
                    1 => 'Yes',
                    0 => 'No',
                ),
                'label' => 'Allow admin testing?',
                'help'  => 'sweepstakes.admin.form.test_only.help',
            ))
            ->add('hidden', 'checkbox', array(
                'label' => 'Do not display listing',
            ))
            ->add('startsAt', 'datetime', array(
                'label'  => 'Entry begins',
                'widget' => 'single_text',
                'attr'   => array(
                    'class' => 'datetime-picker',
                )
            ))
            ->add('endsAt', 'datetime', array(
                'label'  => 'Entry ends',
                'widget' => 'single_text',
                'attr'   => array(
                    'class' => 'datetime-picker',
                )
            ))
            ->add('published', 'choice', array(
                'choices' => array(
                    1 => 'status.published',
                    0 => 'status.unpublished'
                ),
                'label' => 'status.choose_status',
            ))
            ->add('tags', 'text', array(
                'label'         => 'Tags',
                'help'          => 'sweepstakes.admin.form.tags.help',
                'required'      => false,
                'property_path' => false,
                'data'          => $this->sweepstakes ? $this->tagManager->getConcatenatedTagNames($this->sweepstakes) : null,
            ))
            ->add('metaDescription', 'text', array(
                'label'    => 'Meta description',
                'required' => false,
                'help'     => 'sweepstakes.admin.form.meta_description.help',
            ))
            ->add('hasOptionalCheckbox', 'checkbox', array(
                'label'    => 'Add checkbox',
                'required' => false,
                'attr'     => array(
                    'class' => 'has-optional-checkbox',
                ),
            ))
            ->add('optionalCheckboxLabel', 'text', array(
                'label' => 'Custom checkbox label',
                'help'  => 'Can be HTML or plain text',
                'attr'  => array(
                    'class' => 'span8',
                ),
            ))
        ;

        if ($isSweeps) {
            $builder
                ->add('externalUrl', null, array(
                    'label' => 'External URL',
                    'help'  => 'sweepstakes.admin.form.slug.help',
                ))
                ->add('group', 'hidden', array(
                    'property_path' => false,
                ))
                ->add('questions', 'collection', array(
                    'type'         => new SweepstakesQuestionType(),
                    'allow_add'    => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'attr'         => array(
                        'class' => 'sweepstakes_question',
                    ),
                ))
            ;
        } else {
            $builder
                ->add('winningCodesFile', 'file', array(
                    'label' => 'Winning Codes CSV',
                    'error_bubbling' => false,
                    'help'  => 'Recommended format: CSV, 1 code per line.'.($this->sweepstakes->getWinningCodesCount() > 0 ? '<br /><br />Codes added: '.$this->sweepstakes->getWinningCodesCount() : ''),
                ))
                ->add('consolationCodesFile', 'file', array(
                    'label' => 'Consolation Codes CSV',
                    'help'  => 'Recommended format: CSV, 1 code per line.'.($this->sweepstakes->getConsolationCodesCount() > 0 ? '<br /><br />Codes added: '.$this->sweepstakes->getConsolationCodesCount() : ''),
                ))
                ->add('affidavit', new MediaType(), array(
                    'image_label' => 'Affidavit Form',
                ))
                ->add('w9form', new MediaType(), array(
                    'image_label' => 'W9 Form',
                ))
                ->add('winnerMessage', 'purifiedTextarea', array(
                    'label' => 'Winner Message',
                    'help' => 'This message will be displayed to users in the event that they have a winning code. Include the following placeholders in links/message body (e.g. &lt;a href="--w9Url--"&gt;w9&lt;/a&gt;): "--w9Url--", "--affidavitUrl--", and also "--contestName--"',
                    'attr' => array('class' => 'ckeditor')
                ))
                ->add('loserMessage', 'purifiedTextarea', array(
                    'label' => 'Loser Message',
                    'help' => 'This message will be displayed to users in the event that they do not have a winning code. To include a "Consolation Code", use the phrase "--code--"" as a placeholder.',
                    'attr' => array('class' => 'ckeditor')
                ))
                ->add('backupLoserMessage', 'purifiedTextarea', array(
                    'label'    => 'Secondary Loser Message',
                    'required' => false,
                    'help'     => 'This message will be displayed to users in the event that they do not have a winning code and there are no "Consolation Codes" left.',
                    'attr'     => array('class' => 'ckeditor')
                ))
            ;
        }
    }

    public function getName()
    {
        return 'sweepstakes';
    }

    public function getDefaultOptions(array $options)
    {
        $options['data_class'] = 'Platformd\SweepstakesBundle\Entity\Sweepstakes';

        return $options;
    }
}
