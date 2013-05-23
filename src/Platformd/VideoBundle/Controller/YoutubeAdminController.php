<?php

namespace Platformd\VideoBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller;
use Platformd\VideoBundle\Entity\YoutubeVideo;
use Platformd\VideoBundle\Entity\YoutubeVote;
use Platformd\VideoBundle\Form\Type\YoutubeMetricsType;
use Platformd\GroupBundle\Entity\GroupVideo;
use Platformd\SpoutletBundle\Util\CsvResponseFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use DateTime,
    DateInterval
;

use Pagerfanta\Pagerfanta,
    Pagerfanta\Adapter\DoctrineORMAdapter,
    Pagerfanta\Adapter\ArrayAdapter
;

class YoutubeAdminController extends Controller
{
    public function metricsAction(Request $request)
    {
        $page       = $request->query->get('page', 1);
        $filters    = $this->getFilterFormData();
        $form       = $this->createForm(new YoutubeMetricsType(), $filters);

        if($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            $data     = $form->getData();
            $fromDate = $data['fromDate'];
            $thruDate = $data['thruDate'];
            $keyWords = $data['keyWords'];

            if($thruDate) {
                $thruDate->add(DateInterval::createFromDateString('1439 minutes'));
            }

            $this->setFilterFormData(array('fromDate' => $fromDate, 'thruDate' => $thruDate, 'keyWords' => $keyWords));

            $adapter  = new ArrayAdapter($this->getVideoMetricResults($fromDate, $thruDate, $keyWords));
            $pager    = new Pagerfanta($adapter);
            $pager->setMaxPerPage(10)->setCurrentPage($page);
            $results  = $pager->getCurrentPageResults();

            return $this->render('VideoBundle:YoutubeAdmin:metrics.html.twig', array(
                'pager'     => $pager,
                'results'   => $results,
                'form'      => $form->createView(),
            ));
        }

        $fromDate = array_key_exists('fromDate', $filters) ? $filters['fromDate'] : null;
        $thruDate = array_key_exists('thruDate', $filters) ? $filters['thruDate'] : null;
        $keyWords = array_key_exists('keyWords', $filters) ? $filters['keyWords'] : null;

        $adapter  = new ArrayAdapter($this->getVideoMetricResults($fromDate, $thruDate, $keyWords));
        $pager    = new Pagerfanta($adapter);
        $pager->setMaxPerPage(10)->setCurrentPage($page);
        $results  = $pager->getCurrentPageResults();

        return $this->render('VideoBundle:YoutubeAdmin:metrics.html.twig', array(
            'pager'     => $pager,
            'results'   => $results,
            'form'      => $form->createView(),
        ));
    }

    public function exportMetricsAction()
    {
        $factory = new CsvResponseFactory();
        $factory->addRow(array(
            'Video Title',
            'Username',
            'Country',
            'Region',
            'Upload Date',
            'Votes',
            'Views',
            'Comments'
        ));

        $filters  = $this->getFilterFormData();
        $fromDate = array_key_exists('fromDate', $filters) ? $filters['fromDate'] : null;
        $thruDate = array_key_exists('thruDate', $filters) ? $filters['thruDate'] : null;
        $keyWords = array_key_exists('keyWords', $filters) ? $filters['keyWords'] : null;

        $results = $this->getVideoMetricResults($fromDate, $thruDate, $keyWords, false);

        foreach ($results as $result) {
            $factory->addRow(array(
                $result['video']->getTitle(),
                $result['video']->getAuthor()->getUsername(),
                $result['video']->getAuthor()->getCountry(),
                $result['video']->getSite()->getName(),
                $result['video']->getCreatedAt()->format('m/d/Y'),
                $result['voteCount'],
                $result['video']->getViews(),
                $result['commentCount']
            ));
        }

        return $factory->createResponse('Youtube_Video_Metrics.csv');
    }

    public function exportVideoCommentsAction($videoId)
    {
        $video      = $this->getYoutubeManager()->findOneBy(array('id' => $videoId));
        $thread     = $this->getThreadRepository()->findOneBy(array('id' => $video->getThreadId()));
        $comments   = $thread->getComments();

        $factory = new CsvResponseFactory();
        $factory->addRow(array(
            'Video Title',
            'Username',
            'Country',
            'Date',
            'Comment Made'
        ));

        foreach ($comments as $comment) {
            $factory->addRow(array(
                $video->getTitle(),
                $comment->getAuthor()->getUsername(),
                $comment->getAuthor()->getCountry(),
                $comment->getCreatedAt()->format('m/d/Y'),
                $comment->getBody()
            ));
        }

        $fileName = sprintf('Comments_For_%s.csv', $video->getTitle());

        return $factory->createResponse($fileName);
    }

    public function clearMetricsAction()
    {
        $this->setFilterFormData(array());

        return $this->redirect($this->generateUrl('youtube_admin_metrics'));
    }

    private function getVideoMetricResults($fromDate, $thruDate, $keyWords, $usePager = true, $maxPerPage = 10, $page = 1)
    {
        $metrics = array();
        $results = $this->getYoutubeManager()->findVideoMetrics($fromDate, $thruDate, $keyWords);

        foreach ($results as $result) {
            $commentCount   = $this->getThreadRepository()->getTotalCommentsByThreadId($result[0]->getThreadId());
            array_push($metrics, array('video' => $result[0], 'voteCount' => $result['voteCount'], 'commentCount' => $commentCount));
        }

        return $metrics;
    }

    private function getFilterFormData()
    {
        $session = $this->getRequest()->getSession();
        return $session->get('formValues', array());
    }

    private function setFilterFormData(array $data)
    {
        $session = $this->getRequest()->getSession();
        $session->set('formValues', $data);
    }

    private function getYoutubeManager()
    {
        return $this->get('platformd.model.youtube_manager');
    }

    private function getThreadRepository()
    {
        return $this->getDoctrine()->getRepository('SpoutletBundle:Thread');
    }

    private function getCommentRepository()
    {
        return $this->getDoctrine()->getRepository('SpoutletBundle:Comment');
    }
}
