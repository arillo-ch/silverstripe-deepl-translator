<?php
namespace Arillo\Deepl;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

class TranslationController extends Controller implements PermissionProvider
{
    const USE_DEEPL = 'USE_DEEPL';

    public function providePermissions()
    {
        return [
            self::USE_DEEPL => _t(
                'Arillo\Deepl.USE_DEEPL',
                'Can user use deepl in CMS'
            ),
        ];
    }

    public function index(HTTPRequest $request)
    {
        if (!Permission::check(self::USE_DEEPL)) {
            return $this->response
                ->setStatusCode(401)
                ->addHeader('X-Status', rawurlencode('Unautherized'))
                ->setBody('Unautherized');
        }

        $toLanguage = Deepl::language_from_locale(
            $request->postVar('toLocale')
        );
        $fromLanguage = Deepl::language_from_locale(
            $request->postVar('fromLocale')
        );
        $text = $request->postVar('text');

        if (!$toLanguage || !$text) {
            return $this->response
                ->setStatusCode(400)
                ->addHeader('X-Status', rawurlencode('Bad request'))
                ->setBody('Bad request');
        }

        try {
            return $this->response
                ->addHeader('Content-Type', 'application/json')
                ->setBody(
                    json_encode(
                        Deepl::translate($text, $toLanguage, $fromLanguage)
                    )
                );
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode(400)
                ->addHeader('X-Status', rawurlencode($th->getMessage()))
                ->setBody($th->getMessage());
        }
    }
}
