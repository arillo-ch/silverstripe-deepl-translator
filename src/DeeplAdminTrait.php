<?php
namespace Arillo\Deepl;

use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\Security\Permission;
use TractorCow\Fluent\State\FluentState;

trait DeeplAdminTrait
{
    protected function updateFluentDeeplActions(
        FieldList $actions,
        $record,
        bool $forceMenu = false
    ) {
        if (
            !$record->hasExtension(DataObjectWiseTranslationExtension::class) ||
            !FluentState::singleton()->getLocale() ||
            !Permission::check(Deepl::USE_DEEPL)
        ) {
            return;
        }

        $rootMenu = $actions->fieldByName('FluentMenu');
        if (!$rootMenu && $forceMenu) {
            $rootMenu = TabSet::create('FluentMenu')
                ->setTemplate('FluentAdminTabSet')

                ->addExtraClass(
                    'ss-ui-action-tabset action-menus fluent-actions-menu noborder'
                );
            $moreOptions = Tab::create(
                'FluentMenuOptions',
                _t(
                    'TractorCow\Fluent\Extension\Traits\FluentAdminTrait.Localisation',
                    'Localisation'
                )
            );
            $moreOptions->addExtraClass('popover-actions-simulate');
            $rootMenu->push($moreOptions);

            $actions->insertBefore('RightGroup', $rootMenu);
        }

        if (
            $rootMenu &&
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
