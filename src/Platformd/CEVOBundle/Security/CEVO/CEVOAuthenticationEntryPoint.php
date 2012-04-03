<?php

namespace Platformd\CEVOBundle\Security\CEVO;

use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Platformd\CEVOBundle\CEVOAuthManager;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * An entry point is the action that should be taken when a user hits a page
 * that is protected and we need to give them a way to authenticate.
 *
 * In a traditional model, this is a redirect to the login page
 */
class CEVOAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    private $cevoAuthManager;

    public function __construct(CEVOAuthManager $cevoAuthManager)
    {
        $this->cevoAuthManager = $cevoAuthManager;
    }

    /**
     * Starts the authentication scheme.
     *
     * @param Request                 $request       The request that resulted in an AuthenticationException
     * @param AuthenticationException $authException The exception that started the authentication process
     *
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        // make the "return" the URL that was previously requested, or homepage
        $returnPath = $request->getSession()->get('_security.target_path', '/');

        // I don't know if this happens in practice (http seems to be there), but just in case
        if (strpos($returnPath, 'http') !== 0) {
            $returnPath = $request->getUriForPath($returnPath);
        }

        $url = $this->cevoAuthManager->generateCevoUrl(
            CEVOAuthManager::LOGIN_PATH,
            $returnPath
        );

        return new RedirectResponse($url);
    }

}