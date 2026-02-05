<?php
namespace Arillo\Deepl;

use SilverStripe\Model\List\ArrayList;

class SerialTranslator
{
    public static function run(ArrayList $translationDataObjects, $to, $from)
    {
        foreach ($translationDataObjects as $item) {
            $item->setField(
                'Results',
                Deepl::translate($item->getField('Texts'), $to, $from)
            );
        }

        return $translationDataObjects;
    }
}
