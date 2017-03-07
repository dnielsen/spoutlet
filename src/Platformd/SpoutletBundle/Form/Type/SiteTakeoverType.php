<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;

class SiteTakeoverType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', null, [
                'label' => 'Title',
            ])
            ->add('startsAt', DateType::class, array(
                'widget' => 'single_text',
                'attr'   => array(
                    'class' => 'datetime-picker'
                ),
                'format' => 'yyyy-MM-dd HH:mm',
                'label'  => 'Starts At'
            ))
            ->add('endsAt', DateType::class, array(
                'widget' => 'single_text',
                'attr'   => array(
                    'class' => 'datetime-picker'
                ),
                'format' => 'yyyy-MM-dd HH:mm',
                'label'  => 'Ends At'
            ))
            ->add('timezone', TimezoneType::class, array('label' => 'Timezone'))
            ->add('sites', EntityType::class, array(
                'label' => 'Sites',
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
            ))
            ->add('content', TextareaType::class, array(
                'label' => 'Content',
                'attr'  => array('class' => 'ckeditor'),
            ));
    }

    public function getBlockPrefix()
    {
        return 'platformd_spoutletbundle_sitetakeovertype';
    }
}
