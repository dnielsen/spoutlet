<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Form\DataTransformer\RsvpCodeTransformer;

class RsvpCodeType extends AbstractType
{
    private $transformer;

    public function __construct(RsvpCodeTransformer $transformer)
    {
        $this->transformer = $transformer;
    }
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->appendClientTransformer($this->transformer)
        ;
    }

    public function getParent(array $options)
    {
        return 'text';
    }

    public function getName()
    {
        return 'rsvp_code';
    }
}

