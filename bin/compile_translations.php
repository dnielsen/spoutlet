<?php
umask(0000); // This will let the permissions be 0777

require_once __DIR__.'/../app/bootstrap.php.cache';
require_once __DIR__.'/../app/AppKernel.php';
//require_once __DIR__.'/../app/AppCache.php';

use Symfony\Component\HttpFoundation\Request;

$kernel = new AppKernel('dev', true);
$kernel->loadClassCache();
$kernel->boot();

$translator = $kernel->getContainer()->get('translator');

$locales = array('cn', 'jp');

foreach ($locales as $locale) {
    $results = array();
    $catalog = $translator->getMessageCatalog($locale);

    $domains = $catalog->getDomains();
    foreach ($domains as $domain) {
        foreach ($catalog->all($domain) as $key => $val) {
            $results[] = sprintf('%s: %s', $key, $val);
        }
    }

    file_put_contents('translations.'.$locale.'.txt', implode("\n", $results));
}