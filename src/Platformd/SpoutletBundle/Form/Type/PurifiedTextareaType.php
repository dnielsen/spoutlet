<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Security\Core\SecurityContextInterface;

class PurifiedTextareaType extends AbstractType
{
    private $basicPurifierTransformer;
    private $adminPurifierTransformer;
    private $security;

    public function __construct(
        DataTransformerInterface $basicPurifierTransformer,
        DataTransformerInterface $adminPurifierTransformer,
        SecurityContextInterface $security
    )
    {
        $this->basicPurifierTransformer = $basicPurifierTransformer;
        $this->adminPurifierTransformer = $adminPurifierTransformer;
        $this->security = $security;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        $roles = $this->security->getToken()->getRoles();

        if (in_array('ROLE_SUPER_ADMIN', $roles)) {
            $builder->appendClientTransformer($this->adminPurifierTransformer);
        } else {
            $builder->appendClientTransformer($this->basicPurifierTransformer);
        }
    }

    public function getParent(array $options)
    {
        return 'textarea';
    }

    public function getName()
    {
        return 'purified_textarea';
    }
}
