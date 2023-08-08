<?php
namespace Arillo\Deepl;

use PhpParser\Node\Expr\Cast\Array_;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Permission;
use SilverStripe\View\ArrayData;

class ApiController extends Controller
{
    private static $allowed_actions = [
        'translate',
        'usage',
        'glossaryEntries',
        'saveGlossaries',
    ];

    private static $url_segment = 'api/deepl';

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

    public function glossaryEntries(HTTPRequest $request)
    {
        if (!Permission::check(Deepl::USE_DEEPL)) {
            return $this->respondUnauthorized();
        }

        return $this->response
            ->addHeader('Content-Type', 'application/json')
            ->setBody(
                json_encode($this->accumulateGlossaries()->toNestedArray())
            );
    }

    public function accumulateGlossaries(): ArrayList
    {
        $acc = new ArrayList();

        $defaultLocale = Locale::getDefault();
        $sourceLang = Deepl::language_from_locale($defaultLocale->Locale);

        Glossary::get()
            ->filter([
                'GlossaryId:not' => [null, ''],
                'SourceLang' => $sourceLang,
            ])
            ->each(function (Glossary $glossary) use ($acc) {
                try {
                    $entries = Deepl::get_glossary_entries(
                        $glossary->GlossaryId
                    );

                    foreach ($entries->getEntries() as $source => $target) {
                        if ($e = $acc->find($glossary->SourceLang, $source)) {
                            $e->setField($glossary->TargetLang, $target);
                        } else {
                            $acc->push(
                                new ArrayData([
                                    $glossary->SourceLang => $source,
                                    $glossary->TargetLang => $target,
                                ])
                            );
                        }
                    }
                } catch (\Throwable $th) {
                    //throw $th;
                }
            });

        return $acc;
    }

    public function saveGlossaries(HTTPRequest $request)
    {
        if (!Permission::check(Deepl::USE_DEEPL)) {
            return $this->respondUnauthorized();
        }
        $namePrefix = Deepl::get_glossary_name_prefix();
        if (null == $namePrefix) {
            return $this->respondWithJson([
                'message' => 'env DEEPL_GLOSSARY_NAME_PREFIX not set',
            ])->setStatusCode(400);
        }

        if (
            null != $request->postVar('glossaryEntries') &&
            ($glossaryEntries = json_decode(
                $request->postVar('glossaryEntries'),
                true
            )) &&
            $glossaryEntries
        ) {
            $locales = Locale::get()->sort('IsGlobalDefault DESC');
            $defaultLocale = $locales->find('IsGlobalDefault', true);
            $sourceLang = Deepl::language_from_locale($defaultLocale->Locale);
            $now = DBDatetime::now()->format(DBDatetime::ISO_DATETIME);

            foreach ($locales as $sourceLocale) {
                foreach ($locales as $targetLocale) {
                    if ($sourceLocale->ID != $targetLocale->ID) {
                        $sourceLang = Deepl::language_from_locale(
                            $sourceLocale->Locale
                        );
                        $targetLang = Deepl::language_from_locale(
                            $targetLocale->Locale
                        );
                        $entries = [];
                        foreach ($glossaryEntries as $glossaryEntry) {
                            if (
                                isset($glossaryEntry[$sourceLang]) &&
                                isset($glossaryEntry[$targetLang])
                            ) {
                                $entries[
                                    trim($glossaryEntry[$sourceLang])
                                ] = trim($glossaryEntry[$targetLang]);
                            }
                        }

                        $deeplGlossary = Deepl::create_glossary(
                            "{$namePrefix}: {$sourceLang} - {$targetLang} ({$now})",
                            $sourceLang,
                            $targetLang,
                            $entries
                        );

                        $glossary = Glossary::find_or_create(
                            $sourceLang,
                            $targetLang
                        );

                        $glossary
                            ->update([
                                'GlossaryId' => $deeplGlossary->glossaryId,
                            ])
                            ->write();
                    }
                }
            }

            Deepl::delete_unused_glossaries($namePrefix);

            return $this->respondWithJson([
                'glossaryEntries' => $this->accumulateGlossaries()->toNestedArray(),
            ]);
        }

        return $this->resondErrorMessage('insufficiant arguments');
    }

    protected function respondWithJson($data)
    {
        return $this->response
            ->addHeader('Content-Type', 'application/json')
            ->setBody(json_encode($data));
    }

    protected function respondUnauthorized()
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
