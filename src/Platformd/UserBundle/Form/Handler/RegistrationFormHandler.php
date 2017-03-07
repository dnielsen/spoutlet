<?php

namespace Platformd\UserBundle\Form\Handler;

use FOS\UserBundle\Form\Handler\RegistrationFormHandler as BaseRegistrationFormHandler;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Mailer\MailerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Platformd\SpoutletBundle\Util\IpLookupUtil;
use Platformd\SpoutletBundle\Exception\InsufficientAgeException;
use Platformd\UserBundle\Exception\UserRegistrationTimeoutException;
use FOS\UserBundle\Util\TokenGeneratorInterface;

class RegistrationFormHandler extends BaseRegistrationFormHandler
{
    protected $request;
    protected $userManager;
    protected $form;
    protected $mailer;
    protected $em;
    protected $container;
    protected $ipLookupUtil;
    protected $apiManager;

    public function __construct(
        Form $form,
        Request $request,
        UserManagerInterface $userManager,
        MailerInterface $mailer,
        TokenGeneratorInterface $tokenGenerator,
        EntityManager $em,
        ContainerInterface $container,
        IpLookupUtil $ipLookupUtil,
        $apiManager
    ) {
        parent::__construct($form, $request, $userManager, $mailer, $tokenGenerator);

        $this->form = $form;
        $this->request = $request;
        $this->userManager = $userManager;
        $this->mailer = $mailer;
        $this->em = $em;
        $this->container = $container;
        $this->ipLookupUtil = $ipLookupUtil;
        $this->apiManager = $apiManager;
    }

    public function process($confirmation = false)
    {
        $user = $this->userManager->createUser();
        $country = $this->getUserCountry();
        $user->setCountry($country);
        $this->form->setData($user);

        if ($country === 'US') {
            $user->setSubscribedAlienwareEvents(true);
        }

        if ('POST' === $this->request->getMethod()) {
            $this->form->submit($this->request);

            if ($this->form->getData()->getBirthdate()) {
                $ageManager = $this->container->get('platformd.age.age_manager');

                if (!$ageManager->getUsersAge()) {
                    $ageManager->setUsersBirthday($this->form->getData()->getBirthdate());
                }

                $site = $this->container->get('platformd.util.site_util')->getCurrentSite();

                if ($ageManager->getUsersAge() < $site->getSiteConfig()->getMinAgeRequirement()) {
                    throw new InsufficientAgeException();
                }
            }

            if ($this->form->isValid()) {
                $ipAddress = $this->request->getClientIp(true);
                $user->setIpAddress($ipAddress);

                if ($this->checkRegistrationTimeoutPassed() === false) {
                    throw new UserRegistrationTimeoutException();
                }

                if ($this->container->getParameter('api_authentication')) {
                    if (false === $this->apiManager->createRemoteUser($user, $user->getPlainPassword())) {
                        throw new UserRegistrationTimeoutException();
                    }
                }

                $this->onSuccess($user, $confirmation);

                $this->apiManager->updateRemoteUserData(array(
                    'uuid' => $user->getUuid(),
                    'created' => $user->getCreated()->format('Y-m-d H:i:s'),
                    'last_updated' => $user->getUpdated()->format('Y-m-d H:i:s'),
                ));

                return true;
            }
        }

        return false;
    }

    protected function checkRegistrationTimeoutPassed()
    {
        $repo = $this->em->getRepository('UserBundle:User');

        $request = $this->request;
        $ipAddress = $request->getClientIp(true);

        $result = $repo->createQueryBuilder('u')
            ->andWhere('u.ipAddress = :ipAddress')
            ->andWhere('u.created > :dateTime')
            ->setParameters(array(
                'ipAddress' => $ipAddress,
                'dateTime' => new \DateTime('-1 minute')
            ))
            ->getQuery()
            ->execute();

        return $result ? false : true;
    }

    private function getUserCountry()
    {
        $ipAddress = $this->request->getClientIp(true);
        return $this->ipLookupUtil->getCountryCode($ipAddress);
    }
}
