<div class="deeplGlossary grid grid-field ss-gridfield field form-group--no-label" x-data="deeplGlossary($GlossaryConfig)">
  <div class="toolbar--content ss-gridfield-buttonrow ss-gridfield-buttonrow-before">
    <div class="pull-xs-right">
      <button 
        @click="saveGlossaries"
        :disabled="isLoading || !isDirty"
        class="btn action btn btn-big btn-primary font-icon-save"
      >
        <%t Arillo\Deepl\GlossaryEditor.Save 'Save glossary' %>
      </button>
    </div>
    <div class="btn-toolbar">
      <div>
        <input 
          type="text"
          x-model="search"
          @keyup.escape="search = ''"
          placeholder="<%t Arillo\Deepl\GlossaryEditor.Search 'Search' %>"
        >
      </div>
      <button 
        @click="search = ''"
        :disabled="search == '' || isLoading"
        class="btn btn--no-text btn-sm font-icon-cancel btn btn-primary"
      ></button>
    </div>
  </div>
  <table class="table grid-field__table deeplGlossary__table" cellpadding="0" cellspacing="0">
    <thead>
      <tr class="sortable-header">
        <th></th>
        <template x-for="locale in locales">
          <th class="main">
            <div
              @click="sorterBy(locale.Lang)" 
              class="action btn btn-big grid-field__sort"
            >
              <span class="btn__title" x-text="locale.Lang"></span>
            </div>
          </th>
        </template>
        <th></th>
      </tr>
    </thead>
    <tbody class="ss-gridfield-items">
      <template x-if="!!newEntry">
        <tr class="ss-gridfield-item new-entry">
          <td></td>
          <template x-for="locale in locales">
            <td>
              <input 
                type="text"
                :disabled="isLoading"
                x-model="newEntry[`${locale.Lang}`]"
                placeholder="<%t Arillo\Deepl\GlossaryEditor.NewEntry 'Neuer Eintrag...' %>"
              >
            </td>
          </template>
          <td class="grid-field__col-compact action-menu">
            <button 
              @click="createNewEntry"
              :disabled="isLoading || !isNewEntryValid()"
              class="btn btn--no-text btn-sm font-icon-plus btn btn-primary"
            ></button>
          </td>
        </tr>
      </template>
      <template x-for="(entry, index) in glossaryEntriesForTable">
        <tr :class="entry.isNew ? 'added-entry' : ''">
          <td x-text="index+1"></td>
          <template x-for="locale in locales">
            <td>
              <input 
                type="text"
                :disabled="isLoading"
                x-model="entry[`${locale.Lang}`]"
                :value="entry[`${locale.Lang}`]"
                :name="`DeeplGlossary[${entry.id}][${locale.Lang}]`"
              >
            </td>
          </template>
          <td class="grid-field__col-compact action-menu">
            <button 
              type="button"
              @click="removeEntry(entry.id)"
              :disabled="isLoading"
              class="btn btn--no-text btn-sm font-icon-minus-circle btn btn-danger"
            ></button>
          </td>
        </tr>
      </template>
    </tbody>
  </table>
</div>