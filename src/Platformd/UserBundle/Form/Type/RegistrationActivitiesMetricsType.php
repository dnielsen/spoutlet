<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

use Doctrine\ORM\EntityRepository;

use Platformd\UserBundle\Entity\RegistrationSource;

class RegistrationActivitiesMetricsType extends AbstractType
{
    static $choices = array(
        0 => 'All Activities',
        RegistrationSource::REGISTRATION_SOURCE_TYPE_GIVEAWAY    => 'Giveaways',
        RegistrationSource::REGISTRATION_SOURCE_TYPE_CONTEST     => 'Contests',
        RegistrationSource::REGISTRATION_SOURCE_TYPE_SWEEPSTAKES => 'Sweepstakes',
        RegistrationSource::REGISTRATION_SOURCE_TYPE_DEAL        => 'Deals',
        RegistrationSource::REGISTRATION_SOURCE_TYPE_NEWS        => 'News',
        RegistrationSource::REGISTRATION_SOURCE_TYPE_HOMEPAGE    => 'Homepage',
        RegistrationSource::REGISTRATION_SOURCE_TYPE_GROUP       => 'Groups',
        RegistrationSource::REGISTRATION_SOURCE_TYPE_ARP         => 'ARP',
        RegistrationSource::REGISTRATION_SOURCE_TYPE_FORUMS      => 'Forums',
        RegistrationSource::REGISTRATION_SOURCE_TYPE_VIDEOS      => 'Videos',
        RegistrationSource::REGISTRATION_SOURCE_TYPE_IMAGES      => 'Images',
        RegistrationSource::REGISTRATION_SOURCE_TYPE_OTHER       => 'Other',
    );

    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('country', 'entity', array(
                'class'         => 'SpoutletBundle:Country',
                'empty_value'   => 'All Countries',
                'property'      => 'name',
                'attr'    => array(
                    'class' => 'base-select',
                ),
            ))
            ->add('country_activity_type', 'choice', array(
                'choices' => self::$choices,
                'attr'    => array(
                    'class'         => 'activity-type country-activity-type',
                    'data-grouping' => 'country',
                ),
            ))
            ->add('country_activity_id', 'choice', array(
                'choices'     => array(),
                'empty_value' => 'Choose an Activity Type',
                'attr'        => array(
                    'class' => 'country-activity-id',
                ),
            ))
            ->add('from_date_country', 'date', array(
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
            )))
            ->add('to_date_country', 'date', array(
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
            )))
            ->add('region', 'entity', array(
                'class'         => 'SpoutletBundle:Region',
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('r')
                        ->where('r.isMetricsRegion = true');
                },
                'empty_value'   => 'All Regions',
                'property'      => 'name',
                'attr'    => array(
                    'class' => 'base-select',
                ),
            ))
            ->add('region_activity_type', 'choice', array(
                'choices' => self::$choices,
                'attr'    => array(
                    'class'         => 'activity-type region-activity-type',
                    'data-grouping' => 'region',
                ),
            ))
            ->add('region_activity_id', 'choice', array(
                'choices'     => array(),
                'empty_value' => 'Choose an Activity Type',
                'attr'        => array(
                    'class' => 'region-activity-id',
                ),
            ))
            ->add('from_date_region', 'date', array(
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
            )))
            ->add('to_date_region', 'date', array(
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
            )))
            ->add('currentSettings', 'hidden')
        ;
    }

    public function getName()
    {
        return 'user_registration_activities_metrics';
    }
}

