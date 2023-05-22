<?php
namespace Arillo\Deepl;

use SilverStripe\Forms\LiteralField;

class DeeplUsageField extends LiteralField
{
    public function __construct($name)
    {
        parent::__construct($name, '');
        $this->setTitle(_t(__CLASS__ . '.Title', 'Deepl usage'))->addExtraClass(
            'deepl-usage-field'
        );
    }

    /**
     * @param array $properties
     *
     * @return string
     */
    public function FieldHolder($properties = [])
    {
        $context = $this;

        $this->extend('onBeforeRenderHolder', $context, $properties);

        if (count($properties ?? [])) {
            $context = $this->customise($properties);
        }

        return $context->renderWith($this->getFieldHolderTemplates());
    }
}
