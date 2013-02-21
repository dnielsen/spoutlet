<?php

namespace Platformd\EventBundle\Form\Type;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilder,
    Symfony\Component\Security\Core\SecurityContextInterface,
    Symfony\Component\EventDispatcher\EventSubscriberInterface
;

use Platformd\EventBundle\Form\EventSubscriber\AdminEventSubscriber,
    Platformd\EventBundle\Entity\Event,
    Platformd\MediaBundle\Form\Type\MediaType
;

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
    public function __construct(
        SecurityContextInterface $security
    )
    {
        $this->security         = $security;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'label' => 'platformd.event.form.name',
            ))
            ->add('bannerImage', new MediaType(), array(
                'image_label'   => 'platformd.event.form.banner_image',
                'image_help'    => 'platformd.event.form.help.banner_image',
                'required' => false
            ))
            ->add('content', 'purifiedTextarea', array(
                'label' => 'platformd.event.form.description',
                'attr' => array(
                    'class' => 'ckeditor'
                )
            ))
            ->add('game', 'entity', array(
                'class'     => 'GameBundle:Game',
                'property'  => 'name',
                'empty_value' => 'Other',
                'required'  => false,
                'label' => 'platformd.event.form.game'
            ))
            ->add('online', 'choice', array(
                'choices'   => array(
                    1 => 'platformd.event.form.choice.online_event',
                    0 => 'platformd.event.form.choice.physical_event'
                ),
                'required'  => false,
                'expanded' => true,
                'label' => 'platformd.event.form.event_type'
            ))
            ->add('location', 'text', array(
                'required' => false,
                'label' => 'platformd.event.form.location'
            ))
            ->add('address', 'text', array(
                'required' => false,
                'help' => 'platformd.event.form.help.address',
                'label' => 'platformd.event.form.address'
            ))
            ->add('startsAt', 'datetime', array(
                'label' => 'platformd.event.form.starts_at',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker',
                )
            ))
            ->add('endsAt', 'datetime', array(
                'label' => 'platformd.event.form.ends_at',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker',
                )
            ))
        ;
    }

    public function getDefaultOptions(array $options)
    {
        $options = parent::getDefaultOptions($options);

        $options['data_class'] = 'Platformd\EventBundle\Entity\Event';

        return $options;
    }

    public function getName()
    {
        return 'platformd_event';
    }
}
