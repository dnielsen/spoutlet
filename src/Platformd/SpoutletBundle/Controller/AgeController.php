<?php

namespace Platformd\SpoutletBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Form;
use Platformd\SpoutletBundle\Age\InsufficientAgeListener;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Handles the age verification process
 */
class AgeController extends Controller
{

    protected $returnUrl;

    /**
     * Displays and processes the "Confirm" birthday page
     *
     * @Template
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function verifyAgeAction($returnUrl = null, Request $request)
    {
        $this->returnUrl = $returnUrl;

        if ($this->getAgeManager()->isUsersAgeVerified()) {
            $this->onSuccess();
        }

        $constraint = new NotBlank();
        $constraint->message = $this->trans('error.fill_in_birthday');

        $form = $this->createForm('birthday', null, array(
            'csrf_protection' => false,
            'empty_value' => '--', 'required' => true,
            'years' => range(date('Y'), 1940),
            'validation_constraint' => $constraint,
            'invalid_message' => ''
        ));

        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {
                return $this->onSuccess($form);
            }
        }

        return array(
            'form' => $form->createView(),
            'return_url' => urlencode($returnUrl)
        );
    }

    /**
     * Called after successfully verifying your age:
     *
     *      * Records your age in the age manager
     *      * Tries to redirect you back to where you came from
     *
     * @param \Symfony\Component\Form\Form $form
     */
    private function onSuccess(Form $form = null)
    {
        // if this was a result of a form post, lets get the data and set it
        if ($form) {
            $this->getAgeManager()->setUsersBirthday($form->getData());
        }

        if (!$this->returnUrl) {
            $this->returnUrl = $this->generateUrl('default_index');
        }

        return $this->redirect($this->returnUrl);
    }
}
