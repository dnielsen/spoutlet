<?php

namespace Platformd\EventBundle\Validator;

use Symfony\Component\Validator\Constraint;

class GroupEventUniqueSlugValidator extends EventUniqueSlugValidator
{
    public function isValid($entity, Constraint $constraint)
    {
        $criteria = array(
            'slug' => $entity->getSlug(),
            'group' => $entity->getGroup()->getId()
        );

        $result = $this->eventService->findBy($criteria);

        /*
         * This little spot taken from UniqueEntityValidator
         *
         * If no entity matched the query criteria or a single entity matched,
         * which is the same as the entity being validated, the criteria is
         * unique.
         */
        if (0 == count($result) || (1 == count($result) && $entity === $result[0])) {
            return true;
        }

        $oldPath = $this->context->getPropertyPath();
        $this->context->setPropertyPath(empty($oldPath) ? 'slug' : $oldPath.'.slug');
        $this->context->addViolation($constraint->message, array(), $entity->getSlug());
        $this->context->setPropertyPath($oldPath);

        return true; // all true, we added the violation already!
    }
}
