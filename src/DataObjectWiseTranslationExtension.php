<?php
namespace Arillo\Deepl;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Versioned\Versioned;
use TractorCow\Fluent\State\FluentState;

class DataObjectWiseTranslationExtension extends DataExtension
{
    use DeeplAdminTrait;

    const TRANSLATABLE_DB_FIELDS = ['Varchar', 'Text', 'HTMLText'];

    private static $deepl_dataobject_included_relations = [];

    public function updateCMSActions(FieldList $actions)
    {
        $this->updateFluentDeeplActions($actions, $this->owner);
    }

    public function deeplTranslateWithRelations($toLocale, $fromLocale)
    {
        if ($this->owner->hasMethod('beforeDeeplTranslateWithRelations')) {
            $this->owner->beforeDeeplTranslateWithRelations();
        }
        $path = Deepl::module_path();
        $basePath = BASE_PATH;
        $class = get_class($this->owner);
        $id = $this->owner->ID;

        exec(
            "php -f {$path}/scripts/parallel_translate_data_object.php basepath='{$basePath}' classname='{$class}' id={$id} from='{$fromLocale}' to='{$toLocale}'",
            $result
        );

        if ($this->owner->hasMethod('afterDeeplTranslateWithRelations')) {
            $this->owner->afterDeeplTranslateWithRelations();
        }

        return $result;
    }

    /**
     * Executes translation requests in parallel.
     * CAUTION: This function can not be called in a php process which handles a web request, because is it using Amp parallelMap.
     * Nevertheless, you can run exec in a php file, please take a look at `scripts/parallel_translate_data_object.php`
     *
     *
     * @param string $classname
     * @param int $id
     * @param string $toLocale
     * @param string $fromLocale
     * @return void
     */
    public static function parallel_translate_data_object(
        string $classname,
        int $id,
        string $toLocale,
        string $fromLocale
    ) {
        $translations = FluentState::singleton()->withState(function (
            $state
        ) use ($classname, $id, $toLocale, $fromLocale) {
            $state->setLocale($fromLocale);
            $recordsCollection = new ArrayList();
            $sourceRecord = $classname::get()->byId($id);

            $recordsCollection = self::add_to_records_collection(
                $recordsCollection,
                $sourceRecord
            );

            $recordsCollection = self::add_relations_to_records_collection(
                $recordsCollection,
                $sourceRecord
            );

            $result = ParallelTranslator::run(
                $recordsCollection,
                Deepl::language_from_locale($toLocale),
                Deepl::language_from_locale($fromLocale)
            );

            return $result;
        });

        FluentState::singleton()->withState(function ($state) use (
            $translations,
            $toLocale
        ) {
            $state->setLocale($toLocale);
            foreach ($translations as $translation) {
                $class = $translation->ClassName;
                if ($record = $class::get()->byId($translation->ID)) {
                    $update = [];
                    for ($i = 0; $i < count($translation->Fields); $i++) {
                        $update[$translation->Fields[$i]] =
                            $translation->Results[$i]->text;
                    }

                    $record->update($update)->write();
                    if (
                        $record->hasExtension(Versioned::class) &&
                        $record->isPublished()
                    ) {
                        $record->doPublish();
                    }
                }
            }
        });
    }

    /**
     * Add related objects to the translation collection.
     *
     * @param ArrayList $recordsCollection
     * @param DataObject $rootRecord
     * @return ArrayList
     */
    public static function add_relations_to_records_collection(
        ArrayList $recordsCollection,
        DataObject $rootRecord
    ): ArrayList {
        if (
            ($relations = $rootRecord->config()
                ->deepl_dataobject_included_relations) &&
            is_array($relations)
        ) {
            foreach ($relations as $relation) {
                $many = $rootRecord->hasMany();
                $many = array_merge($many, $rootRecord->manyMany());

                if (
                    isset($many[$relation]) &&
                    ($records = $rootRecord->{$relation}()) &&
                    $records->exists()
                ) {
                    foreach ($records as $record) {
                        $recordsCollection = self::add_to_records_collection(
                            $recordsCollection,
                            $record
                        );

                        self::add_relations_to_records_collection(
                            $recordsCollection,
                            $record
                        );
                    }
                }

                if (
                    ($hasOne = $rootRecord->hasOne()) &&
                    isset($hasOne[$relation]) &&
                    ($record = $rootRecord->{$relation}()) &&
                    $record->exists()
                ) {
                    $recordsCollection = self::add_to_records_collection(
                        $recordsCollection,
                        $record
                    );
                    self::add_relations_to_records_collection(
                        $recordsCollection,
                        $record
                    );
                }
            }
        }
        return $recordsCollection;
    }

    /**
     * This function assumes that the passed record was already loaded in the right locale.
     *
     * @param ArrayList $recordsCollection
     * @param DataObject $record
     * @return ArrayList
     */
    public static function add_to_records_collection(
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
                    ])
                );
            }
        }

        if ($fields->exists()) {
            $recordsCollection->push(
                new ArrayData([
                    'ClassName' => $classname,
                    'ID' => $sourceRecord->ID,
                    'Fields' => $fields->column('Field'),
                    'Texts' => $fields->column('Source'),
                    'Results' => [],
                ])
            );
        }

        return $recordsCollection;
    }
}
