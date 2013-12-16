<?php

namespace Platformd\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

use Platformd\IdeaBundle\Entity\EntrySet;
use Platformd\IdeaBundle\Entity\EntrySetRegistryRepository;
use Platformd\SpoutletBundle\Controller\Controller;



class ApiController extends Controller
{
    public function entrySetAction($entrySetId)
    {
        $entrySet = $this->getDoctrine()->getRepository('IdeaBundle:EntrySet')->find($entrySetId);

        $response = new Response();

        if (!$entrySet){
            $response->setStatusCode(404);
        }
        else {
            $response->setStatusCode(200);

            //$jsonContent = $this->getJsonSerializer()->serialize($entrySet, 'json');
            //$response->setContent($jsonContent);

            $entrySetData = array(
                'id'            => $entrySet->getId(),
                'name'          => $entrySet->getName(),
                'type'          => $entrySet->getType(),
                'registration'  => $entrySet->getEntrySetRegistration(),
            );

            $jsonEncoder = new JsonEncoder();
            $response->setContent($jsonEncoder->encode($entrySetData, $format = 'json'));
        }

        return $response;
    }

    public function entryAction($entryId)
    {
        $entry = $this->getDoctrine()->getRepository('IdeaBundle:Idea')>find($entryId);

        if (!$entry){
            throw new NotFoundHttpException('Entry '.$entryId.' not found');
        }

        return $entry;
    }


    // This didn't work, went into a recursive loop when encoding EntrySet
    public function getJsonSerializer()
    {
        $normalizers = array(new GetSetMethodNormalizer());
        $encoders = array('json' => new JsonEncoder());
        return new Serializer($normalizers, $encoders);
    }

}

?>