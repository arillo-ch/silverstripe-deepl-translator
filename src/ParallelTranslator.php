<?php
namespace Arillo\Deepl;

use function Amp\Promise\wait;
use SilverStripe\ORM\ArrayList;
use function Amp\ParallelFunctions\parallelMap;

class ParallelTranslator
{
    public static function run(ArrayList $translationDataObjects, $to, $from)
    {
        $translate = function ($item) use ($to, $from) {
            try {
                $r = Deepl::translate($item->getField('Source'), $to, $from);
                if ($r->text) {
                    $item->setField('Result', $r->text);
                }
            } catch (\Throwable $th) {
                $item->setField('Error', $th->getMessage());
            }

            return $item;
        };

        $result = wait(
            parallelMap($translationDataObjects->toArray(), $translate)
        );

        return new ArrayList($result);
    }
    // public static function run($fields, $data, $to, $from)
    // {
    //     $translate = function ($field) use ($data, $to, $from) {
    //         $node = $data[$field];
    //         $result = [
    //             'field' => $field,
    //             'source' => $node['source'],
    //         ];
    //         try {
    //             $r = Deepl::translate($node['source'], $to, $from);
    //             if ($r->text) {
    //                 $result['result'] = $r->text;
    //             }
    //         } catch (\Throwable $th) {
    //             $result['error'] = $th->getMessage();
    //         }
    //         return $result;
    //     };
    //     return wait(parallelMap($fields, $translate));
    // }
}
