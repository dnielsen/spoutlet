<?php

namespace Platformd\GroupBundle\Form\Type;

use Platformd\GroupBundle\Entity\Group;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;


class GroupFindType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('groupName', 'text', array(
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
            ->add('category', 'choice', array(
                'label' => 'Category:',
                'choices' => array(
                    'location' => 'Location',
                    'topic' => 'Topic'
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
            ->add('startDate', 'date', array(
                'label' => 'Start Date:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ))
            ->add('endDate', 'date', array(
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
        return 'platformd_groupbundle_groupfindtype';
    }
}
