<?php

namespace Platformd\HtmlWidgetBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Symfony\Component\Form\FormBuilderInterface;

class HtmlWidgetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null)
            ->add('slug', new SlugType(), array('url_prefix' => '/page/{slug}'))
            ->add('content', 'purifiedTextarea', array(
                'attr'  => array('class' => 'ckeditor')
            ))
            ->add('notes', 'textarea')
            ->add('sites', 'entity', array(
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name'
            ))
        ;
    }

    public function getName()
    {
        return 'platformd_html_widget_bundle_html_widget_type';
    }
}
