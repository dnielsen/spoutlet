<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;

class DiscussionFindType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('discussionName', 'text', array(
                'label' => 'Name:'
            ))
            ->add('deleted', 'choice', array(
                'label' => 'Status:',
                'choices' => array(
                    '0' => 'Active',
                    '1' => 'Inactive'
                ),
                'empty_value' => 'Select All',
                'required' => false,
            ))
            ->add('sites', 'choice', array(
                'label' => 'Region:',
                'expanded' => 'true',
                'multiple' => 'true',
                'choices' => MultitenancyManager::getSiteChoices(),
            ))
            ->add('from', 'date', array(
                'label' => 'Start Date:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ))
            ->add('thru', 'date', array(
                'label' => 'End Date:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ));
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_discussionfindtype';
    }

}
