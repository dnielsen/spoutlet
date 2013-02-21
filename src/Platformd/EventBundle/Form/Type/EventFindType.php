<?php

namespace Platformd\EventBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;

class EventFindType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('eventName', 'text', array(
                'label' => 'Name:'
            ))
            ->add('eventType', 'choice', array(
                'label' => 'Type',
                'choices' => array(
                    'group' => 'Group',
                    'global' => 'Global'
                )
            ))
            ->add('sites', 'choice', array(
                'label' => 'Region:',
                'expanded' => 'true',
                'multiple' => 'true',
                'choices' => MultitenancyManager::getSiteChoices(),
            ))
            ->add('filter', 'choice', array(
                'label' => 'Status',
                'choices' => array(
                    'upcoming' => 'Upcoming Events',
                    'past'     => 'Past Events',
                    'public'   => 'Public Only',
                    'private'  => 'Private Only',
                    'inactive' => 'Inactive Events'
                ),
                'empty_value' => 'None',
                'required' => false,
            ));
    }

    public function getName()
    {
        return 'platformd_eventbundle_eventfindtype';
    }

}
