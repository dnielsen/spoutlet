<?php

namespace Platformd\GroupBundle\Validator;

use Symfony\Component\Validator\ConstraintValidator,
    Symfony\Component\Validator\Constraint,
    Symfony\Component\Routing\Router;

class GroupSlugCollisionValidator extends ConstraintValidator
{
    protected $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function validate($entity, Constraint $constraint)
    {
        $matcher = $this->router->getMatcher();

        $routeInfo = $matcher->match('/' . $entity->getSlug());

//        if ($routeInfo && isset($routeInfo['_route']) && $routeInfo['_route'] !== 'group_show') {
//            $oldPath = $this->context->getPropertyPath();
//            $this->context
//                ->buildViolation($constraint->fieldMessage, array(), $entity->getSlug())
//                ->atPath(empty($oldPath) ? 'slug' : $oldPath . '.slug')
//                ->addViolation();
//        }
    }
}
