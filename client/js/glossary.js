export default (config) => ({
  ...config,
  deeplGlossaries: [],
  newRow: null,
  glossaryEntries: [],
  init() {
    this.deeplListGlossaries();
  },

  async deeplListGlossaries() {
    fetch(this.apiUrl('listGlossaries'), {
      method: 'get',
    })
      .then(async (response) => {
        const data = await response.json();

        if (!response.ok) {
          const error = data?.message || response.status;
          return Promise.reject(error);
        }

        this.deeplGlossaries = data;
        this.setupNewRow();
      })
      .catch((error) => {
        console.error(error);
      });
  },

  setupNewRow() {
    const row = {};
    this.locales.forEach((locale) => {
      row[locale.Lang] = '';
    });

    this.newRow = row;
  },

  createNewEntry() {
    const entry = { ...this.newRow, id: this.glossaryEntries.length };
    this.glossaryEntries.push(entry);
    this.setupNewRow();
  },

  removeEntry(id) {
    this.glossaryEntries = [...this.glossaryEntries.filter((e) => e.id !== id)];
  },

  apiUrl(append = null) {
    if (!append) return this.apiBase;

    return `${this.apiBase}${append}`;
  },
});
