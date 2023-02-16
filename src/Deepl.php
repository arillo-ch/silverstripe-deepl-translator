<?php
namespace Arillo\Deepl;

use DeepL\TextResult;
use DeepL\Translator;
use SilverStripe\Core\Environment;

class Deepl
{
    public static function get_apikey()
    {
        return Environment::getEnv('DEEPL_APIKEY');
    }

    public static function translate(
        string $text,
        string $toLanguage,
        ?string $fromLanguage = null
    ): ?TextResult {
        $apiKey = Environment::getEnv('DEEPL_APIKEY');

        if (!$apiKey) {
            return null;
        }

        return (new Translator($apiKey))->translateText(
            $text,
            $fromLanguage,
            $toLanguage
        );
    }

    public static function language_from_locale(?string $locale = null): ?string
    {
        $parsed = locale_parse($locale);
        if ($parsed && isset($parsed['language'])) {
            return $parsed['language'];
        }

        return null;
    }
}
