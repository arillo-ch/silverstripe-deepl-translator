/*global jQuery, ss, tinymce */
jQuery(function () {
  DeeplField(jQuery).init();
});

function DeeplField($) {
  return {
    init: function () {
      console.log('DeeplField');
      var body = $('body');

      body.on('click', '.js-deepl', function (event) {
        setTimeout(function () {
          $(event.target).find('ul').toggle();
        }, 50);
      });

      body.on('click', function (event) {
        if (false === $(event.target).hasClass('js-deepl')) {
          $('.js-deepl').find('ul').hide();
        }
      });

      body.on(
        'click',
        '.js-deepl-translate',
        function (event) {
          event.preventDefault();
          var elements = this.elements(event);
          elements.holder.append(
            '<div class="translate-in-progress js-translate-in-progress"><span>Translating...</span></div>'
          );
          elements.label.find('ul').hide();
          var payload = {
            fromLocale: elements.label.data('source-lang'),
            toLocale: elements.label.data('target-lang'),
            text:
              elements.textarea.length > 0
                ? elements.textarea.val()
                : elements.input.val(),
          };

          setTimeout(function () {
            elements.holder.find('.js-translate-in-progress').fadeOut(200);
          }, 10000);

          $.post(
            'api/deepl/translate',
            payload,
            function (response) {
              if (response.text) {
                this.setValue(elements.input, elements.textarea, response.text);
              }
              elements.holder.find('.js-translate-in-progress').fadeOut(200);
            }.bind(this)
          );
        }.bind(this)
      );

      body.on(
        'click',
        '.js-deepl-reset',
        function (event) {
          var elements = this.elements(event);
          this.setValue(
            elements.input,
            elements.textarea,
            elements.label.data('value')
          );
          elements.label.find('ul').hide();
        }.bind(this)
      );

      body.on(
        'click',
        '.js-deepl-revert',
        function (event) {
          var elements = this.elements(event);
          this.setValue(
            elements.input,
            elements.textarea,
            elements.label.data('source-value')
          );
          elements.label.find('ul').hide();
        }.bind(this)
      );
    },

    elements: function (event) {
      var $label = $(event.target).closest('.deepl');
      var $holder = $label.closest('.form-group').find('.form__field-holder');
      return {
        label: $label,
        holder: $holder,
        input: $holder.find('input'),
        textarea: $holder.find('textarea'),
      };
    },

    setValue: function (input, textarea, value) {
      if (textarea.length > 0) {
        textarea.val(value);
        if ('tinyMCE' === textarea.data('editor')) {
          tinymce.get(textarea.attr('id')).load();
          // tinymce.get(textarea.attr('id')).setContent(value);
        }
      } else {
        input.val(value);
      }
    },
  };
}
