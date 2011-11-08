<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class GiveawayType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('name', 'text');
    	$builder->add('content', 'textarea');
        $builder->add('bannerImageFile', 'file');
        $builder->add('redemptionInstructions', 'textarea');
        $builder->add('status', 'choice', array(
            'choices' => array(
                'disabled' => 'platformd.giveaway.status.disabled',
                'inactive' => 'platformd.giveaway.status.inactive',
                'active'   => 'platformd.giveaway.status.active',
            ),
            'empty_value' => 'platformd.giveaway.status.blank_value',
        ));
    }

    public function getName()
    {
        return 'giveaway';
    }

    public function getDefaultOptions(array $options)
    {
        $options = parent::getDefaultOptions($options);

        $options['data_class'] = 'Platformd\GiveawayBundle\Entity\Giveaway';

        return $options;
    }
}
