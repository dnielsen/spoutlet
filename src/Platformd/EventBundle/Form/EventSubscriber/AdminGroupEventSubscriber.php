<?php

namespace Platformd\EventBundle\Form\EventSubscriber;

use Platformd\EventBundle\Entity\GroupEvent;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface,
    Symfony\Component\EventDispatcher\EventSubscriberInterface,
    Symfony\Component\Form\FormEvents;

use Doctrine\ORM\EntityRepository;

use Platformd\SpoutletBundle\Form\Type\SlugType,
    Platformd\EventBundle\Entity\Event,
    Platformd\EventBundle\Form\Type\GroupEventTranslationType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AdminGroupEventSubscriber implements EventSubscriberInterface
{
    const PRIVATES = [
        'platformd.event.form.choice.private_event' => 1,
        'platformd.event.form.choice.public_event' => 0,
    ];

    private $factory;
    private $authChecker;

    public function __construct(FormFactoryInterface $factory, AuthorizationCheckerInterface $authChecker)
    {
        $this->factory = $factory;
        $this->authChecker = $authChecker;
    }

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }

    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (null === $data) {
            return;
        }

        // If User is a super admin, they have more options
        if ($this->authChecker->isGranted('ROLE_SUPER_ADMIN')) {
            $form->add('registrationOption', ChoiceType::class, [
                'choices' => [
                    'platformd.event.registration.enabled' => Event::REGISTRATION_ENABLED,
                    'platformd.event.registration.disabled' => Event::REGISTRATION_DISABLED,
                    'platformd.event.registration.3rdparty' => Event::REGISTRATION_3RD_PARTY,
                ],
                'choices_as_values' => true,
                'expanded' => false,
                'multiple' => false,
                'label' => 'platformd.event.form.event_options',
//                'auto_initialize' => false,
            ]);

            $form->add('externalUrl', TextType::class, array(
                'label' => 'platformd.event.form.external_url',
//                'help' => 'platformd.event.form.help.external_url',
                'required' => false,
                'auto_initialize' => false,
            ));

            /** @var \Doctrine\ORM\PersistentCollection $sites */
            $sitesArr = array();

            if ($data instanceof GroupEvent) {
                $sites = $data->getGroup()->getSites();
                $sitesArr = array();
                foreach ($sites->toArray() as $site) {
                    $sitesArr[] = $site->getId();
                }
            }

            $form->add('slug', new SlugType(), array(
                'label' => 'platformd.event.form.url'
            ));

            $form->add('sites', EntityType::class, array(
                'class' => 'SpoutletBundle:Site',
                'query_builder' => function (EntityRepository $er) use ($sitesArr) {
                    $qb = $er->createQueryBuilder('s');
                    return $qb
                        ->add('where', $qb->expr()->in('s.id', ':sites_array'))
                        ->setParameter('sites_array', $sitesArr);
                },
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
                'label' => 'platformd.event.form.sites'
            ));

            $form->add('translations', CollectionType::class, [
                'entry_type' => new GroupEventTranslationType,
                'allow_add' => false,
                'allow_delete' => false,
                'by_reference' => false,
                'required' => false
            ]);
        } else {
            $form->add('registrationOption', ChoiceType::class, [
                'choices' => [
                    'platformd.event.registration.enabled' => Event::REGISTRATION_ENABLED,
                    'platformd.event.registration.disabled' => Event::REGISTRATION_DISABLED,
                ],
                'choices_as_values' => true,
                'expanded' => false,
                'multiple' => false,
                'label' => 'platformd.event.form.event_options'
            ]);
        }

        if ($data instanceof GroupEvent) {
            $form->add('private', ChoiceType::class, array(
                'choices' => self::PRIVATES,
                'choices_as_values' => true,
                'label' => 'platformd.event.form.private_public',
                'expanded' => true
            ));
        }
    }
}
