<?php

namespace Platformd\NewsBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;

use Platformd\NewsBundle\Entity\News;
use Platformd\NewsBundle\Form\Type\CreateNewsFormType;

use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use Knp\MediaBundle\Util\MediaUtil;

class AdminController extends Controller
{
    public function indexAction()
    {
        if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
            $url = $this->generateUrl('NewsBundle_admin_siteList', array('site' => 2));
            return $this->redirect($url);
        }

        $this->addNewsBreadcrumb();

        return $this->render('NewsBundle:Admin:index.html.twig', array(
            'sites' => $this->getSiteManager()->getSiteChoices()
        ));
    }

    public function listAction($site)
    {
        if ($this->isGranted('ROLE_JAPAN_ADMIN')) {
            $site = 2;
        }

        $this->addNewsBreadcrumb();
        $this->addSiteBreadcrumbs($site);

        $em = $this
            ->getDoctrine()
            ->getEntityManager();

        $site = $em->getRepository('SpoutletBundle:Site')->find($site);

        $repo   = $em->getRepository('NewsBundle:News');
        $query  = $repo->getFindNewsForSiteQuery($site);

        $pager = new PagerFanta(new DoctrineORMAdapter($query));
        $pager->setCurrentPage($this->getRequest()->get('page', 1));

        $giveaways = $this->getGiveawayRepo()->findAllForSite($site);

        return $this->render('NewsBundle:Admin:list.html.twig', array(
            'news'  => $pager,
            'site'  => $site,
        ));
    }

    public function newAction()
    {
        $this->addNewsBreadcrumb()->addChild('New');
        $news       = new News();
        $tagManager = $this->getTagManager();
        $form       = $this->createForm(new CreateNewsFormType($news, $tagManager), $news);
        $request    = $this->getRequest();

        if ($this->processForm($form, $request)) {

            $this->setFlash('success', $this->trans('platformd.admin.news.created'));

            return $this->redirect($this->generateUrl('NewsBundle_admin_homepage'));
        }

        return $this->render('NewsBundle:Admin:new.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function editAction($id)
    {
        $this->addNewsBreadcrumb()->addChild('Edit');
        $em         = $this->getDoctrine()->getEntityManager();
        $tagManager = $this->getTagManager();

        $news = $em
            ->getRepository('NewsBundle:News')
            ->findOneBy(array('id' => $id));

        if (!$news) {

            throw $this->createNotFoundException('Unable to retrieve news item #'.$id);
        }

        $tagManager->loadTagging($news);

        $form = $this->createForm(new CreateNewsFormType($news, $tagManager), $news);
        $request = $this->getRequest();

        if ($this->processForm($form, $request)) {
            $this->setFlash('success', $this->trans('platformd.admin.news.modified'));

            return $this->redirect($this->generateUrl('NewsBundle_admin_homepage'));
        }

        return $this->render('NewsBundle:Admin:edit.html.twig', array(
            'form' => $form->createView(),
            'news' => $news
        ));
    }

    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $news = $em
            ->getRepository('NewsBundle:News')
            ->findOneBy(array('id' => $id));

        if (!$news) {

            throw $this->createNotFoundException('Unable to retrieve news item #'.$id);
        }

        $em->remove($news);
        $em->flush();

        $this->setFlash('success', $this->trans('platformd.admin.news.deleted'));

        return $this->redirect($this->generateUrl('NewsBundle_admin_homepage'));
    }

    private function processForm(Form $form, Request $request)
    {
        $em         = $this->getDoctrine()->getEntityManager();
        $tagManager = $this->getTagManager();

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {

                // either persist the image, or remove it
                /** @var $news \Platformd\NewsBundle\Entity\News */
                $news = $form->getData();

                $mUtil = new MediaUtil($this->getDoctrine()->getEntityManager());

                if (!$mUtil->persistRelatedMedia($news->getImage())) {
                    $news->setImage(null);
                }

                $tags = $tagManager->loadOrCreateTags($tagManager->splitTagNames($form['tags']->getData()));

                $isEdit = $news->getId();

                $em->persist($news);
                $em->flush();

                $isEdit ? $tagManager->replaceTags($tags, $news) : $tagManager->addTags($tags, $news);

                $tagManager->saveTagging($news);

                return true;
            }
        }

        return false;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    private function addNewsBreadcrumb()
    {
        $this->getBreadcrumbs()->addChild('News', array(
            'route' => 'NewsBundle_admin_homepage'
        ));

        return $this->getBreadcrumbs();
    }

    private function addSiteBreadcrumbs($site)
    {
        if ($site) {

            $this->getBreadcrumbs()->addChild($this->getSiteManager()->getSiteName($site), array(
                'route' => 'NewsBundle_admin_siteList',
                'routeParameters' => array('site' => $site)
            ));
        }

        return $this->getBreadcrumbs();
    }

    private function getTagManager()
    {
        return $this->get('platformd.tags.model.tag_manager');
    }
}
