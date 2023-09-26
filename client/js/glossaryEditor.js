/* global Alpine */

import deeplGlossary from './glossary';

if (typeof Alpine !== 'undefined') {
  Alpine.data('deeplGlossary', deeplGlossary);
}
document.addEventListener('alpine:init', () => {
  Alpine.data('deeplGlossary', deeplGlossary);
});
