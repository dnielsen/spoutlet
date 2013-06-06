<?php

namespace Platformd\HtmlWidgetBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\HtmlWidgetBundle\Entity\HtmlWidget;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;
use Platformd\SpoutletBundle\Form\Type\SlugType;


class HtmlWidgetType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
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
                'property' => 'name'
            ))
        ;
    }

    public function getName()
    {
        return 'platformd_html_widget_bundle_html_widget_type';
    }
}
