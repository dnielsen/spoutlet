<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Platformd\SpoutletBundle\Entity\Group;
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
                'label' => 'Find Group:'
            ))
            ->add('sites', 'choice', array(
                'label' => 'Select Region:',
                'choices' => MultitenancyManager::getSiteChoices(),
                'empty_value' => 'Select All',
                'required' => false,
            ));
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_groupfindtype';
    }
}
