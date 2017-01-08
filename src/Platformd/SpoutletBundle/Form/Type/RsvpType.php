<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\SpoutletBundle\Form\DataTransformer\CsvToRsvpCodeTransformer;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\Form\FormBuilderInterface;

class RsvpType extends AbstractType
{
    const PUBLISHED = [
        'Published' => 1,
        'Unpublished' => 0,
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('content', 'purifiedTextarea',array(
                'attr' => array('class' => 'ckeditor'),
                'required' => false,
            ))
            ->add('background', new MediaType, array(
                'image_label' => 'Custom background',
                'image_help' => 'Recommended format: 1920x988px, .jpg.',
            ))
            ->add('codeRequired', 'checkbox', array(
                'required' => false,
                'label' => 'Code Required?'
            ))
            ->add('published', 'choice', array(
                'choices' => self::PUBLISHED,
                'choices_as_values' => true,
                'label' => 'Status',
            ))
            ->add('sites', 'entity', array(
                'class' => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
            ))
            ->add('codes', 'file', array(
                'required' => false,
//                'help' => 'Recommended format: CSV, 1 code per line.'.($builder->getData()->getCodes() ? '<br /><br />Codes added: '.count($builder->getData()->getCodes()) : ''),
            ))
            ->add('slug', new SlugType(), array(
                'url_prefix' => '/rsvp/{slug}',
            ))
            ->add('successMessage', 'text', array(
                'label' => 'Success Message',
            ))
        ;
        $builder->get('codes')->addViewTransformer(new CsvToRsvpCodeTransformer());
    }

    public function getName()
    {
        return 'rsvp';
    }
}

