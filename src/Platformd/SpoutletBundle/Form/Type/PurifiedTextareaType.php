<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Platformd\UserBundle\Entity\User;

class PurifiedTextareaType extends AbstractType
{
    private $basicPurifierTransformer;
    private $adminPurifierTransformer;
    private $tokenStorage;

    public function __construct(
        DataTransformerInterface $basicPurifierTransformer,
        DataTransformerInterface $adminPurifierTransformer,
        TokenStorageInterface $tokenStorage
    ) {
        $this->basicPurifierTransformer = $basicPurifierTransformer;
        $this->adminPurifierTransformer = $adminPurifierTransformer;
        $this->tokenStorage = $tokenStorage;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if ($user && $user instanceof User && $user->hasRole('ROLE_SUPER_ADMIN')) {
            $builder->addViewTransformer($this->adminPurifierTransformer);
        } else {
            $builder->addViewTransformer($this->basicPurifierTransformer);
        }
    }

    public function getParent()
    {
        return TextareaType::class;
    }

    public function getBlockPrefix()
    {
        return 'purifiedTextarea';
    }
}
