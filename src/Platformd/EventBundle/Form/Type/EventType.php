<?php

namespace Platformd\EventBundle\Form\Type;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilder,
    Symfony\Component\Security\Core\SecurityContextInterface
;

use Platformd\SpoutletBundle\Form\Type\SlugType;

use Platformd\EventBundle\Form\EventSubscriber\AdminEventSubscriber,
    Platformd\EventBundle\Entity\Event
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
    public function __construct(SecurityContextInterface $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        // Needed to show fields only to admins
        $adminEventSubscriber = new AdminEventSubscriber($builder->getFormFactory(), $this->security);

        $builder
            ->add('name', 'text')
            ->add('bannerImageFile', 'file', array(
                'help' => 'Resolution 950x120, jpeg or png formats',
                'required' => false
            ))
            ->add('slug', new SlugType(), array('label' => 'URL'))
            ->add('content', 'purifiedTextarea', array(
                'label' => 'Description',
                'attr' => array(
                    'class' => 'ckeditor'
                )
            ))
            ->add('game', 'entity', array(
                'class'     => 'SpoutletBundle:Game',
                'property'  => 'name',
                'empty_value' => 'N/A'
            ))
            ->add('online', 'choice', array(
                'choices'   => array(
                    1 => 'Online Event',
                    0 => 'Enter Location Address'
                ),
                'required'  => false,
                'expanded' => true,
                'label' => 'Event Type'
            ))
            ->add('location', 'text', array(
                'required' => false
            ))
            ->add('address', 'text', array(
                'required' => false,
                'help' => 'Example: 1021 Washington Drive, San Francisco, CA, United States'
            ))
            ->add('startsAt', 'datetime', array(
                'label' => 'Starts At',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker',
                )
            ))
            ->add('endsAt', 'datetime', array(
                'label' => 'Ends At',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker',
                )
            ))
            ->add('timezone', 'timezone')
            ->add('displayTimezone', 'checkbox', array(
                'label' => 'Display Timezone',

            ))
            ->addEventSubscriber($adminEventSubscriber)
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
        return 'awa_event';
    }
}
