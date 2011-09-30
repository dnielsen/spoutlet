<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class EventType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('name', 'text');
    	$builder->add('ready', 'checkbox', array('required' => false));
    	$builder->add('published', 'checkbox', array('required' => false));
    	$builder->add('starts_at', 'datetime');
    	$builder->add('ends_at', 'datetime');
    	$builder->add('city', 'text');
    	$builder->add('metro_area', 'entity', array('class' => 'Platformd\\SpoutletBundle\\Entity\\MetroArea', 'property' => 'label'));
    	$builder->add('country', 'text');
    	$builder->add('content', 'textarea');
        $builder->add('hosted_by', 'text');
        $builder->add('game', 'text');
    }

    public function getName()
    {
        return 'event';
    }
}
