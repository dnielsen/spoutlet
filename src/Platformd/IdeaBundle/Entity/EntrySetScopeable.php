<?php
/**
 * Created by PhpStorm.
 * User: gimballock
 * Date: 12/10/13
 * Time: 7:52 PM
 */

namespace Platformd\IdeaBundle\Entity;

use Platformd\SpoutletBundle\Link\LinkableInterface;

interface EntrySetScopeable extends LinkableInterface {
    public function createEntrySetRegistration();
    public function getEntrySetRegistration();
    public function getEntrySets();
    public function getId();
} 