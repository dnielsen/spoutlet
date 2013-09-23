<?php

namespace Platformd\SweepstakesBundle\Form\Handler;

use FOS\UserBundle\Form\Handler\RegistrationFormHandler as BaseRegistrationFormHandler;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Mailer\MailerInterface;

use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use Platformd\SpoutletBundle\Util\IpLookupUtil;
use Platformd\SpoutletBundle\Exception\InsufficientAgeException;
use Platformd\SpoutletBundle\Util\SiteUtil;
use Platformd\UserBundle\Exception\ApiRequestException;
use Platformd\UserBundle\Exception\UserRegistrationTimeoutException;
use Platformd\CEVOBundle\Api\ApiManager as CevoApiManager;
use Platformd\CEVOBundle\Api\ApiException as CevoApiException;
use Platformd\GroupBundle\Model\GroupManager;
use Platformd\UserBundle\Entity\RegistrationSource;
use Platformd\SweepstakesBundle\Entity\Sweepstakes;

class SweepstakesEntryFormHandler
{
    protected $request;
    protected $userManager;
    protected $form;
    protected $mailer;
    protected $em;
    protected $container;
    protected $ipLookupUtil;
    protected $apiManager;
    protected $siteUtil;
    protected $groupManager;
    protected $cevoApiManager;
    protected $translator;
    protected $exposer;

    public function __construct(Request $request, UserManagerInterface $userManager, MailerInterface $mailer, EntityManager $em, ContainerInterface $container, IpLookupUtil $ipLookupUtil, $apiManager, SiteUtil $siteUtil, GroupManager $groupManager, CevoApiManager $cevoApiManager, $translator, $exposer)
    {
        $this->request        = $request;
        $this->userManager    = $userManager;
        $this->mailer         = $mailer;
        $this->em             = $em;
        $this->container      = $container;
        $this->ipLookupUtil   = $ipLookupUtil;
        $this->apiManager     = $apiManager;
        $this->siteUtil       = $siteUtil;
        $this->groupManager   = $groupManager;
        $this->cevoApiManager = $cevoApiManager;
        $this->translator     = $translator;
        $this->exposer        = $exposer;
    }

    public function process($confirmation = false)
    {
        $regProcessed = $this->processRegistation($confirmation);

        if ($regProcessed) {
            $entryProcessed = $this->processSweepsEntry();
            return $entryProcessed;
        }

        return false;
    }

    private function processRegistation($confirmation = false)
    {
        if ($this->form->has('registrationDetails')) {
            $user = $this->userManager->createUser();
            $country = $this->getUserCountry();
            $user->setCountry($country);
            $this->form->get('registrationDetails')->setData($user);

            if ($country == 'US') {
                $user->setSubscribedAlienwareEvents(true);
            }

            if ('POST' == $this->request->getMethod()) {
                $this->form->bindRequest($this->request);

                $ageManager = $this->container->get('platformd.age.age_manager');
                $site       = $this->siteUtil->getCurrentSite();

                if ($this->form->get('registrationDetails')->getData()->getBirthdate()) {

                    if(!$ageManager->getUsersAge()) {
                        $ageManager->setUsersBirthday($this->form->get('registrationDetails')->getData()->getBirthdate());
                    }

                    if ($ageManager->getUsersAge() < $site->getSiteConfig()->getMinAgeRequirement()) {
                        throw new InsufficientAgeException();
                    }
                }

                if ($this->form->isValid()) {

                    $ipAddress  = $this->request->getClientIp(true);
                    $user->setIpAddress($ipAddress);

                    if ($this->checkRegistrationTimeoutPassed() === false) {
                        throw new UserRegistrationTimeoutException();
                    }

                    if ($this->container->getParameter('api_authentication')) {
                        if (false === $this->apiManager->createRemoteUser($user, $user->getPlainPassword())) {
                            throw new UserRegistrationTimeoutException();
                        }
                    }

                    $entry       = $this->form->getData();
                    $sweepstakes = $entry->getSweepstakes();
                    $country     = $this->em->getRepository('SpoutletBundle:Country')->findOneByCode($country);
                    $regSource   = new RegistrationSource($user, RegistrationSource::REGISTRATION_SOURCE_TYPE_SWEEPSTAKES, $sweepstakes->getId(), $country);

                    $this->em->persist($regSource);
                    $this->em->flush();

                    $this->onSuccess($user, $confirmation);

                    $this->apiManager->updateRemoteUserData(array(
                        'uuid'         => $user->getUuid(),
                        'created'      => $user->getCreated()->format('Y-m-d H:i:s'),
                        'last_updated' => $user->getUpdated()->format('Y-m-d H:i:s'),
                    ));

                    return true;
                }
            }

            return false;
        }

        return true;
    }

