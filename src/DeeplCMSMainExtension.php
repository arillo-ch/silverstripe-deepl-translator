<?php
namespace Arillo\Deepl;

use SilverStripe\Core\Extension;
use TractorCow\Fluent\Model\Locale;

class DeeplCMSMainExtension extends Extension
{
    private static $allowed_actions = ['deepl_translate_from'];

    public function deepl_translate_from($data, $form)
    {
        if (
            isset($data['action_deepl_translate_from']) &&
            is_array($data['action_deepl_translate_from']) &&
            count($data['action_deepl_translate_from']) &&
            ($toLocale = Locale::getByLocale(
                array_keys($data['action_deepl_translate_from'])[0]
            )) &&
            ($currentLocale = Locale::getCurrentLocale()) &&
            ($record = $this->owner->currentPage())
        ) {
            $record->deeplTranslateWithRelations(
                $currentLocale->Locale,
                $toLocale->Locale
            );

            $this->owner->getResponse()->addHeader(
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

            return $this->owner
                ->getResponseNegotiator()
                ->respond($this->owner->request);
        }

        return $this->owner
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
