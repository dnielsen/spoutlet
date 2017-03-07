<?php

namespace Knp\MediaBundle\Model;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * If you implement this interface, then the owner will be set on creation
 */
interface MediaOwnerInterface
{
    public function setOwner(UserInterface $author);
}