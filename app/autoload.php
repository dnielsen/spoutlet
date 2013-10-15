<?php

use Symfony\Component\ClassLoader\UniversalClassLoader;
use Doctrine\Common\Annotations\AnnotationRegistry;

require_once __DIR__.'/../vendor/amazonwebservices/aws-sdk-for-php/sdk.class.php';

$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
    'Symfony'                        => array(__DIR__.'/../vendor/symfony/src', __DIR__.'/../vendor/bundles'),
    'Sensio'                         => __DIR__.'/../vendor/bundles',
    'JMS'                            => __DIR__.'/../vendor/bundles',
    'Doctrine\\Common\\DataFixtures' => __DIR__.'/../vendor/doctrine-fixtures/lib',
    'Doctrine\\Common'               => __DIR__.'/../vendor/doctrine-common/lib',
    'Doctrine\\DBAL\\Migrations'     => __DIR__.'/../vendor/doctrine-migrations/lib',
    'Doctrine\\DBAL'                 => __DIR__.'/../vendor/doctrine-dbal/lib',
    'Doctrine'                       => __DIR__.'/../vendor/doctrine/lib',
    'Monolog'                        => __DIR__.'/../vendor/monolog/src',
    'Assetic'                        => __DIR__.'/../vendor/assetic/src',
    'Metadata'                       => __DIR__.'/../vendor/metadata/src',
    'FOS'                            => __DIR__.'/../vendor/bundles',
    'EWZ'                            => __DIR__.'/../vendor/bundles',
    'Stof'                           => __DIR__.'/../vendor/bundles',
    'Gedmo'                          => __DIR__.'/../vendor/gedmo-doctrine-extensions/lib',
    'WhiteOctober\PagerfantaBundle' => __DIR__.'/../vendor/bundles',
    'Pagerfanta'                    => __DIR__.'/../vendor/pagerfanta/src',
    'Knp\\Bundle'                   => __DIR__.'/../vendor/bundles',
    'Gaufrette'                     => __DIR__.'/../vendor/gaufrette/src',
    'MediaExposer'                  => __DIR__.'/../vendor/media-exposer/src',
    'Behat\BehatBundle'             => __DIR__.'/../vendor/bundles',
    'Behat\MinkBundle'              => __DIR__.'/../vendor/bundles',
    'Knp\Bundle'                    => __DIR__.'/../vendor/bundles',
    'Knp\Menu'                      => __DIR__.'/../vendor/KnpMenu/src',
    'Liip'                          => __DIR__.'/../vendor/bundles',
    'Imagine'                       => __DIR__.'/../vendor/imagine/lib',
    'Cybernox'                      => __DIR__.'/../vendor/bundles',
    'Exercise'                      => __DIR__. '/../vendor/bundles',
    'Vich'                          => __DIR__.'/../vendor/bundles',
    'HPCloud'                       => __DIR__.'/../vendor/HpCloudPhp/src' 
));
$loader->registerPrefixes(array(
    'Twig_Extensions_' => __DIR__.'/../vendor/twig-extensions/lib',
    'Twig_'            => __DIR__.'/../vendor/twig/lib',
    'PHPParser'        => __DIR__.'/../vendor/php-parser/lib',
    'HTMLPurifier'     => __DIR__.'/../vendor/htmlpurifier/library',
));

// intl
if (!function_exists('intl_get_error_code')) {
    require_once __DIR__.'/../vendor/symfony/src/Symfony/Component/Locale/Resources/stubs/functions.php';

    $loader->registerPrefixFallbacks(array(__DIR__.'/../vendor/symfony/src/Symfony/Component/Locale/Resources/stubs'));
}

$loader->registerNamespaceFallbacks(array(
    __DIR__.'/../src',
));
$loader->register();

AnnotationRegistry::registerLoader(function($class) use ($loader) {
    $loader->loadClass($class);
    return class_exists($class, false);
});
AnnotationRegistry::registerFile(__DIR__.'/../vendor/doctrine/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');

require __DIR__.'/../vendor/swiftmailer/lib/swift_required.php';
require __DIR__.'/../external_data/maxmind/GeoIP.inc';

// uncomment to see stack traces in PHPUnit
// xdebug_enable();
ini_set('xdebug.max_nesting_level', 110);
