<?php
namespace Arillo\Deepl;

use DeepL\GlossaryEntries;
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

    public static function get_glossary_name_prefix()
    {
        return Environment::getEnv('DEEPL_GLOSSARY_NAME_PREFIX');
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

        $glossaryId = null;
        if (
            ($glossary = Glossary::by_source_and_target(
                $fromLanguage,
                $toLanguage
            )) &&
            $glossary->GlossaryId
        ) {
            $glossaryId = $glossary->GlossaryId;
        }

        return $translator->translateText($text, $fromLanguage, $toLanguage, [
            'tag_handling' => 'html',
            'glossary' => $glossaryId,
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

    public static function list_glossaries()
    {
        $translator = self::create_translator();

        if (!$translator) {
            return null;
        }

        return $translator->listGlossaries();
    }

    public static function create_glossary(
        $name,
        $sourceLang,
        $targetLang,
        $entries
    ) {
        $translator = self::create_translator();

        if (!$translator) {
            return null;
        }

        return $translator->createGlossary(
            $name,
            $sourceLang,
            $targetLang,
            GlossaryEntries::fromEntries($entries)
        );
    }

    public static function get_glossary_entries($id)
    {
        $translator = self::create_translator();

        if (!$translator) {
            return null;
        }

        return $translator->getGlossaryEntries($id);
    }

    public static function delete_unused_glossaries(
        $excludeWhereNameStartsWith = null
    ) {
        $translator = self::create_translator();

        if (!$translator) {
            return null;
        }

        $activeGlossariesIds = Glossary::get()->column('GlossaryId');
        $glossaries = $translator->listGlossaries();

        foreach ($glossaries as $glossary) {
            if (
                !in_array($glossary->glossaryId, $activeGlossariesIds) &&
                (!!$excludeWhereNameStartsWith &&
                    strncmp(
                        $glossary->name,
                        $excludeWhereNameStartsWith,
                        strlen($excludeWhereNameStartsWith)
                    ) === 0)
            ) {
                $translator->deleteGlossary($glossary);
            }
        }
    }

    public static function language_from_locale(?string $locale = null): ?string
    {
        $parsed = locale_parse($locale);

        if (
            $parsed &&
            isset($parsed['language']) &&
            $parsed['language'] == 'en'
        ) {
            if (
                $parsed &&
                isset($parsed['language']) &&
                $parsed['language'] == 'en'
            ) {
                if (isset($parsed['region']) && $parsed['region'] == 'GB') {
                    return 'en-GB';
                } else {
                    return 'en-US';
                }
            }
        }

        if ($parsed && isset($parsed['language'])) {
            return $parsed['language'];
        }

        return null;
    }
}
