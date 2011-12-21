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
    	$builder->add('starts_at', 'date', array(
            'widget' => 'single_text',
            'format' => 'MM/dd/YYYY',
            'attr'   => array(
                'class' => 'date-picker',
            )
        ));
    	$builder->add('ends_at', 'date', array(
            'widget' => 'single_text',
            'format' => 'MM/dd/YYYY',
            'attr'   => array(
                'class' => 'date-picker',
            )
        ));
    	$builder->add('city', 'text');
    	$builder->add('country', 'text');
    	$builder->add('content', 'textarea');
        $builder->add('hosted_by', 'text');
        $builder->add('game', 'text');
        $builder->add('location', 'text');
        $builder->add('bannerImageFile', 'file');
        $builder->add('locale', new SiteChoiceType());
    }

    public function getName()
    {
        return 'event';
    }
}
