/* global Alpine */

import deeplGlossary from './glossary';

document.addEventListener('alpine:init', () => {
  // console.log('alpine:init');
  Alpine.data('deeplGlossary', deeplGlossary);
});
