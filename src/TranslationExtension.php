<?php
namespace Arillo\Deepl;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataExtension;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\Security\Permission;
use SilverStripe\ORM\FieldType\DBField;
use TractorCow\Fluent\State\FluentState;

class TranslationExtension extends DataExtension
{
    const FLUENT_LOCALISED_FIELD_CSS_CLASS = 'fluent__localised-field';

    const TRANSLATABLE_FIELD_SCHEMA_DATA_TYPES = [
        FormField::SCHEMA_DATA_TYPE_STRING,
        FormField::SCHEMA_DATA_TYPE_TEXT,
        FormField::SCHEMA_DATA_TYPE_HTML,
    ];

    private static $targetLocale;

    public function updateCMSFields(FieldList $fields): void
    {
        if (!Permission::check(TranslationController::USE_DEEPL)) {
            return;
        }
        if (null !== Deepl::get_apikey()) {
            $targetLanguage = Deepl::language_from_locale(
                $this->currentLocale()
            );

            $localized = new ArrayList();
            $record = $this->owner;
            Locale::get()->each(function ($locale) use ($record, $localized) {
                FluentState::singleton()->withState(function () use (
                    $record,
                    $localized,
                    $locale
                ) {
                    FluentState::singleton()->setLocale($locale->Locale);
                    $localized->push(
                        new ArrayData([
                            'Locale' => $locale,
                            'Record' => DataObject::get(
                                $record->ClassName
                            )->byID($record->ID),
                        ])
                    );
                });
            });

            foreach ($fields->dataFields() as $field) {
                if ($this->isFieldAutotranslatable($field)) {
                    $currentValues = new ArrayList();

                    $localized->each(function ($r) use (
                        $currentValues,
                        $field,
                        $targetLanguage
                    ) {
                        $language = Deepl::language_from_locale(
                            $r->Locale->Locale
                        );

                        $currentValues->push(
                            new ArrayData([
                                'Language' => $language,
                                'Locale' => $r->Locale,
                                'IsCurrent' => $language == $targetLanguage,
                                'Value' => $this->forAttr(
                                    $r->Record->{$field->getName()}
                                ),
                            ])
                        );
                    });

                    $field->setTitle(
                        DBField::create_field(
                            'HTMLFragment',
                            (new ArrayData([
                                'TargetLanguage' => $targetLanguage,
                                'CurrentValue' =>
                                    $this->forAttr(
                                        $this->owner->{$field->getName()}
                                    ) ?? '',
                                'CurrentValues' => $currentValues,
                                'FieldTitle' => $field->Title(),
                            ]))->renderWith(get_class($this))
                        )
                    );
                }
            }
        }
    }

    private function forAttr($value)
    {
        return str_replace(["\r\n", "\r", "\n"], '', $value);
    }

    private function isFieldAutotranslatable(FormField $field): bool
    {
        return $field->hasClass(self::FLUENT_LOCALISED_FIELD_CSS_CLASS) &&
            in_array(
                $field->getSchemaDataType(),
                self::TRANSLATABLE_FIELD_SCHEMA_DATA_TYPES
            ) &&
            false === $field->isReadOnly() &&
            'URLSegment' !== $field->getName();
    }

    private function currentLocale(): string
    {
        if (null === self::$targetLocale) {
            self::$targetLocale = Locale::singleton()->getCurrentLocale()->Locale;
        }

        return self::$targetLocale;
    }
}
