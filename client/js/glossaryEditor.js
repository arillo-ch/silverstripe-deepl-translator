import Alpine from 'alpinejs';
import deeplGlossary from './glossary';

(function ($) {
  if (typeof window.Alpine === 'undefined') {
    window.Alpine = Alpine;
  }
  Alpine.data('deeplGlossary', deeplGlossary);
  Alpine.start();
})(window.jQuery);
