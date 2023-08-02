<div class="deeplGlossary" x-data="deeplGlossary($GlossaryConfig)">
  <div>
    <div>
      <input type="text" x-model="search">
    </div>
    <div>
      <button @click="search = ''" :disabled="search == '' || isLoading" class="btn">
        x
      </button>
    </div>
    <div>
      <button @click="saveGlossaries" :disabled="isLoading" class="btn">
        Save
      </button>
    </div>
  </div>
  <table class="table grid-field__table" cellpadding="0" cellspacing="0">
    <thead>
      <tr class="sortable-header">
        <template x-for="locale in locales">
          <th class="main">
            <button type="button" @click="sorterBy(locale.Lang)" class="action btn btn-big grid-field__sort">
              <span class="btn__title" x-text="locale.Lang"></span>
            </button>
          </th>
        </template>
        <th></th>
      </tr>
    </thead>
    <tbody class="ss-gridfield-items">
      <template x-if="!!newEntry">
        <tr class="ss-gridfield-item first last odd">
          <template x-for="locale in locales">
            <td>
              <input type="text" :disabled="isLoading" x-model="newEntry[`${locale.Lang}`]">
            </td>
          </template>
          <td class="grid-field__col-compact action-menu">
            <button @click="createNewEntry" :disabled="isLoading" class="btn btn--no-text btn-sm font-icon-plus btn btn-primary"></button>
          </td>
        </tr>
      </template>
      <template x-for="entry in glossaryEntriesForTable">
        <tr>
          <template x-for="locale in locales">
            <td>
              <input type="text" :disabled="isLoading" x-model="entry[`${locale.Lang}`]" :value="entry[`${locale.Lang}`]">
            </td>
          </template>
          <td class="grid-field__col-compact action-menu">
            <button type="button" @click="removeEntry(entry.id)" :disabled="isLoading" class="btn btn--no-text btn-sm font-icon-minus-circle btn btn-secondary"></button>
          </td>
        </tr>
      </template>
    </tbody>
  </table>
</div>