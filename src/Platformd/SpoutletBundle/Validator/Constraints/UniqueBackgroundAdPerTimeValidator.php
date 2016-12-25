<?php

namespace Platformd\SpoutletBundle\Validator\Constraints;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Platformd\SpoutletBundle\Entity\BackgroundAd;

class UniqueBackgroundAdPerTimeValidator extends ConstraintValidator
{
    private $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function validate($value, Constraint $constraint)
    {
        $nonUnique = $this->hasSameTimeForSites($value);
        if ($nonUnique) {
            $oldPath = $this->context->getPropertyPath();

            $this->context->buildViolation($constraint->message)
                ->atPath($oldPath.'.dateStart')
                ->addVioldation();

//            $propertyPath = $oldPath.'.dateStart';
//            $this->context->setPropertyPath($propertyPath);
//            $this->context->addViolation('', array(), null);
//            $propertyPath = $oldPath.'.dateEnd';
//            $this->context->setPropertyPath($propertyPath);
//            $this->context->addViolation('', array(), null);
//            $this->context->setPropertyPath($oldPath);
        }
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

