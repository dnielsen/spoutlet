<?php

namespace Platformd\TranslationBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TranslationAdminController extends Controller
{
    /**
     * Lists the available locales
     *
     * @Template
     * @return array
     */
    public function listLocalesAction()
    {
        $this->basicSecurityCheck('ROLE_ADMIN_TRANSLATIONS');

        if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
            $url = $this->generateUrl('pd_translation_admin_edit_locale', array('locale' => 'ja'));
            return $this->redirect($url);
        }

        $this->addTranslationAdminBreadcrumbs();

        $localesReport = $this->getTokenRepository()->getLocalesStatusArray($this->getAvailableLocales());

        // add the true name of the locale to the report array
        foreach ($localesReport as $locale => $localeReport) {
            $localesReport[$locale]['name'] = $this->translateLocale($locale);
        }

        return array('localesReport' => $localesReport);
    }

    /**
     * @Template
     * @param $locale
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function editLocaleAction($locale)
    {
        $this->basicSecurityCheck('ROLE_ADMIN_TRANSLATIONS');

        if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
            $locale = 'ja';
        }

        if (!in_array($locale, $this->getAvailableLocales())) {
            throw $this->createNotFoundException(sprintf('Locale "%s" is not a valid locale', $locale));
        }

        $localeName = $this->translateLocale($locale);
        $this->addTranslationAdminBreadcrumbs()
            ->addChild($localeName)
        ;

        $tokens = $this->getTokenRepository()->findAllActiveNonChildrenTokens();
        $tokens = $this->sortTokens($tokens);

        return array('tokens' => $tokens, 'localeName' => $localeName, 'locale' => $locale);
    }

    /**
     * AJAX endpoint to actually update a translation
     *
     * @param $tokenId
     * @param $locale
     */
    public function updateTranslationAction($tokenId, $locale, Request $request)
    {
        $token = $this->getTokenRepository()->find($tokenId);
        if (!$token) {
            throw $this->createNotFoundException('No translation token for id '.$tokenId);
        }

        if (!in_array($locale, $this->getAvailableLocales())) {
            throw $this->createNotFoundException(sprintf('Locale "%s" is not a valid locale', $locale));
        }

        $newString = $request->request->get('translation');

        // actually does the updating (with recursive to children)
        $this->getTranslationUpdater()->updateTranslation($token, $locale, $newString);

        $data = array(
            'status' => 1,
            'translation' => $newString,
        );

        $response =  new Response(json_encode($data));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    private function getAvailableLocales()
    {
        return $this->container->getParameter('available_locales');
    }

    /**
     * @return \Platformd\TranslationBundle\Entity\Repository\TranslationTokenRepository
     */
    private function getTokenRepository()
    {
        return $this->getDoctrine()
            ->getRepository('TranslationBundle:TranslationToken')
        ;
    }

    /**
     * @return \Platformd\TranslationBundle\Translation\Updater
     */
    private function getTranslationUpdater()
    {
        return $this->container->get('pd_translation.translation.updater');
    }

    /**
     * Reorders the tokens array to be alphabetical
     *
     * @param array $tokens
     */
    private function sortTokens(array $tokens)
    {
        /** @var $translator \Symfony\Component\Translation\Translator */
        $translator = $this->container->get('translator');

        usort($tokens, function($a, $b) use ($translator) {
            $transA = $translator->trans($a->getToken(), array(), $a->getDomain(), 'en');
            $transB = $translator->trans($b->getToken(), array(), $b->getDomain(), 'en');

            $tmpArr = array($transA, $transB);
            sort($tmpArr);

            return ($tmpArr[0] == $transA) ? -1 : 1;
        });

        return $tokens;
    }

    private function addTranslationAdminBreadcrumbs()
    {
        $this->getBreadcrumbs()->addChild('Translations', array('route' => 'pd_translation_admin_list_locales'));

        return $this->getBreadcrumbs();
    }

    /**
     * @todo Probably this needs to move somewhere else
     *
     * @param $locale
     * @return string
     */
    private function translateLocale($locale)
    {
        $translations = $this->container->getParameter('locale_translations');

        return isset($translations[$locale]) ? $translations[$locale] : $locale;
    }
}
