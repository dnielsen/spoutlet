<?php

namespace Platformd\EventBundle\Form\EventSubscriber;

use Symfony\Component\Form\FormFactoryInterface,
    Symfony\Component\EventDispatcher\EventSubscriberInterface,
    Symfony\Component\Form\FormEvents,
    Symfony\Component\Security\Core\SecurityContextInterface;

use Doctrine\ORM\EntityRepository;

use Platformd\SpoutletBundle\Form\Type\SlugType,
    Platformd\EventBundle\Entity\Event,
    Platformd\EventBundle\Form\Type\GroupEventTranslationType;
use Symfony\Component\Form\FormEvent;

class AdminGroupEventSubscriber implements EventSubscriberInterface
{
    private $factory;
    private $security;

    public function __construct(FormFactoryInterface $factory, SecurityContextInterface $security)
    {
        $this->factory = $factory;
        $this->security = $security;
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
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $form->add($this->factory->createNamed('choice', 'registrationOption', null, array(
                'choices' => [
                    'platformd.event.registration.enabled' => Event::REGISTRATION_ENABLED,
                    'platformd.event.registration.disabled' => Event::REGISTRATION_DISABLED,
                    'platformd.event.registration.3rdparty' => Event::REGISTRATION_3RD_PARTY,
                ],
                'choices_as_values' => true,
                'expanded' => false,
                'multiple' => false,
                'label' => 'platformd.event.form.event_options'
            )));

            $form->add($this->factory->createNamed('text', 'externalUrl', null, array(
                'label' => 'platformd.event.form.external_url',
                'help' => 'platformd.event.form.help.external_url',
                'required' => false
            )));

            /** @var \Doctrine\ORM\PersistentCollection $sites */
            $sites = $data->getGroup()->getSites();
            $sitesArr = array();
            foreach ($sites->toArray() as $site) {
                $sitesArr[] = $site->getId();
            }

            $form->add($this->factory->createNamed(new SlugType(), 'slug', array(
                'label' => 'platformd.event.form.url'
            )));

            $form->add($this->factory->createNamed('entity', 'sites', null, array(
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
            )));

            $form->add($this->factory->createNamed('collection', 'translations', null, array(
                'type' => new GroupEventTranslationType,
                'allow_add' => false,
                'allow_delete' => false,
                'by_reference' => false,
                'required' => false
            )));
        } else {
            $form->add($this->factory->createNamed('choice', 'registrationOption', null, array(
                'choices' => [
                    'platformd.event.registration.enabled' => Event::REGISTRATION_ENABLED,
                    'platformd.event.registration.disabled' => Event::REGISTRATION_DISABLED,
                ],
                'choices_as_values' => true,
                'expanded' => false,
                'multiple' => false,
                'label' => 'platformd.event.form.event_options'
            )));
        }
    }
}
