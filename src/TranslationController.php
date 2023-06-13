<?php
namespace Arillo\Deepl;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Permission;

class TranslationController extends Controller
{
    private static $allowed_actions = ['translate', 'usage'];

    public function index(HTTPRequest $request)
    {
        return $this->httpError(404);
    }

    public function translate(HTTPRequest $request)
    {
        if (!Permission::check(Deepl::USE_DEEPL)) {
            return $this->respondUnauthorized();
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
            return $this->resondErrorMessage($th);
        }
    }

    public function usage(HTTPRequest $request)
    {
        if (!Permission::check(Deepl::USE_DEEPL)) {
            return $this->respondUnauthorized();
        }

        try {
            return $this->response
                ->addHeader('Content-Type', 'application/json')
                ->setBody(json_encode(Deepl::usage()));
        } catch (\Throwable $th) {
            return $this->resondErrorMessage($th);
        }
    }

    public function respondUnauthorized()
    {
        return $this->response
            ->setStatusCode(401)
            ->addHeader('X-Status', rawurlencode('Unautherized'))
            ->setBody('Unautherized');
    }

    protected function resondErrorMessage($err)
    {
        $message = '';
        switch (true) {
            case is_string($err):
                $message = $err;
                break;
            case class_implements($err, \Throwable::class):
                $message = $err->getMessage();

                break;
        }
        return $this->response
            ->setStatusCode(400)
            ->addHeader('X-Status', rawurlencode($message))
            ->setBody($message);
    }
}
