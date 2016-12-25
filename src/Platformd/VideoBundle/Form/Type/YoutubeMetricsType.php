<?php

namespace Platformd\VideoBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class YoutubeMetricsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fromDate', 'date', array(
                'label' => 'From Date:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ))
            ->add('thruDate', 'date', array(
                'label' => 'Thru Date:',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr'   => array(
                    'class' => 'date-picker'
                )
            ))
            ->add('keyWords', 'text', array(
                'label' => 'Key Words',
            ))
        ;
    }

    public function getName()
    {
        return 'platformd_videobundle_youtubemetricstype';
    }
}
