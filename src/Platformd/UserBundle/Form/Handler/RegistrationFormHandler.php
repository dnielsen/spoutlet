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

class RegistrationFormHandler extends BaseRegistrationFormHandler
{
    protected $request;
    protected $userManager;
    protected $form;
    protected $mailer;
    protected $em;
    protected $container;

    public function __construct(Form $form, Request $request, UserManagerInterface $userManager, MailerInterface $mailer, EntityManager $em, ContainerInterface $container)
    {
        $this->form = $form;
        $this->request = $request;
        $this->userManager = $userManager;
        $this->mailer = $mailer;
        $this->em = $em;
        $this->container = $container;
    }

    public function process($confirmation = false)
    {
        $user = $this->userManager->createUser();
        $this->form->setData($user);

        if ('POST' == $this->request->getMethod()) {
            $this->form->bindRequest($this->request);

            if ($this->form->isValid()) {

                $ipAddress  = $this->request->getClientIp(true);
                $user->setIpAddress($ipAddress);

                if ($this->checkRegistrationTimeoutPassed() === false) {
                    $this->request->getSession()->setFlash('error', 'platformd.user.register.please_wait');
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
}
