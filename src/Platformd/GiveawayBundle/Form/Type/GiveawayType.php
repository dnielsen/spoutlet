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
        $builder->add('name', 'textarea');
        $builder->add('slug', new SlugType(), array('label' => 'URL'));
    	$builder->add('content', 'textarea');
    	$builder->add('giveawayType', 'choice', array(
    			'choices' => Giveaway::getTypeChoices(),
                'label' => 'Giveaway Type'
    	));
        $builder->add('bannerImageFile', 'file');
        $builder->add('redemptionInstructionsArray', 'collection', array(
            'type' => 'textarea',
            'label' => 'Redemption Instructions'
        ));
        $builder->add('status', 'choice', array(
            'choices' => Giveaway::getValidStatusesMap(),
            'empty_value' => 'platformd.giveaway.status.blank_value',
        ));
        $builder->add('game', null, array('empty_value' => 'N/A'));
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
