<?php

namespace Arillo\Deepl;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use TractorCow\Fluent\Model\Locale;

/**
 * @property string $StyleId
 * @property string $TargetLang
 */
class StyleRule extends DataObject
{
    private static $table_name = 'Arillo_Deepl_StyleRule';
    private static $singular_name = 'Style Rule';
    private static $plural_name = 'Style Rules';

    private static $db = [
        'StyleId' => 'Varchar(100)',
        'TargetLang' => 'Varchar(10)',
    ];

    private static $summary_fields = ['TargetLang', 'StyleId'];

    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    public function canDelete($member = null)
    {
        $activeLangs = Locale::get()->map('ID', 'Locale')->toArray();
        $activeLangs = array_map(
            fn($l) => Deepl::language_from_locale($l),
            $activeLangs,
        );

        return !in_array($this->TargetLang, $activeLangs);
    }

    public function getCMSFields()
    {
        $source = [];

        try {
            $styleRules = Deepl::list_style_rules();
            if ($styleRules) {
                foreach ($styleRules as $rule) {
                    if ($rule->language === $this->TargetLang) {
                        $source[$rule->styleId] = $rule->name;
                    }
                }
            }
        } catch (\Throwable $e) {
            // API unavailable
        }

        $fields = FieldList::create(
            ReadonlyField::create('TargetLang', 'Target Language'),
            DropdownField::create('StyleId', 'Style Rule', $source)
                ->setHasEmptyDefault(true)
                ->setEmptyString('(none)'),
        );

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    public static function by_target(string $targetLang): ?StyleRule
    {
        $rule = self::get()->filter('TargetLang', $targetLang);

        if ($rule->exists()) {
            return $rule->first();
        }

        return null;
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        if (static::class !== self::class) {
            return;
        }

        $locales = Locale::get();

        foreach ($locales as $locale) {
            $targetLang = Deepl::language_from_locale($locale->Locale);

            if (!self::by_target($targetLang)) {
                $rule = StyleRule::create();
                $rule->TargetLang = $targetLang;
                $rule->write();

                DB::alteration_message(
                    "StyleRule created ({$targetLang})",
                    'created',
                );
            }
        }
    }
}
