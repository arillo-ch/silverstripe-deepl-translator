<?php
namespace Arillo\Deepl;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\ORM\DataExtension;

class SiteConfig extends DataExtension
{
    private static string $glossary_mode = 'editor';

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Deepl', new DeeplUsageField('DeeplUsage'));

        if (static::config()->get('glossary_mode') === 'external') {
            $config = GridFieldConfig_RecordEditor::create();
            $config->removeComponentsByType(GridFieldAddNewButton::class);
            $config->removeComponentsByType(GridFieldDeleteAction::class);

            $fields->addFieldToTab(
                'Root.Deepl',
                GridField::create(
                    'Glossaries',
                    'Glossaries',
                    Glossary::get(),
                    $config,
                ),
            );
        } else {
            $fields->addFieldToTab(
                'Root.Deepl',
                new GlossaryEditor('GlossaryEditor'),
            );
        }

        return $fields;
    }
}
