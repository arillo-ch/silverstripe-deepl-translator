<div x-data="deeplGlossary($GlossaryConfig)">
  <table class="w-full">
    <thead>
      <tr>
        <template x-for="locale in locales">
          <th>
            <span x-text="locale.Lang"></span>
          </th>
        </template>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <template x-for="entry in glossaryEntries">
        <tr>
          <template x-for="locale in locales">
            <td>
              <input type="text" x-model="entry[`${locale.Lang}`]" :value="entry[`${locale.Lang}`]">
            </td>
          </template>
          <td>
          <button @click="removeEntry(entry.id)" class="btn">-</button>
        </td>
        </tr>
      </template>

      <template x-if="!!newRow">
        <tr>
          <template x-for="locale in locales">
            <td>
              <input type="text" :name="`new[${locale.Lang}]`" x-model="newRow[`${locale.Lang}`]">
            </td>
          </template>
          <td>
            <button @click="createNewEntry" class="btn">+</button>
          </td>
        </tr>
      </template>
    </tbody>
  </table>
</div>