    private function processSweepsEntry()
    {
        if ('POST' == $this->request->getMethod()) {

            $valid = true;

            if (!$this->form->isBound()) {
                $this->form->bindRequest($this->request);
                $valid = $this->form->isValid();
            }

            if ($valid) {
                $entry          = $this->form->getData();
                $sweepstakes    = $entry->getSweepstakes();

                # we don't have a user at all...
                if (!$entry->getUser() && !$this->form->get('registrationDetails')->getData()) {
                    throw new AccessDeniedException();
                }

                $createdAccount = $this->form->has('registrationDetails') ? true : false;
                $entry->setCreatedAccount($createdAccount);

                $isLoggedIn = $entry->getUser() ? true : false;
                $user       = $entry->getUser() ? $entry->getUser() : $this->form->get('registrationDetails')->getData();

                $existing = $user ? $this->em->getRepository('SweepstakesBundle:SweepstakesEntry')->findOneBySweepstakesAndUser($sweepstakes, $user) : null;
                if ($existing) {
                    return false;
                }

                $entry->setUser($user);

                $clientIp = $this->ipLookupUtil->getClientIp($this->request);
                $entry->setIpAddress($clientIp);

                $countryCode = $this->ipLookupUtil->getCountryCode($clientIp);
                $country = $this->em->getRepository('SpoutletBundle:Country')->findOneByCode($countryCode);
                $entry->setCountry($country);

                $this->em->persist($entry);

                $site = $this->siteUtil->getCurrentSite();

                if($site->getSiteFeatures()->getHasGroups() && $sweepstakes->getGroup()) {
                    $this->groupManager->autoJoinGroup($sweepstakes->getGroup(), $user);
                }

                if ($sweepstakes->getEventType() == Sweepstakes::SWEEPSTAKES_TYPE_PROMO_CODE) {

                    $promoCode = $entry->getAnswers()->first();

                    if (!$promoCode) {
                        $isWinner = false;
                    } else {
                        $isWinner = $this->em->getRepository('SweepstakesBundle:PromoCodeContestCode')->findOneBy(array(
                            'value'   => trim($promoCode->getContent()),
                            'contest' => $sweepstakes->getId(),
                            'user'    => null,
                        ));
                    }

                    $session = $this->request->getSession();

                    if ($isWinner) {
                        $winningCode = $isWinner;
                        $winningCode->assign($user, $clientIp, $site->getDefaultLocale(), $country);

                        $this->em->persist($winningCode);

                        if (!$isLoggedIn) {
                            $flashMessage = $sweepstakes->getWinnerMessage();
                            $flashMessage = str_replace(array(
                                '--contestName--', '--w9Url--', '--affidavitUrl--'
                            ), array(
                                $sweepstakes->getName(),
                                $this->exposer->getPath($sweepstakes->getW9Form()),
                                $this->exposer->getPath($sweepstakes->getAffidavit())
                            ), $flashMessage);

                            $session->setFlash('success', $flashMessage);
                        }

                    } else {
                        $consolationCode = $this->em->getRepository('SweepstakesBundle:PromoCodeContestConsolationCode')->findOneBy(array(
                            'contest' => $sweepstakes->getId(),
                            'user'    => null,
                        ));

                        if ($consolationCode) {
                            $consolationCode->assign($user, $clientIp, $site->getDefaultLocale(), $country);
                            $this->em->persist($consolationCode);

                            if (!$isLoggedIn) {
                                $flashMessage = str_replace('--code--', $consolationCode->getValue(), $sweepstakes->getLoserMessage());
                                $session->setFlash('info', $flashMessage);
                            }
                        } else {
                            if (!$isLoggedIn) {
                                $flashMessage = $sweepstakes->getBackupLoserMessage() ?: $this->translator->trans('platformd.sweepstakes.promo_code.flash.loser_no_code');
                                $session->setFlash('info', $flashMessage);
                            }
                        }
                    }
                } else {
                    $this->setFlash('success', 'platformd.sweepstakes.entered.message');
                }

                $this->em->flush();

                // arp - enteredsweepstakes
                try {
                    $arpResponse = $this->cevoApiManager->GiveUserXp('enteredsweepstakes', $user->getCevoUserId());
                } catch (CevoApiException $e) {

                }

                return true;
            }
        }

        return false;
    }

    protected function onSuccess(UserInterface $user, $confirmation)
    {
        if ($confirmation) {
            $user->setEnabled(false);
            $this->mailer->sendConfirmationEmailMessage($user);
        } else {
            $user->setConfirmationToken(null);
            $user->setEnabled(true);
        }

        $this->userManager->updateUser($user);
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
                'dateTime'  => new \DateTime('-1 minutes')
            ))
            ->getQuery()
            ->execute();

        return $result ? false : true;
    }

    private function setFlash($key, $message)
    {
        $this->container->get('platformd.util.flash_util')->setFlash($key, $message);
    }

    private function getUserCountry()
    {
        $ipAddress  = $this->request->getClientIp(true);
        return $this->ipLookupUtil->getCountryCode($ipAddress);
    }

    public function setForm($form)
    {
        $this->form = $form;
    }
}
