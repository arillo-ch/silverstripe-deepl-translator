<?php
namespace Arillo\Deepl;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\Security\Permission;
use TractorCow\Fluent\State\FluentState;

trait DeeplAdminTrait
{
    protected function updateFluentActions(FieldList $actions, $record)
    {
        if (
            !FluentState::singleton()->getLocale() ||
            !Permission::check(Deepl::USE_DEEPL)
        ) {
            return;
        }

        if (
            ($rootMenu = $actions->fieldByName('FluentMenu')) &&
            ($moreOptions = $rootMenu->fieldByName('FluentMenuOptions'))
        ) {
            $currentLocale = Locale::getCurrentLocale();
            Locale::get()->each(function ($locale) use (
                $moreOptions,
                $currentLocale
            ) {
                if ($currentLocale && $locale->ID == $currentLocale->ID) {
                    return;
                }
                $moreOptions->push(
                    new FormAction(
                        "deepl_translate_from[{$locale->Locale}]",
                        _t(
                            __TRAIT__ . '.deepl_translate_from',
                            'Mit Deepl aus {locale} übersetzen',
                            ['locale' => $locale->Title]
                        )
                    )
                );
            });
        }
    }
}
