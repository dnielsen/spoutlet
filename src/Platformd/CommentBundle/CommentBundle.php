<?php

namespace Platformd\CommentBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CommentBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSCommentBundle';
    }
}
