<?php
namespace Arillo\Deepl;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\ORM\DataExtension;

class SiteConfig extends DataExtension
{
    private static string $glossary_mode = 'editor';

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Deepl', new DeeplUsageField('DeeplUsage'));

        if ($this->owner->config()->get('glossary_mode') === 'external') {
            $fields->addFieldToTab(
                'Root.Deepl',
                GridField::create(
                    'Glossaries',
                    'Glossaries',
                    Glossary::get(),
                    GridFieldConfig_RecordEditor::create(),
                ),
            );
        } else {
            $fields->addFieldToTab(
                'Root.Deepl',
                new GlossaryEditor('GlossaryEditor'),
            );
        }

        $fields->addFieldToTab(
            'Root.Deepl',
            GridField::create(
                'StyleRules',
                'Style Rules',
                StyleRule::get(),
                GridFieldConfig_RecordEditor::create(),
            ),
        );

        return $fields;
    }
}
