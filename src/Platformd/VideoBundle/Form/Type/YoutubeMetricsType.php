<?php

namespace Platformd\VideoBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class YoutubeMetricsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fromDate', DateType::class, array(
                'label' => 'From Date:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ))
            ->add('thruDate', DateType::class, array(
                'label' => 'Thru Date:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ))
            ->add('keyWords', TextType::class, array(
                'label' => 'Key Words',
            ))
        ;
    }

    public function getBlockPrefix()
    {
        return 'platformd_videobundle_youtubemetricstype';
    }
}
