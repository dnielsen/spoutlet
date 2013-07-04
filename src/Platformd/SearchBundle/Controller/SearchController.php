<?php

namespace Platformd\SearchBundle\Controller;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException
;

use Platformd\SpoutletBundle\Controller\Controller,
    Platformd\SearchBundle\Form\Type\SearchType,
    Platformd\SearchBundle\Model\SearchManager,
    Platformd\SearchBundle\ViewModel\search_result_data,
    Platformd\SearchBundle\ViewModel\search_results_data,
    Platformd\TagBundle\Model\TaggableInterface
;

use Pagerfanta\Adapter\ArrayAdapter,
    Pagerfanta\Pagerfanta
;

class SearchController extends Controller
{
    public function formAction(Request $request, $category = 'all')
    {
        $search    = array('criteria', 'category' => $category);
        $watermark = $this->trans($request->get('watermark'));

        $form = $this->createFormBuilder($search)
            ->add('criteria', 'text', array(
                'required' => false,
                'attr' => array(
                    'class' => 'search-criteria '. $category,
                )
            ))
            ->add('category', 'hidden', array(
                'attr' => array(
                    'class' => 'search-category',
                )
            ))
            ->getForm();

        return $this->render('SearchBundle::_search.html.twig', array(
            'form'      => $form->createView(),
            'category'  => $category,
            'watermark' => $watermark,
        ));
    }

    public function logSearchAction(Request $request)
    {
        $content = $request->getContent();
        $params  = json_decode($content, true);

        if (empty($content) || !isset($params['criteria'])) {
            return new Response('');
        }

        $log = $this->getSearchManager()->logSearch($params['criteria']);

        return new Response($log ? 'Logged' : '');
    }

    public function resultsAction($category, $criteria, $page = 1)
    {
        $site = $this->getCurrentSite();

        if (!$site->getSiteFeatures()->getHasSearch()) {
            throw new NotFoundHttpException();
        }

        $category        = $category == 'all' ? null : $category;
        $searchManager   = $this->getSearchManager();
        $params          = array();
        $data            = new search_results_data();
        $data->results   = array();

        if ($page) {
            $perPage = SearchManager::SEARCH_RESULTS_PER_PAGE;
            $offset  = ($page - 1) * $perPage;
            $params['start'] = $offset;
        }

        $site       = $this->getCurrentSite();
        $searchData = $searchManager->search($criteria, $params, $site, $category);

        $search = array('criteria' => $criteria);
        $form   = $this->createFormBuilder($search)
            ->add('criteria', 'text', array('required' => false))
            ->getForm();

        if (!empty($searchData)) {

            $data->resultCount   = isset($searchData['allResultCount']) ? $searchData['allResultCount'] : null;
            $data->facets        = isset($searchData['facets']) ? $searchData['facets'] : null;

            if (isset($searchData['results'])) {
                foreach ($searchData['results'] as $searchResult) {
                    $result             = new search_result_data();
                    $entity             = $searchResult['entity'];

                    $result->title      = $entity->getSearchTitle();
                    $result->blurb      = strip_tags($entity->getSearchBlurb());
                    $result->category   = $searchResult['category'];
                    $result->url        = $this->getLinkableUrl($entity);
                    $result->date       = $entity->getSearchDate();
                    $result->extra_data = $searchManager->getExtraData($entity);
                    $result->tags       = array();

                    if ($entity instanceof TaggableInterface) {
                        $this->getTagManager()->loadTagging($entity);
                        $result->tags = $this->getTagManager()->getTagNames($entity);
                    }

                    $data->results[]    = $result;
                }
            }

            $pagerArray = array_pad(array(), $searchData['resultCount'], null);
            $pagerfanta = new Pagerfanta(new ArrayAdapter($pagerArray));

            $pagerfanta->setMaxPerPage(SearchManager::SEARCH_RESULTS_PER_PAGE);

            if ($page) {
                $pagerfanta->setCurrentPage($page);
            }

            $pagerParams = array('criteria' => $criteria);

            if ($category) {
                $pagerParams['category'] = $category;
            }

            $data->pager           = $pagerfanta;
            $data->pagerParams     = $pagerParams;
        }

        $data->criteria        = $criteria;
            $data->currentCategory = $category;

        $response = $this->render('SearchBundle::results.html.twig', array(
            'data' => $data,
            'form' => $form->createView(),
        ));

        $response->setSharedMaxAge(180);
        $response->setMaxAge(1);

        return $response;
    }

    private function getSearchManager()
    {
        return $this->get('platformd.model.search_manager');
    }

    private function getTagManager()
    {
        return $this->get('platformd.tags.model.tag_manager');
    }
}
