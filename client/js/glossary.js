const EMPTY_STRING = '';

let isDirtMessage =
  'Es gibt ungespeicherte Änderungen am Glossar. Seite dennoch verlassen?';

const beforeUnload = (e) => {
  e.preventDefault();
  return (e.returnValue = isDirtMessage);
};

export default (config) => ({
  ...config,
  newEntry: null,
  glossaryEntries: [],
  glossaryEntriesForTable: [],
  search: EMPTY_STRING,
  isLoading: true,
  isFirstLoad: true,
  sorter: {
    field: null,
    dir: 'ASC',
  },
  isDirty: false,

  init() {
    isDirtMessage = this.isDirtMessage || isDirtMessage;
    this.$watch('glossaryEntries', () => {
      this.computeGlossaryEntriesForTable();
      this.$nextTick(() => {
        if (!this.isFirstLoad) {
          this.isDirty = true;
        }
      });
    });
    this.$watch('isDirty', (value) => {
      if (value) {
        window.addEventListener('beforeunload', beforeUnload);
      } else {
        console.log('remove beforeunload');
        window.removeEventListener('beforeunload', beforeUnload);
      }
    });
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
        setTimeout(() => (this.isFirstLoad = false), 100);
      })
      .catch((error) => {
        console.error(error);
      });
  },

  prepareGlossaryEntries(data) {
    this.glossaryEntries = data.map((e) => ({
      ...e,
      id: this.generateId(),
      isNew: false,
    }));
  },

  setupNewEntry() {
    const entry = {};
    this.locales.forEach((locale) => {
      entry[locale.Lang] = '';
    });

    this.newEntry = entry;
  },

  createNewEntry() {
    const entry = { ...this.newEntry, id: this.generateId(), isNew: true };
    this.glossaryEntries.push(entry);
    this.setupNewEntry();
  },

  isNewEntryValid() {
    return Object.keys(this.newEntry).reduce((acc, lng) => {
      if (acc && this.newEntry[lng] === EMPTY_STRING) {
        return false;
      }
      return acc;
    }, true);
  },

  generateId() {
    if (typeof crypto === 'undefined') {
      return 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        const v = c == 'x' ? r : (r & 0x3) | 0x8;
        return v.toString(16);
      });
    } else {
      return crypto.randomUUID();
    }
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

        this.prepareGlossaryEntries(data.glossaryEntries);
        this.setupNewEntry();
        this.isDirty = false;
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
