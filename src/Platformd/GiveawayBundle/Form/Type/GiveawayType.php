<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\GiveawayBundle\Entity\Giveaway;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\SpoutletBundle\Form\Type\CountryAgeRestrictionRulesetType;

class GiveawayType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('name', 'textarea');
        $builder->add('slug', new SlugType(), array('label' => 'URL'));
    	$builder->add('content', 'purifiedTextarea', array(
            'label' => 'Description',
            'attr' => array(
                'class' => 'ckeditor'
            )
        ));
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
        $builder->add('sites', 'entity', array(
            'class'    => 'SpoutletBundle:Site',
            'multiple' => true,
            'expanded' => true,
            'property' => 'name',
        ));
        $builder->add('externalUrl', null, array('label' => 'External URL', 'help' => '(Optional) If filled in, this URL will override the destination of any links that would normally point to the GiveAway page.'));
        $builder->add('ruleset', new CountryAgeRestrictionRulesetType(), array('label' => 'Restrictions'));
        $builder->add('testOnly', 'choice', array(
            'choices' => array(
                1 => 'Yes',
                0 => 'No',
            ),
            'label' => 'Allow admin testing?',
            'help'  => 'This allows admins to still test the operation of the giveaway IF it is unpublished',
        ));
        $builder->add('displayRemainingKeysNumber', null, array('label' => 'Show key count'));
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
