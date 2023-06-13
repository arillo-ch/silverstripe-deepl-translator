<?php
namespace Arillo\Deepl;

use DeepL\TextResult;
use DeepL\Translator;
use DeepL\TranslatorOptions;
use SilverStripe\Core\Environment;
use SilverStripe\Security\PermissionProvider;

class Deepl implements PermissionProvider
{
    const USE_DEEPL = 'USE_DEEPL';

    public static $timeout = TranslatorOptions::DEFAULT_TIMEOUT;
    public static $max_retries = TranslatorOptions::DEFAULT_MAX_RETRIES;

    public function providePermissions()
    {
        return [
            self::USE_DEEPL => _t(
                'Arillo\Deepl.USE_DEEPL',
                'Can user use deepl in CMS'
            ),
        ];
    }

    public static function module_path()
    {
        return substr(realpath(__DIR__), 0, -4);
    }

    public static function get_apikey()
    {
        return Environment::getEnv('DEEPL_APIKEY');
    }

    public static function create_translator(): ?Translator
    {
        $apiKey = Environment::getEnv('DEEPL_APIKEY');

        if (!$apiKey) {
            return null;
        }

        return new Translator($apiKey, [
            TranslatorOptions::TIMEOUT => self::$timeout,
            TranslatorOptions::MAX_RETRIES => self::$max_retries,
        ]);
    }

    /**
     * @param @param $text string|string[]
     * @param string $toLanguage
     * @param string|null $fromLanguage
     * @return TextResult|array
     */
    public static function translate(
        $text,
        string $toLanguage,
        ?string $fromLanguage = null
    ) {
        $translator = self::create_translator();

        if (!$translator) {
            return null;
        }

        return $translator->translateText($text, $fromLanguage, $toLanguage, [
            'tag_handling' => 'html',
        ]);
    }

    public static function usage()
    {
        $translator = self::create_translator();

        if (!$translator) {
            return null;
        }

        return $translator->getUsage();
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
