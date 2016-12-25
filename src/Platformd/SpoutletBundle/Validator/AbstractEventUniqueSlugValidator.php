<?php

namespace Platformd\SpoutletBundle\Validator;

use Symfony\Component\Validator\ConstraintValidator;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraint;

/**
 * Provides the actual validation for UniqueEntityValidator
 */
class AbstractEventUniqueSlugValidator extends ConstraintValidator
{
    private $em;

    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
    }

    public function validate($entity, Constraint $constraint)
    {
        // get the correct repository for this class
        //
        $className = $this->context->getCurrentClass();
        $repository = $this->em->getRepository($className);

        $criteria = array(
            'slug' => $entity->getSlug(),
            'locale' => $entity->getLocale(),
        );

        $result = $repository->findBy($criteria);

        /*
         * This little spot taken from UniqueEntityValidator
         *
         * If no entity matched the query criteria or a single entity matched,
         * which is the same as the entity being validated, the criteria is
         * unique.
         */
        if (0 == count($result) || (1 == count($result) && $entity === $result[0])) {
            return false;
        }

        $this->context->buildViolation($constraint->message)
            ->atPath(empty($oldPath) ? 'slug' : $oldPath . '.slug')
            ->addViolation();
    }
}