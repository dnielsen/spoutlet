<?php

namespace Platformd\SpoutletBundle\Controller;

use Platformd\SpoutletBundle\Controller\Controller,
    Platformd\SpoutletBundle\Exception\InsufficientAgeException,
    Platformd\UserBundle\Exception\UserRegistrationTimeoutException,
    Platformd\UserBundle\Exception\ApiRequestException
;

use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\Request
;

class ApiController extends Controller
{
    public function createAccountAction(Request $request)
    {
        $response   = new Response();
        $response->headers->set('Content-type', 'text/json; charset=utf-8');

        $action     = json_decode($request->getContent(), true);
        $errors     = $this->process($action['data']);

        if (count($errors) > 0) {
            // set errors and return response
            $response->setContent(json_encode(array('success' => false, 'errors' => $this->getErrorKeys($errors))));
            return $response;
        }

        // success!
        $response->setContent(json_encode(array('success' => true)));
        return $response;
    }

    private function process($data)
    {
        $um     = $this->getUserManager();
        $user   = $um->createUser();

        $user->setUsername(isset($data['username']) ? $data['username'] : null);
        $user->setEmail(isset($data['email']) ? $data['email'] : null);
        $user->setBirthdate(isset($data['birth_date']) ? \DateTime::createFromFormat('Y-m-d', $data['birth_date']) : null);
        $user->setCountry(isset($data['country']) ? $data['country'] : null);
        $user->setIpAddress(isset($data['creation_ip_address']) ? $data['creation_ip_address'] : null);
        $user->setFirstname(isset($data['first_name']) ? $data['first_name'] : null);
        $user->setLastname(isset($data['last_name']) ? $data['last_name'] : null);
        $user->setState(isset($data['state']) ? $data['state'] : null);
        $user->setHasAlienwareSystem(false);
        $user->setPassword('');
        $user->setCreated(new \DateTime('now'));
        $user->setUpdated(new \DateTime('now'));

        if ($user->getCountry() == 'US') {
            $user->setSubscribedAlienwareEvents(true);
        }

        $validator = $this->get('validator');

        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            return $errors;
        }

        $um->updateUser($user);

        $this->sendConfirmationEmail($user);

/*        $this->apiManager->updateRemoteUserData(array(
            'uuid' => $user->getUuid(),
            'created' => $user->getCreated()->format('Y-m-d H:i:s'),
            'last_updated' => $user->getUpdated()->format('Y-m-d H:i:s'),
        ));*/

        return array();
    }

    private function getErrorKeys($errors) {
        $errorMessages = array();

        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessageTemplate();
        }

        return array_values(array_unique($errorMessages));
    }

    private function sendConfirmationEmail($user) {
        $mailer = $this->get('platformd_user.mailer');

        $mailer->sendApiConfirmationEmailMessage($user);
    }
}
