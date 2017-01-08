<?php

namespace Platformd\EventBundle\Form\Type;

use Platformd\SpoutletBundle\Form\Type\PurifiedTextareaType;
use Symfony\Component\Form\AbstractType;

use Platformd\EventBundle\Entity\Event,
    Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    const ONLINE = [
        'platformd.event.form.choice.physical_event' => 0,
        'platformd.event.form.choice.online_event' => 1,
    ];

    const REGISTRATION_OPTIONS = [
        'platformd.event.registration.enabled' => Event::REGISTRATION_ENABLED,
        'platformd.event.registration.disabled' => Event::REGISTRATION_DISABLED,
        'platformd.event.registration.3rdparty' => Event::REGISTRATION_3RD_PARTY,
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'platformd.event.form.name',
            ])
            ->add('bannerImage', MediaType::class, [
                'image_label' => 'platformd.event.form.banner_image',
                'image_help' => 'platformd.event.form.help.banner_image',
                'required' => false
            ])
            ->add('content', PurifiedTextareaType::class, array(
                'label' => 'platformd.event.form.description',
                'attr' => array(
                    'class' => 'ckeditor'
                )
            ))
            ->add('online', ChoiceType::class, array(
                'choices' => self::ONLINE,
                'choices_as_values' => true,
                'expanded' => true,
                'label' => 'platformd.event.form.event_type'
            ))
            ->add('location', TextType::class, array(
                'required' => false,
                'label' => 'platformd.event.form.location'
            ))
            ->add('address1', TextType::class, array(
                'required' => false,
                'help' => 'platformd.event.form.help.address1',
                'label' => 'platformd.event.form.address1'
            ))
            ->add('address2', TextType::class, array(
                'required' => false,
                'help' => 'platformd.event.form.help.address2',
                'label' => 'platformd.event.form.address2'
            ))
            ->add('startsAt', DateTimeType::class, array(
                'label' => 'platformd.event.form.starts_at',
                'date_widget' => 'single_text',
                'time_widget' => 'choice',
                'empty_value' => array('hour' => 'Hrs', 'minute' => 'Mins'),
                'date_format' => 'MM/dd/yyyy',
                'error_bubbling' => false,
            ))
            ->add('endsAt', DateTimeType::class, array(
                'label' => 'platformd.event.form.ends_at',
                'date_widget' => 'single_text',
                'time_widget' => 'choice',
                'empty_value' => array('hour' => 'Hrs', 'minute' => 'Mins'),
                'date_format' => 'MM/dd/yyyy',
                'error_bubbling' => false,
            ))
            ->add('registrationOption', ChoiceType::class, array(
                'choices' => self::REGISTRATION_OPTIONS,
                'choices_as_values' => true,
                'expanded' => true,
                'label' => 'platformd.event.form.event_options'
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'platformd_event';
    }
}
