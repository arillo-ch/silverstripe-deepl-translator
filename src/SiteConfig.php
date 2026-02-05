<?php
namespace Arillo\Deepl;

use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Extension;

class SiteConfig extends Extension
{
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab('Root.Deepl', [
            new DeeplUsageField('DeeplUsage'),
            new GlossaryEditor('GlossaryEditor'),
        ]);
        return $fields;
    }
}
