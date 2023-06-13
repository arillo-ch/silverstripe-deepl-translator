<?php

namespace Arillo\Deepl;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;

/**
 * @property GridFieldDetailForm_ItemRequest $owner
 */
class DeeplGridFieldExtension extends Extension
{
    use DeeplAdminTrait;

    private static $allowed_actions = ['deepl_translate_from'];

    public function updateFormActions(FieldList $actions)
    {
        $this->updateFluentDeeplActions($actions, $this->owner->getRecord());
    }

    public function deepl_translate_from($data, $form)
    {
        $controller = Controller::curr();
        if (
            isset($data['action_deepl_translate_from']) &&
            is_array($data['action_deepl_translate_from']) &&
            count($data['action_deepl_translate_from']) &&
            ($toLocale = Locale::getByLocale(
                array_keys($data['action_deepl_translate_from'])[0]
            )) &&
            ($currentLocale = Locale::getCurrentLocale()) &&
            ($record = $this->owner->getRecord())
        ) {
            $record->deeplTranslateWithRelations(
                $currentLocale->Locale,
                $toLocale->Locale
            );

            $controller->getResponse()->addHeader(
                'X-Status',
                rawurlencode(
                    _t(
                        __CLASS__ . '.TranslationSuccess',
                        'Die Seite wurde aus {locale} übersetzt',
                        [
                            'locale' => $toLocale->Title,
                        ]
                    )
                )
            );

            $controller->getRequest()->addHeader('X-Pjax', 'Content');

            // append some random url param to force the reload.
            return $controller->redirect(
                $this->owner->Link() . '?translated=' . time(),
                302
            );
        }

        return $controller
            ->getResponse()
            ->addHeader(
                'X-Status',
                rawurlencode(
                    _t(
                        __CLASS__ . '.TranslationError',
                        'Die Seite konnte nicht übersetzt werden'
                    )
                )
            );
    }
}
