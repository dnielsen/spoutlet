<?php

namespace Platformd\SpoutletBundle\Validator\Constraints;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Bundle\DoctrineBundle\Registry;
use Platformd\SpoutletBundle\Entity\BackgroundAd;

class UniqueBackgroundAdPerTimeValidator extends ConstraintValidator
{
    private $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function isValid($value, Constraint $constraint)
    {
        $nonUnique = $this->hasSameTimeForSites($value);
        if ($nonUnique) {
            $propertyPath = $this->context->getPropertyPath().'.date';
            $this->context->setPropertyPath($propertyPath);
            $this->context->addViolation($constraint->message, array(), null);

            return false;
        }

        return true;
    }

    private function getRepository()
    {
        return $this->doctrine->getRepository('SpoutletBundle:BackgroundAd');
    }

    private function hasSameTimeForSites(BackgroundAd $ad)
    {
        return $this->getRepository()->hasSameTimeForSites($ad);
    }
}

