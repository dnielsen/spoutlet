<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Platformd\SpoutletBundle\Form\DataTransformer\CsvToRsvpCodeTransformer;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
            ->add('name', null, [
                'label' => 'Name',
            ])
            ->add('content', PurifiedTextareaType::class, array(
                'label' => 'Content',
                'attr' => array('class' => 'ckeditor'),
                'required' => false,
            ))
            ->add('background', MediaType::class, array(
                'image_label' => 'Custom background',
                'image_help' => 'Recommended format: 1920x988px, .jpg.',
            ))
            ->add('codeRequired', CheckboxType::class, array(
                'required' => false,
                'label' => 'Code Required?'
            ))
            ->add('published', ChoiceType::class, array(
                'choices' => self::PUBLISHED,
                'choices_as_values' => true,
                'label' => 'Status',
            ))
            ->add('sites', EntityType::class, array(
                'label' => 'Sites',
                'class' => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
            ))
            ->add('codes', FileType::class, array(
                'label' => 'Codes',
                'required' => false,
//                'help' => 'Recommended format: CSV, 1 code per line.'.($builder->getData()->getCodes() ? '<br /><br />Codes added: '.count($builder->getData()->getCodes()) : ''),
            ))
            ->add('slug', SlugType::class, array(
                'url_prefix' => '/rsvp/{slug}',
            ))
            ->add('successMessage', TextType::class, array(
                'label' => 'Success Message',
            ));
        $builder->get('codes')->addViewTransformer(new CsvToRsvpCodeTransformer());
    }

    public function getBlockPrefix()
    {
        return 'rsvp';
    }
}
