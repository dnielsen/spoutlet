<?php

namespace Platformd\TagBundle\Model;

interface TaggableInterface
{
    public function getTaggableType();

    public function getTaggableId();

    public function getTags();
}
