<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Platformd\SpoutletBundle\Form\Type\SlugType;

class GiveawayType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('name', 'text');
        $builder->add('slug', new SlugType());
    	$builder->add('content', 'textarea');
    	$builder->add('giveawayType', 'choice', array(
    			'choices' => Giveaway::getTypeChoices(),
    	));
        $builder->add('bannerImageFile', 'file');
        $builder->add('redemptionInstructionsArray', 'collection', array(
            'type' => 'text',
        ));
        $builder->add('status', 'choice', array(
            'choices' => Giveaway::getValidStatusesMap(),
            'empty_value' => 'platformd.giveaway.status.blank_value',
        ));
        $builder->add('locale', new SiteChoiceType());
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
