<?php
namespace Arillo\Deepl;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Core\Config\Config;
use TractorCow\Fluent\State\FluentState;

class DataObjectWiseTranslationExtension extends DataExtension
{
    const TRANSLATABLE_DB_FIELDS = ['Varchar', 'Text', 'HTMLText'];

    private static $deepl_do_relations_include = ['Elements'];

    public static function bulk_translate(
        array $data,
        string $toLang,
        string $fromLang
    ) {
        $path = Deepl::module_path();
        $payload = escapeshellarg(json_encode($data));
        $basePath = BASE_PATH;

        exec(
            "php -f {$path}/scripts/bulk_translate.php payload={$payload} from={$fromLang} to={$toLang} base_path={$basePath}",
            $result
        );

        \SilverStripe\Dev\Debug::dump($result);

        return json_decode($result[count($result) - 1], true);
    }

    public static function parallel_translate_data_object(
        $classname,
        $id,
        $toLocale,
        $fromLocale
    ) {
        FluentState::singleton()->withState(function ($state) use (
            $classname,
            $id,
            $toLocale,
            $fromLocale
        ) {
            $state->setLocale($fromLocale);
            $recordsCollection = new ArrayList();
            $sourceRecord = $classname::get()->byId($id);

            $recordsCollection = self::add_fields_from_dataobject(
                $recordsCollection,
                $sourceRecord
            );

            // if (
            //     ($elements = $sourceRecord->Elements()) &&
            //     $elements->exists()
            // ) {
            //     foreach ($elements as $element) {
            //         $recordsCollection = self::add_fields_from_dataobject(
            //             $recordsCollection,
            //             $element
            //         );
            //     }
            // }

            \SilverStripe\Dev\Debug::dump($recordsCollection);

            // $groupedByRecord = new ArrayList();

            // foreach ($recordsCollection as $field) {
            //     if (
            //         ($existing = $groupedByRecord->filter([
            //             'ClassName' => $field->ClassName,
            //             'ID' => $field->ID,
            //         ])) &&
            //         $existing->exists()
            //     ) {
            //         $existing = $existing->first();
            //         // $fields =
            //         // $existing->getField('Fields')->push($field);
            //         $existing->Fields->push($field);
            //         // \SilverStripe\Dev\Debug::dump($fields);
            //         // $fields[] = $field;
            //         // $existing->setField('Fields', $field);
            //         // \SilverStripe\Dev\Debug::dump($existing);
            //         // \SilverStripe\Dev\Debug::dump($existing->first());
            //         // die();
            //     } else {
            //         $groupedByRecord->push(
            //             new ArrayData([
            //                 'ClassName' => $field->ClassName,
            //                 'ID' => $field->ID,
            //                 'Fields' => new ArrayList([$field]),
            //             ])
            //         );
            //     }
            // }

            // foreach ($groupedByRecord as $group) {
            //     \SilverStripe\Dev\Debug::dump($group);
            // }

            // $result = ParallelTranslator::run(
            //     $recordsCollection,
            //     Deepl::language_from_locale($toLocale),
            //     Deepl::language_from_locale($fromLocale)
            // );

            // \SilverStripe\Dev\Debug::dump($result);
        });
    }

    /**
     * This function assumes that the passed record was already loaded in the right locale.
     *
     * @param ArrayList $recordsCollection
     * @param DataObject $record
     * @return ArrayList
     */
    public static function add_fields_from_dataobject(
        ArrayList $recordsCollection,
        DataObject $record
    ): ArrayList {
        $classname = get_class($record);
        $dbFields = DataObject::getSchema()->databaseFields($classname, true);
        $translateFields = Config::inst()->get($classname, 'translate');
        $translateFields = array_filter($translateFields, function ($f) use (
            $dbFields
        ) {
            if (empty($dbFields[$f])) {
                return false;
            }

            return in_array(
                trim(preg_replace('/\([^)]*\)/', '', $dbFields[$f])),
                self::TRANSLATABLE_DB_FIELDS
            );
        });

        if (!count($translateFields)) {
            return $recordsCollection;
        }

        $sourceRecord = $classname::get()->byId($record->ID);

        $fields = new ArrayList();

        foreach ($translateFields as $field) {
            if ($sourceRecord->{$field}) {
                $fields->push(
                    new ArrayData([
                        'Field' => $field,
                        'Source' => $sourceRecord->{$field},
                        'Result' => null,
                        'Error' => null,
                    ])
                );
                // $recordsCollection->push(
                //     new ArrayData([
                //         'ClassName' => $classname,
                //         'ID' => $sourceRecord->ID,
                //         'Field' => $field,
                //         'Source' => $sourceRecord->{$field},
                //         'Result' => null,
                //         'Error' => null,
                //     ])
                // );
            }
        }

        if ($fields->exists()) {
            $recordsCollection->push(
                new ArrayData([
                    'ClassName' => $classname,
                    'ID' => $sourceRecord->ID,
                    'Fields' => $fields,
                ])
            );
        }

        // $recordsCollection->push(
        //     new ArrayData([
        //         'ClassName' => $classname,
        //         'ID' => $sourceRecord->ID,
        //         'Field' => $field,
        //         'Source' => $sourceRecord->{$field},
        //         'Result' => null,
        //         'Error' => null,
        //     ])
        // );

        return $recordsCollection;
    }

