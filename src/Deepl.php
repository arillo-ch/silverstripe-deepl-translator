<?php
namespace Arillo\Deepl;

use DeepL\TextResult;
use DeepL\Translator;
use DeepL\TranslatorOptions;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Config\Configurable;

class Deepl
{
    use Configurable;

    private static $timeout = TranslatorOptions::DEFAULT_TIMEOUT;
    private static $max_retries = TranslatorOptions::DEFAULT_MAX_RETRIES;

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

        $translator = new Translator($apiKey, [
            TranslatorOptions::TIMEOUT => self::config()->timeout,
            TranslatorOptions::MAX_RETRIES => self::config()->max_retries,
        ]);
        return $translator->translateText($text, $fromLanguage, $toLanguage);
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
