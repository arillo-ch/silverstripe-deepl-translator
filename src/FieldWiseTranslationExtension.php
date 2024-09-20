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

class FieldWiseTranslationExtension extends DataExtension
{
    const FLUENT_LOCALISED_FIELD_CSS_CLASS = 'fluent__localised-field';

    const TRANSLATABLE_FIELD_SCHEMA_DATA_TYPES = [
        FormField::SCHEMA_DATA_TYPE_STRING,
        FormField::SCHEMA_DATA_TYPE_TEXT,
        FormField::SCHEMA_DATA_TYPE_HTML,
    ];

    private static $targetLocale;
    private static $deepl_fieldwise_included_fields = [];
    private static $deepl_fieldwise_translate_urlsegment_field = false;

    public function updateCMSFields(FieldList $fields): void
    {
        if (!Permission::check(Deepl::USE_DEEPL)) {
            return;
        }
        if (null !== Deepl::get_apikey()) {
            $targetLanguage = Deepl::language_from_locale(
                $this->currentLocale()
            );

            $localized = new ArrayList();
            $record = $this->owner;

            if (!$record->exists()) {
                return;
            }

            Locale::get()->each(function ($locale) use ($record, $localized) {
                FluentState::singleton()->withState(function ($state) use (
                    $record,
                    $localized,
                    $locale
                ) {
                    $state->setLocale($locale->Locale);
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
                if (
                    $this->isFieldAutotranslatable($field) &&
                    $this->isFieldConfiguratedAsAutotranslatable($field)
                ) {
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
                                    $this->deeplFieldValueFromRecord(
                                        $r->Record,
                                        $field->getName()
                                    )
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
                                        $this->deeplFieldValueFromRecord(
                                            $this->owner,
                                            $field->getName()
                                        )
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

    /**
     * Gets field value for a given DataObject.
     * Uses alternate getter method deeplFieldValueFromRecord($fieldName) if is implemented in that class.
     *
     * @param DataObject|null $record
     * @param string $fieldName
     * @return mixed
     */
    private function deeplFieldValueFromRecord(
        ?DataObject $record,
        string $fieldName
    ) {
        if (!$record) {
            return null;
        }
        if ($record->hasMethod('deeplFieldValueFromRecord')) {
            return $record->deeplFieldValueFromRecord($fieldName);
        }
        return $record->{$fieldName};
    }

    private function forAttr($value)
    {
        if (!$value) {
            return $value;
        }
        return str_replace(["\r\n", "\r", "\n"], '', $value);
    }

    private function isFieldConfiguratedAsAutotranslatable(
        FormField $field
    ): bool {
        $include = $this->owner->config()->deepl_fieldwise_included_fields;
        if (!$inckude || !count($include)) {
            return true;
        }
        return in_array($field->getName(), $include);
    }

    private function isFieldAutotranslatable(FormField $field): bool
    {
        return $field->hasClass(self::FLUENT_LOCALISED_FIELD_CSS_CLASS) &&
            in_array(
                $field->getSchemaDataType(),
                self::TRANSLATABLE_FIELD_SCHEMA_DATA_TYPES
            ) &&
            false === $field->isReadOnly() &&
            ('URLSegment' !== $field->getName() ||
                $this->owner->config()
                    ->deepl_fieldwise_translate_urlsegment_field);
    }

    private function currentLocale(): string
    {
        if (null === self::$targetLocale) {
            self::$targetLocale = Locale::singleton()->getCurrentLocale()->Locale;
        }

        return self::$targetLocale;
    }
}
