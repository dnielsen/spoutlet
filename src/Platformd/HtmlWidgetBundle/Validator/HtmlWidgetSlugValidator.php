<?php

namespace Platformd\HtmlWidgetBundle\Validator;

use Symfony\Component\Validator\ConstraintValidator,
    Symfony\Component\Validator\Constraint
;

use Platformd\HtmlWidgetBundle\Model\HtmlWidgetManager;

/**
 * Provides the actual validation for UniqueEntityValidator
 */
class HtmlWidgetSlugValidator extends ConstraintValidator
{
    protected $htmlWidgetManager;

    public function __construct(HtmlWidgetManager $htmlWidgetManager)
    {
        $this->htmlWidgetManager = $htmlWidgetManager;
    }

    public function isValid($widget, Constraint $constraint)
    {
        // @TODO add more criteria like sites, etc...
        $criteria = array(
            'slug'  => $widget->getSlug(),
            'sites' => $widget->getSites()->toArray(),
        );

        $result = $this->htmlWidgetManager->findAllBy($criteria);

        if (0 == count($result) || (1 == count($result) && $widget === $result[0])) {
            return true;
        }

        $oldPath = $this->context->getPropertyPath();
        $this->context->setPropertyPath(empty($oldPath) ? 'slug' : $oldPath.'.slug');
        $this->context->addViolation($constraint->message, array(), $widget->getSlug());
        $this->context->setPropertyPath($oldPath);

        return true; // all true, we added the violation already!
    }
}
