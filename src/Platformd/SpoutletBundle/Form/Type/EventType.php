<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;

class EventType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('name', 'text');
        $builder->add('slug', 'text', array(
            'required' => false,
            'label'    => 'URL key(e.g. my-event)',
        ));
    	$builder->add('starts_at', 'datetime');
    	$builder->add('ends_at', 'datetime');
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
