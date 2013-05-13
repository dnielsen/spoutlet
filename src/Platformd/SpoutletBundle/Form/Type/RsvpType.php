<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Form\DataTransformer\CsvToRsvpCodeTransformer;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SpoutletBundle\Form\Type\SlugType;

class RsvpType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
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
                'choices' => array(
                    1 => 'Published',
                    0 => 'Unpublished',
                ),
                'label' => 'Status',
            ))
            ->add('sites', 'entity', array(
                'class' => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'property' => 'name',
            ))
            ->add('codes', 'file', array(
                'required' => false,
                'help' => 'Recommended format: CSV, 1 code per line.'.($builder->getData()->getCodes() ? '<br /><br />Codes added: '.count($builder->getData()->getCodes()) : ''),
            ))
            ->add('slug', new SlugType(), array(
                'url_prefix' => '/rsvp/{slug}',
            ))
            ->add('successMessage', 'text', array(
                'label' => 'Success Message',
                'help' => 'This is the message that will be displayed to users upon registering successfully."'
            ))
        ;
        $builder->get('codes')->appendClientTransformer(new CsvToRsvpCodeTransformer);
    }

    public function getName()
    {
        return 'rsvp';
    }
}

