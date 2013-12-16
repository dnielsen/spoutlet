<?php

namespace Platformd\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

use Platformd\SpoutletBundle\Controller\Controller;



class ApiController extends Controller
{
    public function entrySetAction($entrySetId)
    {
        $entrySet = $this->getDoctrine()->getRepository('IdeaBundle:EntrySet')->find($entrySetId);

        $response = new Response();

        if (!$entrySet){
            $response->setStatusCode(404);
            // Error message - not found
        }
        else {
            $response->setStatusCode(200);

            $entries = array();
            foreach ($entrySet->getEntries() as $entry) {
                $entries[] = array(
                    'id'    => $entry->getId(),
                    'name'  => $entry->getName(),
                );
            }

            $entrySetData = array(
                'meta'                 => array(
                        'self'  => $this->generateUrl('api_entrySet', array('entrySetId'=>$entrySet->getId()), true),
                        'mimetype' => "application/json"
                    ),
                'id'                    => $entrySet->getId(),
                'name'                  => $entrySet->getName(),
                'type'                  => $entrySet->getType(),
                'registrationId'        => $entrySet->getEntrySetRegistration()->getId(),
                'isSubmissionsActive'   => $entrySet->getIsSubmissionActive(),
                'isVotingActive'        => $entrySet->getIsVotingActive(),
                'allowedVoters'         => $entrySet->getAllowedVoters(),
                'entries'               => $entries,
            );

            $jsonEncoder = new JsonEncoder();
            $response->setContent($jsonEncoder->encode($entrySetData, $format = 'json'));
        }

        return $response;
    }

    public function entryAction($entryId)
    {
        $entry = $this->getDoctrine()->getRepository('IdeaBundle:Idea')->find($entryId);

        $response = new Response();

        if (!$entry){
            $response->setStatusCode(404);
            // Error message - not found
        }
        else {
            $response->setStatusCode(200);

            $entryData = array(
                'meta'                 => array(
                    'self'  => $this->generateUrl('api_entry', array('entryId'=>$entry->getId()), true),
                    'mimetype' => "application/json"
                ),
                'id'                    => $entry->getId(),
                'entrySetId'            => $entry->getEntrySet()->getId(),
                'creator'               => $entry->getCreator()->getUserName(),
                'createdAt'             => $entry->getCreatedAt(),
                'name'                  => $entry->getName(),
                'description'           => $entry->getDescription(),
            );

            $jsonEncoder = new JsonEncoder();
            $response->setContent($jsonEncoder->encode($entryData, $format = 'json'));
        }

        return $response;
    }

}

?>