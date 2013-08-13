<?php

namespace Platformd\MediaBundle\Controller;

use Liip\ImagineBundle\Controller\ImagineController as BaseController;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ImagineController extends BaseController
{
    public function filterAction(Request $request, $path, $filter)
    {
        $targetPath = $this->cacheManager->resolve($request, $path, $filter);
        if ($targetPath instanceof Response) {
            return $targetPath;
        }

        $image = $this->dataManager->find($filter, $path);
        $response = $this->filterManager->get($request, $filter, $image, $path);

        if ($targetPath) {
            $response = $this->cacheManager->store($response, $targetPath, $filter);
        }

        return $response;
    }
}
