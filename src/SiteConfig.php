<?php
namespace Arillo\Deepl;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

class SiteConfig extends DataExtension
{
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Deepl', new DeeplUsageField('DeeplUsage'));
        return $fields;
    }
}
