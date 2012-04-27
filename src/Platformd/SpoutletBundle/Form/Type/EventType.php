<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;
use Platformd\SpoutletBundle\Form\Type\SlugType;

class EventType extends AbstractType
{
    protected $nonRequiredFields = array(
        'slug',
        'starts_at',
        'ends_at',
        'location',
        'city',
        'country',
        'hosted_by',
        'game',
        'url_redirect',
        'bannerImageFile',
        'description',
        'timezone',
    );

    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('name', 'textarea');
        $builder->add('slug', new SlugType());

        $this->createStartsAtField($builder);
        $this->createEndsAtField($builder);
        $builder->add('timezone', 'timezone');

    	$builder->add('city', 'text');
    	$builder->add('country', 'text');
    	$builder->add('content', 'textarea');
        $builder->add('hosted_by', 'text');
        $builder->add('game', 'text');
        $builder->add('url_redirect', 'text');
        $builder->add('location', 'text');
        $builder->add('bannerImageFile', 'file');
        $builder->add('locale', new SiteChoiceType());

        $this->unrequireFields($builder);
    }

    /**
     * Utility function to properly mark fields as required/not-required
     *
     * @param \Symfony\Component\Form\FormBuilder $builder
     */
    protected function unrequireFields(FormBuilder $builder)
    {
        foreach ($this->nonRequiredFields as $name) {
            if ($builder->has($name)) {
                $builder->get($name)->setRequired(false);
            }
        }
    }

    public function getName()
    {
        return 'event';
    }

    protected function createStartsAtField(FormBuilder $builder)
    {
        return $builder->add('starts_at', 'datetime', array(
            'widget' => 'single_text',
            'attr'   => array(
                'class' => 'datetime-picker',
            )
        ));
    }

    protected function createEndsAtField(FormBuilder $builder)
    {
        return $builder->add('ends_at', 'datetime', array(
            'widget' => 'single_text',
            'attr'   => array(
                'class' => 'datetime-picker',
            )
        ));
    }
}
