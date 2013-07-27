<?php

namespace Platformd\UserBundle\Validator;

use Symfony\Component\Validator\ConstraintValidator,
    Symfony\Component\Validator\Constraint
;

use Platformd\SpoutletBundle\Util\SiteUtil;

/**
 * Provides the actual validation for UniqueEntityValidator
 */
class UserValidator extends ConstraintValidator
{
    protected $siteUtil;

    const ERROR_MESSAGE_BIRTHDATE_REQUIRED  = 'birthdate_not_blank';
    const ERROR_MESSAGE_AGE_REQUIREMENT     = 'age_requirement_not_met';
    const ERROR_MESSAGE_HAS_ALIENWARE       = 'has_system_not_blank';

    public function __construct(SiteUtil $siteUtil)
    {
        $this->siteUtil = $siteUtil;
    }

    public function isValid($entity, Constraint $constraint)
    {
        $site = $this->siteUtil->getCurrentSite();

        if (!$site) {
            return true;
        }

        $config = $site->getSiteConfig();

        if ($config->getBirthdateRequired() && (!$entity->getBirthdate() || $entity->getBirthdate() == "")) {

            // Set error message at top of form
            $this->context->addViolation(self::ERROR_MESSAGE_BIRTHDATE_REQUIRED, array(), $entity->getBirthdate());

            // Set field error
            $oldPath = $this->context->getPropertyPath();
            $this->context->setPropertyPath(empty($oldPath) ? 'birthdate' : $oldPath.'.birthdate');
            $this->context->addViolation(self::ERROR_MESSAGE_BIRTHDATE_REQUIRED, array(), $entity->getBirthdate());
            $this->context->setPropertyPath($oldPath);
        }

        if($config->getBirthdateRequired() && $entity->getBirthdate()) {
            // figure out if they meet the age requirement
            if($entity->getAge() <= $config->getMinAgeRequirement()) {
                // Set error message at top of form
                $this->context->addViolation(self::ERROR_MESSAGE_AGE_REQUIREMENT, array(), $entity->getBirthdate());

                // Set field error
                $oldPath = $this->context->getPropertyPath();
                $this->context->setPropertyPath(empty($oldPath) ? 'birthdate' : $oldPath.'.birthdate');
                $this->context->addViolation(self::ERROR_MESSAGE_AGE_REQUIREMENT, array(), $entity->getBirthdate());
                $this->context->setPropertyPath($oldPath);
            }
        }

        if($entity->getHasAlienwareSystem() === null) {
                // Set error message at top of form
                $this->context->addViolation(self::ERROR_MESSAGE_HAS_ALIENWARE, array(), $entity->getHasAlienwareSystem());

                // Set field error
                $oldPath = $this->context->getPropertyPath();
                $this->context->setPropertyPath(empty($oldPath) ? 'hasAlienwareSystem' : $oldPath.'.hasAlienwareSystem');
                $this->context->addViolation(self::ERROR_MESSAGE_HAS_ALIENWARE, array(), $entity->getHasAlienwareSystem());
                $this->context->setPropertyPath($oldPath);
        }

        return true;
    }
}
