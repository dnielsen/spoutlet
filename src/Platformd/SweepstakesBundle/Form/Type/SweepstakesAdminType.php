<?php

namespace Platformd\SweepstakesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\SpoutletBundle\Form\Type\EventType;
use Platformd\SpoutletBundle\Form\Type\CountryAgeRestrictionRulesetType;

class SweepstakesAdminType extends EventType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('name', 'textarea')
            ->add('slug', new SlugType(), array(
                'url_prefix' => '/sweepstakes/'
            ))
            ->add('externalUrl', null, array(
                'label' => 'External URL',
                'help' => '(Optional) If filled in, this URL will override the destination of any links that would normally point to this Sweepstake page.',
            ))
            ->add('sites', 'entity', array(
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'property' => 'name',
            ))
            ->add('timezone', 'gmtTimezone')
            ->add('bannerImageFile', 'file')
            ->add('generalImageFile', 'file', array(
                'label' => 'General image',
            ))
            ->add('ruleset', new CountryAgeRestrictionRulesetType(), array(
                'label' => 'Restrictions',
            ))
            ->add('officialRules', 'purifiedTextarea', array(
                'attr' => array('class' => 'ckeditor')
            ))
            ->add('game', null, array(
                'empty_value' => 'N/A',
            ))
            ->add('content', 'purifiedTextarea', array(
                'attr' => array('class' => 'ckeditor')
            ))
            ->add('testOnly', 'choice', array(
                'choices' => array(
                    1 => 'Yes',
                    0 => 'No',
                ),
                'label' => 'Allow admin testing?',
                'help'  => 'This allows admins to still test the operation of the sweepstakes IF it is unpublished',
            ))
            ->add('hidden', 'checkbox', array(
                'label' => 'Do Not Display Listing',
            ));

        $this->createStartsAtField($builder);
        $this->createEndsAtField($builder);

        $builder->add('group', 'hidden', array(
            'property_path' => false,
        ));
    }


    public function getName()
    {
        return 'sweepstakes';
    }

    public function getDefaultOptions(array $options)
    {
        $options['data_class'] = 'Platformd\SweepstakesBundle\Entity\Sweepstakes';

        return $options;
    }
}
