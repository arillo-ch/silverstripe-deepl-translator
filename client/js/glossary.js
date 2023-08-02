const EMPTY_STRING = '';

export default (config) => ({
  ...config,
  newEntry: null,
  glossaryEntries: [],
  glossaryEntriesForTable: [],
  search: EMPTY_STRING,
  isLoading: true,
  sorter: {
    field: null,
    dir: 'ASC',
  },

  init() {
    this.$watch('glossaryEntries', () => this.computeGlossaryEntriesForTable());
    this.$watch('sorter', () => this.computeGlossaryEntriesForTable());
    this.$watch('search', () => this.computeGlossaryEntriesForTable());
    this.fetchGlossaryEntries();
    this.sorter = {
      field: this.locales.find((l) => l.IsGlobalDefault == true).Lang,
      dir: 'ASC',
    };
  },

  async fetchGlossaryEntries() {
    this.isLoading = true;
    fetch(this.apiUrl('glossaryEntries'), {
      method: 'get',
    })
      .then(async (response) => {
        const data = await response.json();

        if (!response.ok) {
          const error = data?.message || response.status;
          return Promise.reject(error);
        }

        this.prepareGlossaryEntries(data);
        this.setupNewEntry();
        this.isLoading = false;
      })
      .catch((error) => {
        console.error(error);
      });
  },

  prepareGlossaryEntries(data) {
    this.glossaryEntries = data.map((e, idx) => ({ ...e, id: idx }));
  },

  setupNewEntry() {
    const entry = {};
    this.locales.forEach((locale) => {
      entry[locale.Lang] = '';
    });

    this.newEntry = entry;
  },

  createNewEntry() {
    const entry = { ...this.newEntry, id: this.glossaryEntries.length };
    this.glossaryEntries.push(entry);
    this.setupNewEntry();
  },

  computeGlossaryEntriesForTable() {
    const languages = this.locales.map(({ Lang }) => Lang);
    const search = this.search.toLowerCase();
    this.glossaryEntriesForTable = [
      ...this.glossaryEntries
        .filter((e) => {
          return languages.reduce((acc, lng) => {
            if (
              !acc &&
              e[lng] &&
              e[lng] !== EMPTY_STRING &&
              e[lng].toLowerCase().includes(search)
            ) {
              return true;
            }
            return acc;
          }, false);
        })
        .sort((a, b) => {
          const x = a[this.sorter.field].toLowerCase();
          const y = b[this.sorter.field].toLowerCase();
          if (x > y) {
            return this.sorter.dir == 'ASC' ? 1 : -1;
          }
          if (x < y) {
            return this.sorter.dir == 'ASC' ? -1 : 1;
          }
          return 0;
        }),
    ];
  },

  sorterBy(field) {
    if (this.sorter.field === field) {
      this.sorter.dir = this.sorter.dir == 'ASC' ? 'DESC' : 'ASC';
    } else {
      this.sorter = { field: field, dir: 'ASC' };
    }
  },

  async saveGlossaries() {
    this.isLoading = true;
    const formData = new FormData();
    formData.append('glossaryEntries', JSON.stringify(this.glossaryEntries));
    fetch(this.apiUrl('saveGlossaries'), {
      method: 'post',
      body: formData,
    })
      .then(async (response) => {
        const data = await response.json();

        if (!response.ok) {
          const error = data?.message || response.status;
          return Promise.reject(error);
        }

        this.prepareGlossaryEntries(data);
        this.setupNewEntry();
      })
      .catch((error) => {
        console.error(error);
      })
      .finally(() => (this.isLoading = false));
  },

  removeEntry(id) {
    this.glossaryEntries = [...this.glossaryEntries.filter((e) => e.id !== id)];
  },

  apiUrl(append = null) {
    if (!append) return this.apiBase;

    return `${this.apiBase}${append}`;
  },
});
