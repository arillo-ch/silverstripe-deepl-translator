<?php
namespace Arillo\Deepl;

use SilverStripe\Core\Environment;

class Deepl
{
    public static function get_apikey()
    {
        return Environment::getEnv('DEEPL_APIKEY');
    }
}
