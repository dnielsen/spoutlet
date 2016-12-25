<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\SpoutletBundle\Form\DataTransformer\RsvpCodeTransformer;
use Symfony\Component\Form\FormBuilderInterface;

class RsvpCodeType extends AbstractType
{
    private $transformer;

    public function __construct(RsvpCodeTransformer $transformer)
    {
        $this->transformer = $transformer;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->appendClientTransformer($this->transformer)
        ;
    }

    public function getParent()
    {
        return 'text';
    }

    public function getName()
    {
        return 'rsvp_code';
    }
}

