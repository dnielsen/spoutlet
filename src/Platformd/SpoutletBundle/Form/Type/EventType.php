<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;
use Platformd\SpoutletBundle\Form\Type\SlugType;

class EventType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('name', 'text');
        $builder->add('slug', new SlugType());

        $this->createStartsAtField($builder);
        $this->createEndsAtField($builder);
    	
    	$builder->add('city', 'text');
    	$builder->add('country', 'text');
    	$builder->add('content', 'textarea');
        $builder->add('hosted_by', 'text');
        $builder->add('game', 'text');
        $builder->add('url_redirect', 'text',array( 'required' => false,));
        $builder->add('location', 'text');
        $builder->add('bannerImageFile', 'file');
        $builder->add('locale', new SiteChoiceType());
    }

    public function getName()
    {
        return 'event';
    }

    protected function createStartsAtField(FormBuilder $builder)
    {
        return $builder->add('starts_at', 'date', array(
           'widget' => 'single_text',
           'format' => 'MM/dd/yyyy',
           'attr'   => array(
               'class' => 'date-picker',
           )
       ));
    }

    protected function createEndsAtField(FormBuilder $builder)
    {
        return $builder->add('ends_at', 'date', array(
            'widget' => 'single_text',
            'format' => 'MM/dd/yyyy',
            'attr'   => array(
                'class' => 'date-picker',
            )
        ));
    }
}
