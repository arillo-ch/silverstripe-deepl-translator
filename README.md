# arillo/silverstripe-deepl-translator

Adds Deepl translation API to a Fluent translated SilverStripe CMS.
For now it only adds UI-Elements to texty fields (TextField, TextareaField, HTMLEditorField) to translate their contents.

## Installation

```
composer require arillo/silverstripe-deepl-translator
```

## Configuration

Create a Deepl API key and add it to your `.env`:

```
DEEPL_APIKEY="<YOUR_DEEPL_APIKEY>"
```

If you are using the glossary feature, it is necessary, to set a unique name prefix in your `.env`.
It will prevent old glossaries from deletion if their names do not start with that name prefix.
This is handy if you are using the same API key in multiple instances (e.g. dev & prod).

```
DEEPL_GLOSSARY_NAME_PREFIX="myky-dev"
```

Configure your DataObjects to display the Deepl translation handles.

**NOTE:** It is important, that this configurations happens **AFTER** fluent was added to these DataObjects. Otherwise the UI will not appear.

Let's assume you have the following fluent config, named `myfluentcms`:

```
---
Name: myfluentcms
After: 
 - '#basei18n'
 - '#fluentcms'
 - '#fluentcms-pages'
---
SilverStripe\i18n\i18n:
  default_locale: de_CH

Arillo\Elements\ElementBase:
  extensions:
    - 'TractorCow\Fluent\Extension\FluentVersionedExtension'
```

## Field-wise translator

Apply the deepl config after `#myfluentcms`:

```
---
Name: mydeepl
After: 
 - '#myfluentcms'
 - '#silverstripe-deepl-translator'
---
SilverStripe\CMS\Model\SiteTree:
  extensions:
    - Arillo\Deepl\FieldWiseTranslationExtension

Arillo\Elements\ElementBase:
  extensions:
    - Arillo\Deepl\FieldWiseTranslationExtension
```

Make URLSegmentFields translatable (CAUTION: is expirimental; JS implementation WIP):

```
Arillo\Deepl\FieldWiseTranslationExtension:
  deepl_fieldwise_translate_urlsegment_field: true
```

## Alternate field value gathering for field-wise translator

To preload current values for field-wise translations this module uses the following method `$record->{$fieldName}`.
However, it is possible to specify an alternate data source by implementing a class method called `deeplFieldValueFromRecord` to overwrite the default behavior, e.g.:

```
    // in some DataObject class
    // ...

    public function deeplFieldValueFromRecord($fieldName)
    {
        switch ($fieldName) {
            case 'AOLink_Title':
                return $this->LinkObject()->Title;

            case 'AOLink_URL':
                return $this->LinkObject()->URL;

            default:
                return $this->{$fieldName};
        }
    }
```

## Add translation features to selected fields only:

Below you  see an example configuration that will only add field-wiese translation features to `Title` and `Description` for that `App\Model\MyDataObject`.

```
App\Model\MyDataObject:
  extensions:
    - Arillo\Deepl\FieldWiseTranslationExtension
  deepl_fieldwise_included_fields:
    - Title
    - Description
```

## DataObject-wise translator

Automatically translates all translatable fields of a DataObject.
Just add `Arillo\Deepl\DataObjectWiseTranslationExtension` to extensions of that DataObject class, e.g.:

```
---
Name: mydeepl
After: 
 - '#myfluentcms'
 - '#silverstripe-deepl-translator'
---
SilverStripe\CMS\Model\SiteTree:
  extensions:
    - Arillo\Deepl\DataObjectWiseTranslationExtension

Arillo\Elements\ElementBase:
  extensions:
    - Arillo\Deepl\DataObjectWiseTranslationExtension
```

Optionally you can define relations that also will be translated. Make sure that the according DataObject also use soem Fluent extensions.

To configure the wanted relations just use the `deepl_dataobject_included_relations` configuration, e.g.:

```
Arillo\Elements\ElementBase:
  extensions:
    - Arillo\Deepl\DataObjectWiseTranslationExtension
  deepl_dataobject_included_relations:
    - Elements
    - LinkObject
```

## Glossary

If you want to use the glossary features of Deepl, you can manage your glossaries in SiteConfig.

There are two glossary modes available: **editor** (default) and **external**.

### Editor mode (default)

In editor mode, glossary entries are managed through an inline editor directly in SiteConfig. This lets you create and edit glossary entries within the CMS.

This is the default mode and requires no additional configuration.

You need to set a glossary name prefix in your `.env`, like so:

```
DEEPL_GLOSSARY_NAME_PREFIX="mysite-prod"
```

These prefixes are useful if you run your app in different environments, e.g.: `dev` or `live`.

As of this writing, keep in mind that the deepl glossary API does not allow for updates of glossaries. So updates are deletes and creates. The purging mechanism takes the name prefixes into account. E.g.:
If your env is set to `mysite-prod` it will only delete glossaries where the name starts with that prefix. This should help to prevent unwanted glossary deletes.

### External mode

In external mode, glossaries are managed externally (e.g. via the Deepl web interface or API). SiteConfig displays a GridField where you can select existing Deepl glossary IDs for each source/target language pair.

To enable external mode, add this to your configuration:

```yaml
SilverStripe\SiteConfig\SiteConfig:
  glossary_mode: external
```

Glossary records are auto-created for each language pair based on your Fluent locales. You then select the appropriate Deepl glossary ID from a dropdown populated by the glossaries available in your Deepl account for that language pair.

## Style Rules

Deepl supports style rules that allow you to control the tone and style of translations per target language. Style rule records are automatically created for each target language based on your Fluent locales.

You can assign a style rule to each target language in SiteConfig under the "Deepl" tab. The available style rules are fetched from your Deepl account and shown in a dropdown per language.

## CMS

In CMS you have to add `USE_DEEPL` permission to non-admin groups.

## Alternatives

If you want to use Google Translate consider to use: [bratiask/silverstripe-autotranslate](https://github.com/bratiask/silverstripe-autotranslate)
