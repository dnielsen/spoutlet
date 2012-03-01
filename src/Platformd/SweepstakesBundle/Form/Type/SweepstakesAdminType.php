<?php

namespace Platformd\SweepstakesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\SpoutletBundle\Form\Type\EventType;

class SweepstakesAdminType extends EventType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('name', 'textarea');
        $builder->add('slug', new SlugType());
        $builder->add('locale', new SiteChoiceType());

        $this->createStartsAtField($builder);
        $this->createEndsAtField($builder);
        $builder->add('timezone', 'timezone');

        $builder->add('bannerImageFile', 'file');
        $builder->add('generalImageFile', 'file', array(
            'label' => 'General image'
        ));
        $builder->add('allowedCountries', 'country', array(
            'multiple' => true,
            'expanded' => false,
        ));
        $builder->add('minimumAgeRequirement', 'number', array(
            'label' => 'Age requirement',
        ));

        $builder->add('officialRules', 'textarea');

    	$builder->add('content', 'textarea');
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
