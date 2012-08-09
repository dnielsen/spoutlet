<?php

namespace Platformd\UserBundle\Controller;

use FOS\UserBundle\Controller\SecurityController as BaseController;
use Symfony\Component\HttpFoundation\Request;
use Platformd\UserBundle\EventListener\AwaVideoLoginRedirectListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Platformd\CEVOBundle\CEVOAuthManager;

/**
 * Overrides controller for login actions
 */
class SecurityController extends BaseController
{
    public function loginAction()
    {
        $this->processAlienwareVideoReturnUrlParameter($this->container->get('request'));

        /*
         * The real functionality of this method has been removed - login is at CEVO
         */
        return $this->redirectToCevoLogin();

        return parent::loginAction();
    }

    /**
     * The Alienware video site expects to send us a ?return=, and we'll go
     * back to that URL afterwards.
     *
     * We use this to store it on the session.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    private function processAlienwareVideoReturnUrlParameter(Request $request)
    {
        if ($returnUrl = $request->query->get('return')) {
            $request->getSession()->set(
                AwaVideoLoginRedirectListener::RETURN_SESSION_PARAMETER_NAME,
                $returnUrl
            );
        }
    }

    /**
     * Redirects to CEVO's login page
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function redirectToCevoLogin()
    {
        $request = $this->container->get('request');

        // todo - duplicated in entry point and registration controller (this type of idea) - centralize this
        $targetPath = $request->getSession()->get(
            AwaVideoLoginRedirectListener::RETURN_SESSION_PARAMETER_NAME,
            $return = $request->getUriForPath('/')
        );
        // I don't know if this happens in practice (http seems to be there), but just in case
        if (strpos($targetPath, 'http') !== 0) {
            $targetPath = $request->getUriForPath($targetPath);
        }

        /*
         * Ok, bear with me :). First, let me describe how the login process from
         * the video site works once again:
         *
         *   a) You're on the video site, you click "login"
         *   b) This sends you to japan.aa.com/login?return=/video (which is this page)
         *   c) The ?return= value is stored in the session (see processAlienwareVideoReturnUrlParameter above)
         *   d) We redirect to CEVO with ?return=XXXX. The value of XXXX is important (stay tuned)
         *   e) When we finish login at CEVO, they redirect to back to XXXX, which is some page on our site
         *   f) Arriving back on our site, the magic authentication takes place.
         *          i) We watch for a cookie set by CEVO on the base domain (e.g. .aa.com) - see CEVOAuthenticationListener
         *          ii) That cookie contains the username, we make an API request to CEVO to get
         *              user details, create a new User record in our DB if necessary, and authenticate
         *              the user (see CEVOAuthenticationProvider)
         *   g) At this point, we're on our site and we're authenticated. Now, one of 2 things happen:
         *          i) If we came from the video site with a ?return=/video (step b), then we now
         *              redirect to that URL. Remember that this return URL was stored in the session.
         *              We're basically sitting, and if we see a request with that session variable,
         *              we always redirect there - see AwaVideoLoginRedirectListener
         *          ii) If we didn't come from the video site, then nothing is set on the session and
         *              we just let the homepage load
         *   h) Assuming we're redirected back to the video site, *it* now looks for a cookie that *we*
         *      set and makes an API call back to us to get user details and then authenticate. This is
         *      identical to step (f), except that we're the authentication "server" (instead of CEVO)
         *      and the video site is the client (instead of us).
         *
         * This process is always important, but especially more important/confusing for the video site.
         * Remember step d where we redirected to CEVO with ?return=XXXX? This value is very important.
         * If XXXX is japan.aa.com/video, then CEVO will redirect to the video site. This is BAD, as it
         * means that we never authenticate on *this* site, and the video site depends on that (see h).
         *
         * SO, if the $targetPath is to the video site, we need to change it to point to *this* site.
         * This should be fine, as when CEVO redirects back to our homepage, step (g) will guarantee
         * that the user ends up on the video site.
         *
         * Actually, we could *always* change the $targetPath to the homepage - since step (g) would always
         * handle the page that we should return to. But, this was originally changed just to be a little
         * bit more direct (i.e. remove one of the redirects).
         */

        if (strpos($targetPath, '/video') !== false) {
            $targetPath = $this->container->get('router')->generate('default_index', array(), true);
        }

        $cevoManager = $this->container->get('pd.cevo.cevo_auth_manager');

        return new RedirectResponse($cevoManager->generateCevoUrl(
            CEVOAuthManager::LOGIN_PATH,
            $targetPath
        ));
    }
}
