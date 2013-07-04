<?php

namespace Platformd\TagBundle\Model;

interface TaggableInterface
{
    function getTaggableType();

    function getTaggableId();

    function getTags();
}
