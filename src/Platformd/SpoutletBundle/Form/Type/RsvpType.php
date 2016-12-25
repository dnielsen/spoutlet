<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\SpoutletBundle\Form\DataTransformer\CsvToRsvpCodeTransformer;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\Form\FormBuilderInterface;

class RsvpType extends AbstractType
{
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
//                'help' => 'Recommended format: CSV, 1 code per line.'.($builder->getData()->getCodes() ? '<br /><br />Codes added: '.count($builder->getData()->getCodes()) : ''),
            ))
            ->add('slug', new SlugType(), array(
                'url_prefix' => '/rsvp/{slug}',
            ))
            ->add('successMessage', 'text', array(
                'label' => 'Success Message',
            ))
        ;
        $builder->get('codes')->addModelTransformer(new CsvToRsvpCodeTransformer());
    }

    public function getName()
    {
        return 'rsvp';
    }
}

