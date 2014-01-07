<?php
/**
 * Created by PhpStorm.
 * User: gimballock
 * Date: 12/10/13
 * Time: 7:52 PM
 */

namespace Platformd\IdeaBundle\Entity;

use Platformd\SpoutletBundle\Link\LinkableInterface;
use Platformd\UserBundle\Entity\User;

interface EntrySetScopeable extends LinkableInterface {
    public function createEntrySetRegistration();
    public function getEntrySetRegistration();
    public function getEntrySets();
    public function getId();
    public function isMemberOf(User $user);
} 