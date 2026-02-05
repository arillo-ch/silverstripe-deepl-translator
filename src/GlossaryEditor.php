<?php
namespace Arillo\Deepl;
use SilverStripe\Model\ArrayData;
use SilverStripe\View\Requirements;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\Forms\LiteralField;

class GlossaryEditor extends LiteralField
{
    public function __construct($name)
    {
        Requirements::javascript(
            // 'arillo//silverstripe-deepl-translator: client/dist/glossaryEditor.js'
            '/_resources/vendor/arillo/silverstripe-deepl-translator/client/dist/glossaryEditor.js'
        );

        parent::__construct(
            $name,
            (new ArrayData([
                'GlossaryConfig' => json_encode(self::glossary_config()),
            ]))->renderWith('Arillo\Deepl\Includes\Glossary')
        );
    }

    public static function glossary_config()
    {
        return [
            'apiBase' => ApiController::singleton()->Link(),
            'locales' => array_map(
                function ($l) {
                    return [
                        'ID' => $l->ID,
                        'Locale' => $l->Locale,
                        'Lang' => Deepl::language_from_locale($l->Locale),
                        'IsGlobalDefault' => $l->IsGlobalDefault,
                    ];
                },
                Locale::get()
                    ->sort('IsGlobalDefault DESC')
                    ->toArray()
            ),
            'isDirtMessage' => _t(
                __CLASS__ . '.IsDirtMessage',
                'Es gibt ungespeicherte Änderungen am Glossar. Seite dennoch verlassen?'
            ),
        ];
    }
}
