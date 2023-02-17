# arillo/silverstripe-deepl-translator

Adds Deepl translation API to a Fluent translated SilverStripe CMS.

## Installation

```
composer require arillo/silverstripe-deepl-translator
```

## Configuration

Create a Deepl API key and add it to your `.env`:

```
DEEPL_APIKEY="<YOUR_DEEPL_APIKEY>"
```

Configure ypur DataObjects to display the Deepl translation handles.
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
  frontend_publish_required: false
  # cms_publish_required: false
  extensions:
    - 'TractorCow\Fluent\Extension\FluentVersionedExtension'
```

Like this it is possible to apply the deepl config after `#myfluentcms`:

```
---
Name: mydeepl
After: 
 - '#myfluentcms'
 - '#silverstripe-deepl-translator'
---
SilverStripe\CMS\Model\SiteTree:
  extensions:
    - Arillo\Deepl\TranslationExtension

Arillo\Elements\ElementBase:
  extensions:
    - Arillo\Deepl\TranslationExtension
```