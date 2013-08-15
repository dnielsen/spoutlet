<?php

namespace Platformd\SweepstakesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SweepstakesBundle\Form\Type\SweepstakesQuestionType;

class SweepstakesAdminType extends AbstractType
{
    private $sweepstakes;
    private $tagManager;

    public function __construct($sweepstakes, $tagManager)
    {
        $this->sweepstakes  = $sweepstakes;
        $this->tagManager   = $tagManager;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('name', 'text')
            ->add('slug', new SlugType(), array(
                'url_prefix' => '/sweepstakes/'
            ))
            ->add('externalUrl', null, array(
                'label' => 'External URL',
                'help'  => 'sweepstakes.admin.form.slug.help',
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
                'attr' => array('class' => 'ckeditor')
            ))
            ->add('content', 'purifiedTextarea', array(
                'attr' => array('class' => 'ckeditor')
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
            ->add('group', 'hidden', array(
                'property_path' => false,
            ))
            ->add('tags', 'text', array(
                'label'         => 'Tags',
                'help'          => 'sweepstakes.admin.form.tags.help',
                'property_path' => false,
                'data'          => $this->sweepstakes ? $this->tagManager->getConcatenatedTagNames($this->sweepstakes) : null,
            ))
            ->add('metaDescription', 'text', array(
                'label' => 'Meta description',
                'help' => 'sweepstakes.admin.form.meta_description.help',
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
