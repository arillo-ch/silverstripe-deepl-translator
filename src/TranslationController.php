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
        // if (!Permission::check(self::USE_DEEPL)) {
        //     return $this->response
        //         ->addHeader('Content-Type', 'application/json')
        //         ->setBody(json_encode(['message' => 'Unautherized']))
        //         ->setStatusCode(401);
        // }

        $toLanguage = Deepl::language_from_locale(
            $request->postVar('toLocale')
        );
        $fromLanguage = Deepl::language_from_locale(
            $request->postVar('fromLocale')
        );
        $text = $request->postVar('text');

        if (!$toLanguage || !$text) {
            return $this->response
                ->addHeader('Content-Type', 'application/json')
                ->setBody(json_encode(['message' => 'Bad request']))
                ->setStatusCode(400);
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
                ->addHeader('Content-Type', 'application/json')
                ->setBody(json_encode(['message' => 'Bad request']))
                ->setStatusCode(400);
        }
    }
}
