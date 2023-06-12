<?php
/**
 * Expects a cli arg named `basepath` with the value of BASE_PATH.
 * Expects a cli arg named `classname` with the value of DataObject classname e.g. 'Page'.
 * Expects a cli arg named `id` with the value of id e.g. '1'.
 * Expects a cli arg named `to` with the value of a locale e.g. 'de_CH'.
 * Expects a cli arg named `from` with the value of a locale e.g. 'fr_CH'.
 * php parallel_translate_data_object.php basepath="/var/www/html" classname="StartPage" id=1 from="de_CH" to="fr_CH"
 */
$params = array_slice($argv, 1);
$queryString = implode('&', $params);
parse_str($queryString, $_GET);

if (
    empty($_GET['basepath']) ||
    empty($_GET['classname']) ||
    empty($_GET['id']) ||
    empty($_GET['to']) ||
    empty($_GET['from'])
) {
    exit(0);
}

require $_GET['basepath'] . '/vendor/autoload.php';

use SilverStripe\Core\CoreKernel;
use Arillo\Deepl\DataObjectWiseTranslationExtension;

var_dump('parallel_translate_data_object');

(new CoreKernel($_GET['basepath']))->boot();
$start = microtime(true);
$r = DataObjectWiseTranslationExtension::parallel_translate_data_object(
    $_GET['classname'],
    $_GET['id'],
    $_GET['to'],
    $_GET['from']
);
$duration = microtime(true) - $start;
var_dump("Translations took {$duration}");

exit(0);
