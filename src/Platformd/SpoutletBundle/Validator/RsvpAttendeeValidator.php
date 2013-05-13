<?php

namespace Platformd\SpoutletBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Doctrine\ORM\EntityManager;

class RsvpAttendeeValidator extends ConstraintValidator
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function isValid($entity, Constraint $constraint)
    {
        $rsvpAttendeeRepo = $this->em->getRepository('SpoutletBundle:RsvpAttendee');

        if (!$entity->getRsvp()->isCodeRequired()) {
            return true;
        }

        if (!$entity->getCode() || !$entity->getCode()->getValue()) {
            $oldPath = $this->context->getPropertyPath();
            $this->context->setPropertyPath($oldPath.'.code');

            $this->context->addViolation(
                'Required',
                array(),
                null
            );

            $this->context->setPropertyPath($oldPath);
            return true;
        }

        if (!$entity->getCode()->getId()) {
            $oldPath = $this->context->getPropertyPath();
            $this->context->setPropertyPath($oldPath.'.code');

            $this->context->addViolation(
                'You must enter a valid RSVP Code to submit an RSVP.',
                array(),
                null
            );

            $this->context->setPropertyPath($oldPath);
            return true;
        }

        $result = $rsvpAttendeeRepo->findBy(array(
            'rsvp' => $entity->getRsvp()->getId(),
            'code' => $entity->getCode()->getId(),
        ));

        if (0 == count($result) || (1 == count($result) && $entity === $result[0])) {
            return true;
        }

        $oldPath = $this->context->getPropertyPath();
        $this->context->setPropertyPath($oldPath.'.code');

        $this->context->addViolation(
            $constraint->message,
            array(),
            null
        );

        $this->context->setPropertyPath($oldPath);
        return true;
    }
}
