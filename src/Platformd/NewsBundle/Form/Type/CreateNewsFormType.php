<?php

namespace Platformd\NewsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class CreateNewsFormType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('body');
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        
        return 'news';
    }

}