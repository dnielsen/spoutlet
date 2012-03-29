<?php

namespace Platformd\TranslationBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

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

        if (!in_array($locale, $this->getAvailableLocales())) {
            throw $this->createNotFoundException(sprintf('Locale "%s" is not a valid locale', $locale));
        }

        $localeName = $this->translateLocale($locale);
        $this->addTranslationAdminBreadcrumbs()
            ->addChild($localeName)
        ;

        $tokens = $this->getTokenRepository()->findAll();
        $tokens = $this->sortTokens($tokens);

        return array('tokens' => $tokens, 'localeName' => $localeName, 'locale' => $locale);
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
            asort($tmpArr);

            return ($tmpArr[0] == $transA) ? 1 : -1;
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

        if (!array_key_exists($locale, $translations)) {
            throw new \InvalidArgumentException(sprintf('Invalid locale or locale without translation: "%s"', $locale));
        }

        return $translations[$locale];
    }
}