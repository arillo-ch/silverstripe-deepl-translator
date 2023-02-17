<?php
namespace Arillo\Deepl;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataExtension;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Permission;
use SilverStripe\View\ArrayData;
use TractorCow\Fluent\State\FluentState;

class TranslationExtension extends DataExtension
{
    const FLUENT_LOCALISED_FIELD_CSS_CLASS = 'fluent__localised-field';

    const TRANSLATABLE_FIELD_SCHEMA_DATA_TYPES = [
        FormField::SCHEMA_DATA_TYPE_STRING,
        FormField::SCHEMA_DATA_TYPE_TEXT,
        FormField::SCHEMA_DATA_TYPE_HTML,
    ];

    private static $sourceLocale;
    private static $targetLocale;

    public function updateCMSFields(FieldList $fields): void
    {
        if (!Permission::check(TranslationController::USE_DEEPL)) {
            return;
        }
        if (null !== Deepl::get_apikey()) {
            $localisedDataObject = $this->localisedDataObject(
                $this->owner,
                $this->sourceLocale()
            );

            $sourceLanguage = Deepl::language_from_locale(
                $this->sourceLocale()
            );
            $targetLanguage = Deepl::language_from_locale(
                $this->currentLocale()
            );
            foreach ($fields->dataFields() as $field) {
                if (
                    $this->isFieldAutotranslatable($field) &&
                    $sourceLanguage != $targetLanguage
                ) {
                    $field->setTitle(
                        DBField::create_field(
                            'HTMLFragment',
                            (new ArrayData([
                                'SourceLanguage' => $sourceLanguage,
                                'TargetLanguage' => $targetLanguage,
                                'CurrentValue' => $this->htmlEncode(
                                    $this->owner->{$field->getName()}
                                ),
                                'SourceValue' =>
                                    null === $localisedDataObject
                                        ? ''
                                        : $this->htmlEncode(
                                            $localisedDataObject->{$field->getName()}
                                        ),
                                'FieldTitle' => $field->Title(),
                                'HasLocalizedObject' => !!$localisedDataObject,
                            ]))->renderWith(get_class($this))
                        )
                    );
                }
            }
        }
    }

    private function localisedDataObject(
        DataObject $dataObject,
        string $locale
    ): ?DataObject {
        if ($this->sourceLocale() === $this->currentLocale()) {
            return null;
        }

        $originalLocale = $this->currentLocale();
        FluentState::singleton()->setLocale($locale);

        $localisedDataObject = DataObject::get($dataObject->ClassName)->byID(
            $dataObject->ID
        );
        FluentState::singleton()->setLocale($originalLocale);

        return $localisedDataObject;
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

    private function htmlEncode(?string $string): string
    {
        if (null === $string) {
            return '';
        }

        return $string;
        // return htmlspecialchars($string);
    }

    private function sourceLocale(): string
    {
        if (null === self::$sourceLocale) {
            self::$sourceLocale = Locale::singleton()
                ->getChain()
                ->first()->Locale;
        }

        return self::$sourceLocale;
    }

    private function currentLocale(): string
    {
        if (null === self::$targetLocale) {
            self::$targetLocale = Locale::singleton()->getCurrentLocale()->Locale;
        }

        return self::$targetLocale;
    }
}
