<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\GiveawayBundle\Entity\Giveaway;

class GiveawayType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('name', 'text');
        $builder->add('slug', 'text', array(
            'required' => false,
            'label'    => 'URL key(e.g. my-event)',
        ));
    	$builder->add('content', 'textarea');
        $builder->add('bannerImageFile', 'file');
        $builder->add('redemptionInstructionsArray', 'collection', array(
            'type' => 'text',
        ));
        $builder->add('status', 'choice', array(
            'choices' => Giveaway::getValidStatusesMap(),
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
