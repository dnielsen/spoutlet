<?php

namespace Platformd\UserBundle\Mailer;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Mailer\MailerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Routing\RouterInterface;
use Platformd\SpoutletBundle\Util\SiteUtil;
use Platformd\SpoutletBundle\Model\EmailManager;

class Mailer implements MailerInterface
{
    private $emailManager;
    private $router;
    private $templating;
    private $parameters;
    private $siteUtil;
    private $userManager;

    function __construct(EmailManager $emailManager, RouterInterface $router, EngineInterface $templating, array $parameters, SiteUtil $siteUtil, $userManager) {
        $this->emailManager = $emailManager;
        $this->router       = $router;
        $this->templating   = $templating;
        $this->parameters   = $parameters;
        $this->siteUtil     = $siteUtil;
        $this->userManager  = $userManager;
    }

    public function sendResettedPasswordMessage(UserInterface $user)
    {
        $url = $this->router->generate('fos_user_resetting_reset', array(
            'token'   => $user->getConfirmationToken(),
            '_locale' => $this->userManager->getCountryLocaleForUser($user),
        ), true);

        $rendered = $this->templating->render(
            'FOSUserBundle:Admin/Resetted:resetted_email.txt.twig', array(
                'user'            => $user,
                'confirmationUrl' => $url
            )
        );

        $this->sendEmailMessage(
            $rendered,
            $user->getEmail(),
            'Password Reset Confirmation Email'
        );
    }

    public function sendResettingEmailMessage(UserInterface $user)
    {
        $template = $this->parameters['resetting.template'];
        $url      = $this->router->generate('fos_user_resetting_reset', array('token' => $user->getConfirmationToken()), true);
        $rendered = $this->templating->render($template, array(
            'user'            => $user,
            'confirmationUrl' => $url
        ));
        $this->sendEmailMessage($rendered, $user->getEmail(), 'Password Reset Email');
    }

    public function sendConfirmationEmailMessage(UserInterface $user)
    {
        $template = $this->parameters['confirmation.template'];
        $url      = $this->router->generate('fos_user_registration_confirm', array('token' => $user->getConfirmationToken()), true);
        $rendered = $this->templating->render($template, array(
            'user'            => $user,
            'confirmationUrl' =>  $url
        ));
        $this->sendEmailMessage($rendered, $user->getEmail(), 'Registration Email');
    }

    /**
     * Overridden to send an HTML email.
     *
     * This means that the body must be HTML - not text like normal
     *
     * @param $renderedTemplate
     * @param $fromEmail
     * @param $toEmail
     * @param $type
     */
    protected function sendEmailMessage($renderedTemplate, $toEmail, $type = '')
    {
        // Render the email, use the first line as the subject, and the rest as the body
        $renderedLines = explode("\n", trim($renderedTemplate));
        $subject       = $renderedLines[0];
        $body          = implode("\n", array_slice($renderedLines, 1));
        $site          = $this->siteUtil->getCurrentSite() ? $this->siteUtil->getCurrentSite()->getFullDomain() : 'unknown';

        $this->emailManager->sendHtmlEmail($toEmail, $subject, $body, $type, $site);
    }
}
