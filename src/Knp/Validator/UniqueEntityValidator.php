<?php

namespace Knp\Validator;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator as BaseUniqueEntityValidator;
use Symfony\Component\Validator\Constraint;
use Platformd\SpoutletBundle\Entity\RsvpAttendee;
use Symfony\Bridge\Doctrine\RegistryInterface;

class UniqueEntityValidator extends BaseUniqueEntityValidator
{
    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    public function isValid($entity, Constraint $constraint)
    {
        if (!is_array($constraint->fields) && !is_string($constraint->fields)) {
            throw new UnexpectedTypeException($constraint->fields, 'array');
        }

        $fields = (array) $constraint->fields;

        if (0 === count($fields)) {
            throw new ConstraintDefinitionException('At least one field has to be specified.');
        }

        $em = $this->registry->getEntityManager($constraint->em);

        $className = $this->context->getCurrentClass();
        $class = $em->getClassMetadata($className);

        $criteria = array();
        foreach ($fields as $fieldName) {
            if (!isset($class->reflFields[$fieldName])) {
                throw new ConstraintDefinitionException('Only field names mapped by Doctrine can be validated for uniqueness.');
            }

            $criteria[$fieldName] = $class->reflFields[$fieldName]->getValue($entity);

            if (null === $criteria[$fieldName]) {
                return true;
            }

            if (isset($class->associationMappings[$fieldName])) {
                $relatedClass = $em->getClassMetadata($class->associationMappings[$fieldName]['targetEntity']);
                $relatedId = $relatedClass->getIdentifierValues($criteria[$fieldName]);

                if (count($relatedId) > 1) {
                    throw new ConstraintDefinitionException(sprintf('Associated entities are not allowed to have more than one identifier field to be part of a unique constraint in %s#%s.', $class->name, $fieldName));
                }
                $criteria[$fieldName] = array_pop($relatedId);
            }
        }

        $repository = $em->getRepository($className);
        $result = $repository->findBy($criteria);

        /* If no entity matched the query criteria or a single entity matched,
         * which is the same as the entity being validated, the criteria is
         * unique.
         */
        if (0 === count($result) || (1 === count($result) && $entity === $result[0])) {
            return true;
        }

        $oldPath = $this->context->getPropertyPath();
        $this->context->setPropertyPath( empty($oldPath) ? $fields[0] : $oldPath.'.'.$fields[0]);
        $this->context->addViolation($constraint->message, array(), $criteria[$fields[0]]);
        $this->context->setPropertyPath($oldPath);

        return true; // all true, we added the violation already!
    }
}

