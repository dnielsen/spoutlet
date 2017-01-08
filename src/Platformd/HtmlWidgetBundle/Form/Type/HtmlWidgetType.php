<?php

namespace Platformd\HtmlWidgetBundle\Form\Type;

use Platformd\SpoutletBundle\Form\Type\PurifiedTextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class HtmlWidgetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, [
                'label' => 'Name',
            ])
            ->add('slug', SlugType::class, array('url_prefix' => '/page/{slug}'))
            ->add('content', PurifiedTextareaType::class, array(
                'label' => 'Content',
                'attr'  => array('class' => 'ckeditor')
            ))
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
            ])
            ->add('sites', EntityType::class, array(
                'label' => 'Sites',
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name'
            ))
        ;
    }

    public function getBlockPrefix()
    {
        return 'platformd_html_widget_bundle_html_widget_type';
    }
}
