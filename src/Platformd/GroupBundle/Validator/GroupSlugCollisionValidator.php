<?php

namespace Platformd\GroupBundle\Validator;

use Symfony\Component\Validator\ConstraintValidator,
    Symfony\Component\Validator\Constraint,
    Symfony\Component\Routing\Router
;

class GroupSlugCollisionValidator extends ConstraintValidator
{
    protected $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function isValid($entity, Constraint $constraint)
    {
        $matcher = $this->router->getMatcher();

        $routeInfo = $matcher->match('/'.$entity->getSlug());

        if ($routeInfo && isset($routeInfo['_route']) && $routeInfo['_route'] != 'group_show') {

            $oldPath = $this->context->getPropertyPath();
            $this->context->setPropertyPath(empty($oldPath) ? 'slug' : $oldPath.'.slug');
            $this->context->addViolation($constraint->fieldMessage, array(), $entity->getSlug());
            $this->context->setPropertyPath($oldPath);

            return false;
        }

        return true; // all true, we added the violation already!
    }
}
