<?php

namespace Arillo\Deepl;

use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use TractorCow\Fluent\Model\Locale;

/**
 * @property string $GlossaryId
 * @property string $SourceLang
 * @property string $TargetLang
 */
class Glossary extends DataObject
{
    private static $table_name = 'Arillo_Deepl_Glossary';
    private static $singular_name = 'Glossary';
    private static $plural_name = 'Glossaries';
    private static $db = [
        'GlossaryId' => 'Varchar(100)',
        'SourceLang' => 'Varchar(10)',
        'TargetLang' => 'Varchar(10)',
    ];

    // private static $has_one = [
    //     'Locale' => Locale::class,
    // ];

    public static function find_or_create($source, $target): Glossary
    {
        if ($existing = self::by_source_and_target($source, $target)) {
            return $existing;
        }
        $g = new Glossary();
        $g->update([
            'SourceLang' => $source,
            'TargetLang' => $target,
        ]);

        $g->write();
        return $g;
    }

    public static function by_source_and_target($source, $target): ?Glossary
    {
        if (
            ($glossary = self::get()->filter([
                'SourceLang' => $source,
                'TargetLang' => $target,
            ])) &&
            $glossary->exists()
        ) {
            return $glossary->first();
        }

        return null;
    }

    public function forJson()
    {
        return [
            'ID' => $this->ID,
            'GlossaryId' => $this->GlossaryId,
            'SourceLang' => $this->SourceLang,
            'TargetLang' => $this->TargetLang,
        ];
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        if (static::class === self::class) {
            $locales = Locale::get()->sort('IsGlobalDefault DESC');
            $defaultLocale = $locales->find('IsGlobalDefault', true);

            $locales->each(function ($locale) use ($defaultLocale) {
                if ($locale->ID != $defaultLocale->ID) {
                    $source = Deepl::language_from_locale(
                        $defaultLocale->Locale, true
                    );
                    $target = Deepl::language_from_locale($locale->Locale);
                    if (
                        !self::by_source_and_target(
                            Deepl::language_from_locale($defaultLocale->Locale, true),
                            Deepl::language_from_locale($locale->Locale)
                        )
                    ) {
                        $glossary = (new Glossary())->update([
                            'SourceLang' => $source,
                            'TargetLang' => $target,
                        ]);
                        $glossary->write();

                        DB::alteration_message(
                            "Gloassary created ({$glossary->SourceLang}, {$glossary->TargetLang}) ",
                            'created'
                        );
                    }
                }
            });
        }
    }
}
