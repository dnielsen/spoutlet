<?php

namespace Platformd\EventBundle\Form\Type;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Security\Core\SecurityContextInterface;

use Platformd\EventBundle\Form\EventSubscriber\AdminEventSubscriber,
    Platformd\EventBundle\Entity\Event,
    Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EventType extends AbstractType
{
    /**
     * @var \Symfony\Component\Security\Core\SecurityContextInterface
     */
    protected $security;

    /**
     * Constructor
     *
     * @param \Symfony\Component\Security\Core\SecurityContextInterface $security
     */
    public function __construct(SecurityContextInterface $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'label' => 'platformd.event.form.name',
            ))
            ->add('bannerImage', new MediaType(), array(
                'image_label' => 'platformd.event.form.banner_image',
                'image_help' => 'platformd.event.form.help.banner_image',
                'required' => false
            ))
            ->add('content', 'purifiedTextarea', array(
                'label' => 'platformd.event.form.description',
                'attr' => array(
                    'class' => 'ckeditor'
                )
            ))
            ->add('online', 'choice', array(
                'choices' => array(
                    0 => 'platformd.event.form.choice.physical_event',
                    1 => 'platformd.event.form.choice.online_event'
                ),
                'expanded' => true,
                'label' => 'platformd.event.form.event_type'
            ))
            ->add('location', 'text', array(
                'required' => false,
                'label' => 'platformd.event.form.location'
            ))
            ->add('address1', 'text', array(
                'required' => false,
                'help' => 'platformd.event.form.help.address1',
                'label' => 'platformd.event.form.address1'
            ))
            ->add('address2', 'text', array(
                'required' => false,
                'help' => 'platformd.event.form.help.address2',
                'label' => 'platformd.event.form.address2'
            ))
            ->add('startsAt', 'datetime', array(
                'label' => 'platformd.event.form.starts_at',
                'date_widget' => 'single_text',
                'time_widget' => 'choice',
                'empty_value' => array('hour' => 'Hrs', 'minute' => 'Mins'),
                'date_format' => 'MM/dd/yyyy',
                'error_bubbling' => false,
            ))
            ->add('endsAt', 'datetime', array(
                'label' => 'platformd.event.form.ends_at',
                'date_widget' => 'single_text',
                'time_widget' => 'choice',
                'empty_value' => array('hour' => 'Hrs', 'minute' => 'Mins'),
                'date_format' => 'MM/dd/yyyy',
                'error_bubbling' => false,
            ))
            ->add('registrationOption', 'choice', array(
                'choices' => array(
                    Event::REGISTRATION_ENABLED => 'platformd.event.registration.enabled',
                    Event::REGISTRATION_DISABLED => 'platformd.event.registration.disabled',
                    Event::REGISTRATION_3RD_PARTY => 'platformd.event.registration.3rdparty'
                ),
                'expanded' => true,
                'label' => 'platformd.event.form.event_options'
            ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }

    public function getName()
    {
        return 'platformd_event';
    }
}
