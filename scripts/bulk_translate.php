<?php
/**
 * Expects a cli arg named base_path with the value of BASE_PATH.
 * Expects a json encoded cli arg named payload ['Title' => ['source' => 'Startseite DE', 'result' => null]].
 * Expects a cli arg named to with the value of a language e.g. 'fr'.
 * Expects a cli arg named from with the value of a language e.g. 'fr'.
 */
$params = array_slice($argv, 1);
$queryString = implode('&', $params);
parse_str($queryString, $_GET);

if (
    !$_GET['payload'] ||
    !$_GET['base_path'] ||
    !$_GET['to'] ||
    !$_GET['from']
) {
    echo json_encode([]);
    exit(0);
}

require $_GET['base_path'] . '/vendor/autoload.php';

use Arillo\Deepl\Deepl;
use function Amp\ParallelFunctions\parallelMap;
use function Amp\Promise\wait;

$data = json_decode($_GET['payload'], true);
$fields = array_keys($data);
$to = $_GET['to'];
$from = $_GET['from'];

$translate = function ($field) use ($data, $to, $from) {
    $node = $data[$field];
    $result = [
        'field' => $field,
        'source' => $node['source'],
    ];
    try {
        $r = Deepl::translate($node['source'], $to, $from);
        if ($r->text) {
            $result['result'] = $r->text;
        }
    } catch (\Throwable $th) {
        $result['error'] = $th->getMessage();
    }
    return $result;
};

// $start = \microtime(true);
$result = wait(parallelMap($fields, $translate));
// var_dump('Took ' . (\microtime(true) - $start) . ' milliseconds');

echo json_encode($result);
exit(0);
