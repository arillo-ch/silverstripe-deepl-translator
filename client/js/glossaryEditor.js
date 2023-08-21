/* global Alpine */

import deeplGlossary from './glossary';

if (typeof Alpine !== 'undefined') {
  Alpine.data('multiselectfield', deeplGlossary);
}
document.addEventListener('alpine:init', () => {
  Alpine.data('multiselectfield', deeplGlossary);
});
