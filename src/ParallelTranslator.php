<?php
namespace Arillo\Deepl;

use SilverStripe\Model\List\ArrayList;
use function Amp\Promise\wait;
use function Amp\ParallelFunctions\parallelMap;

/**
 * Runs multiple Deepl API requests in parallel.
 * CAUTION: Can only be used via CLI.
 */
class ParallelTranslator
{
    public static function run(ArrayList $translationDataObjects, $to, $from)
    {
        $translate = function ($item) use ($to, $from) {
            return Deepl::translate($item->getField('Texts'), $to, $from);
        };

        $objects = $translationDataObjects->toArray();
        $result = wait(parallelMap($objects, $translate));

        for ($i = 0; $i < count($result); $i++) {
            $objects[$i]->setField('Results', $result[$i]);
        }

        return new ArrayList($objects);
    }
}
