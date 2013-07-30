<?php

namespace Platformd\UserBundle\Form\Handler;

use FOS\UserBundle\Form\Handler\RegistrationFormHandler as BaseRegistrationFormHandler;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Mailer\MailerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Platformd\SpoutletBundle\Util\IpLookupUtil;
use Platformd\SpoutletBundle\Exception\InsufficientAgeException;

class RegistrationFormHandler extends BaseRegistrationFormHandler
{
    protected $request;
    protected $userManager;
    protected $form;
    protected $mailer;
    protected $em;
    protected $container;
    protected $ipLookupUtil;

    public function __construct(Form $form, Request $request, UserManagerInterface $userManager, MailerInterface $mailer, EntityManager $em, ContainerInterface $container, IpLookupUtil $ipLookupUtil)
    {
        $this->form         = $form;
        $this->request      = $request;
        $this->userManager  = $userManager;
        $this->mailer       = $mailer;
        $this->em           = $em;
        $this->container    = $container;
        $this->ipLookupUtil = $ipLookupUtil;
    }

    public function process($confirmation = false)
    {
        $user = $this->userManager->createUser();
        $user->setCountry($this->getUserCountry());
        $this->form->setData($user);

        if ('POST' == $this->request->getMethod()) {
            $this->form->bindRequest($this->request);

            $ageManager = $this->container->get('platformd.age.age_manager');
            $site       = $this->container->get('platformd.util.site_util')->getCurrentSite();

            if ($this->form->getData()->getBirthdate()) {
                $ageManager->setUsersBirthday($this->form->getData()->getBirthdate());

                if ($ageManager->getUsersAge() < $site->getSiteConfig()->getMinAgeRequirement()) {
                    throw new InsufficientAgeException();
                }
            }

            if ($this->form->isValid()) {

                $ipAddress  = $this->request->getClientIp(true);
                $user->setIpAddress($ipAddress);

                if ($this->checkRegistrationTimeoutPassed() === false) {
                    $this->container->get('platformd.util.flash_util')->setFlash('error', 'platformd.user.register.please_wait');
                    return false;
                }

                $this->onSuccess($user, $confirmation);

                return true;
            }
        }

        return false;
    }

    protected function checkRegistrationTimeoutPassed()
    {
        $repo = $this->em->getRepository('UserBundle:User');

        $request    = $this->request;
        $ipAddress  = $request->getClientIp(true);

        $result = $repo->createQueryBuilder('u')
            ->andWhere('u.ipAddress = :ipAddress')
            ->andWhere('u.created > :dateTime')
            ->setParameters(array(
                'ipAddress' => $ipAddress,
                'dateTime'  => new \DateTime('-5 minutes')
            ))
            ->getQuery()
            ->execute();

        return $result ? false : true;
    }

    private function getUserCountry()
    {
        $ipAddress  = $this->request->getClientIp(true);
        return $this->ipLookupUtil->getCountryCode($ipAddress);
    }
}
