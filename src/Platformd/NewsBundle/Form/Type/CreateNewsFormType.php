<?php

namespace Platformd\NewsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;

class CreateNewsFormType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('body')
            ->add('locale', new SiteChoiceType())
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        
        return 'news';
    }

}