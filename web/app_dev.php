<?php
umask(0000); // This will let the permissions be 0777

// this check prevents access to debug front controllers that are deployed by accident to production servers.
// feel free to remove this, extend it, or make something more sophisticated.
if (!in_array(@$_SERVER['REMOTE_ADDR'], array(
    '127.0.0.1',
    '192.168.1.81',
    '192.168.1.76',
    '192.168.2.2',
    '192.168.1.5',
    '192.168.56.1',
    '::1',
))) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}

// Setting $_ENV['HOME'] to avoid excessive, unnecessary errors in apache log file
$_ENV['HOME'] = '/home/ubuntu';

require_once __DIR__.'/../app/bootstrap.php.cache';
require_once __DIR__.'/../app/AppKernel.php';

use Symfony\Component\HttpFoundation\Request;

$kernel = new AppKernel('dev', true);
$kernel->loadClassCache();

$request = Request::createFromGlobals();

$request->trustProxyData();

$kernel->handle($request)->send();
