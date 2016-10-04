<?php
/**
 * test suite bootstrap.
 *
 * Tries to include Composer vendor/autoload.php; dies if it does not exist.
 *
 * @category  Location
 * @author    Carsten Witt <tomkyle@posteo.de>
 */

$autoloader_file = __DIR__ . '/../vendor/autoload.php';
if (!is_readable( $autoloader_file )) {
    die( PHP_EOL . "Missing Composer's vendor/autoload.php; run 'composer update' first." . PHP_EOL . PHP_EOL );
}
$autoloader = require $autoloader_file;
$autoloader->addPsr4('tests\\',  __DIR__.'/src');
$autoloader->addPsr4('mocks\\',  __DIR__.'/mocks');
