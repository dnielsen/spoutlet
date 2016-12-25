<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Platformd\UserBundle\Entity\User;

class PurifiedTextareaType extends AbstractType
{
    private $basicPurifierTransformer;
    private $adminPurifierTransformer;
    private $security;

    public function __construct(
        DataTransformerInterface $basicPurifierTransformer,
        DataTransformerInterface $adminPurifierTransformer,
        SecurityContextInterface $security
    ) {
        $this->basicPurifierTransformer = $basicPurifierTransformer;
        $this->adminPurifierTransformer = $adminPurifierTransformer;
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->security->getToken()->getUser();

        if ($user && $user instanceof User && $user->hasRole('ROLE_SUPER_ADMIN')) {
            $builder->addModelTransformer($this->adminPurifierTransformer);
        } else {
            $builder->addModelTransformer($this->basicPurifierTransformer);
        }
    }

    public function getParent()
    {
        return 'textarea';
    }

    public function getName()
    {
        return 'purifiedTextarea';
    }
}
