<?php

$file = $container->getParameter('kernel.root_dir').'/../VERSION';
$version = 'dev';

if (file_exists($file)) {
    $version = file_get_contents($file);
    // stripe whitespace, to be safe (remove line break)
    $version = preg_replace('/\s\s+/', '', $version);
    $version = str_replace("\n", '', $version);
}

$container->setParameter('app.version', 'v'.$version);