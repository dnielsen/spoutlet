<?php
umask(0000); // This will let the permissions be 0777

require_once __DIR__.'/../app/bootstrap.php.cache';
require_once __DIR__.'/../app/AppKernel.php';
//require_once __DIR__.'/../app/AppCache.php';

use Symfony\Component\HttpFoundation\Request;

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();
//$kernel = new AppCache($kernel);

$request = Request::createFromGlobals();

$request->trustProxyData();

// Setting $_ENV['HOME'] to avoid excessive, unnecessary errors in apache log file
$_ENV['HOME'] = '/home/ubuntu';

$kernel->handle($request)->send();