    public static function translate_data_object(
        DataObject $record,
        string $toLocale,
        ?string $fromLocale = null
    ) {
        die();
        $translated = FluentState::singleton()->withState(function (
            FluentState $state
        ) use ($record, $toLocale, $fromLocale) {
            $state->setLocale($fromLocale);
            $class = get_class($record);
            $dbFields = DataObject::getSchema()->databaseFields($class, true);
            $translateFields = Config::inst()->get($class, 'translate');
            $translateFields = array_filter($translateFields, function (
                $f
            ) use ($dbFields) {
                if (empty($dbFields[$f])) {
                    return false;
                }

                return in_array(
                    trim(preg_replace('/\([^)]*\)/', '', $dbFields[$f])),
                    self::TRANSLATABLE_DB_FIELDS
                );
            });

            // $dbFields =
            // \SilverStripe\Dev\Debug::dump($translateFields);
            $dataForTranslation = [];
            $sourceRecord = $class::get()->byId($record->ID);
            $fromLang = Deepl::language_from_locale($fromLocale);
            $toLang = Deepl::language_from_locale($toLocale);
            foreach ($translateFields as $field) {
                if ($sourceRecord->{$field}) {
                    $dataForTranslation[$field] = [
                        'source' => $sourceRecord->{$field},
                        'result' => null,
                    ];
                }
            }
            // foreach ($dataForTranslation as $field => $values) {
            //     try {
            //         $r = Deepl::translate(
            //             $values['source'],
            //             $toLang,
            //             $fromLang
            //         );

            //         if ($r->text) {
            //             $dataForTranslation[$field]['result'] = $r->text;
            //         }
            //     } catch (\Throwable $th) {
            //         \SilverStripe\Dev\Debug::dump($th->getMessage());
            //     }
            // }

            // return $dataForTranslation;

            return [
                'Title' => [
                    'source' => 'Startseite DE',
                    'result' => 'Page d\'accueil FR',
                ],
                'MetaTitle' => [
                    'source' =>
                        'myky - der intelligente Partner für Ihr Eigenheim',
                    'result' =>
                        'myky - le partenaire intelligent pour votre maison individuelle',
                ],
                'MenuTitle' => [
                    'source' => 'Startseite DE',
                    'result' => 'Page d\'accueil FR',
                ],
                'URLSegment' => [
                    'source' => 'home',
                    'result' => 'home',
                ],
                'MetaDescription' => [
                    'source' =>
                        'Benötigen Sie Unterstützung bei der Sanierung? Haben Sie Fragen rund um Ihr Eigenheim? myky, der digitale Partner für Ihr Eigenheim, hilft Ihnen weiter.',
                    'result' =>
                        'Vous avez besoin d\'aide pour votre rénovation ? Vous avez des questions sur votre logement ? myky, le partenaire numérique de votre logement, est là pour vous aider.',
                ],
            ];
            // \SilverStripe\Dev\Debug::dump($class::getSchema());
            // \SilverStripe\Dev\Debug::dump($record);
        });
        \SilverStripe\Dev\Debug::dump($translated);

        die();

        FluentState::singleton()->withState(function (FluentState $state) use (
            $record,
            $toLocale,
            $translated
        ) {
            $state->setLocale($toLocale);
            $class = get_class($record);
            $targetRecord = $class::get()->byId($record->ID);
            $update = [];
            foreach ($translated as $field => $values) {
                $update[$field] = $values['result'];
            }

            // \SilverStripe\Dev\Debug::dump($update);
            \SilverStripe\Dev\Debug::dump($targetRecord);
            $targetRecord->update($update)->write();
            if ($targetRecord->isPublished()) {
                $targetRecord->doPublish();
            }
        });
    }
}
