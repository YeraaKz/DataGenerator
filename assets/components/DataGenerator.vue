<template>
  <div class="container mt-3" @scroll="checkScroll" style="height: 100vh; overflow-y: auto;">
    <div class="row mb-3 align-items-end">
      <div class="col">
        <label for="selectedLocale" class="form-label">Region:</label>
        <select v-model="selectedLocale" class="form-select">
          <option value="en_US">English</option>
          <option value="ru_RU">Russian</option>
          <option value="fr_FR">French</option>
        </select>
      </div>
      <div class="col d-flex">
        <label for="errorsPerRecord">Errors:</label>
        <input type="range" min="0" max="10" step="0.25" v-model.number="errorsPerRecord" class="form-range">
        <input type="number" v-model.number="errorsPerRecord" class="form-control">
      </div>
      <div class="col">
        <label for="seedInput" class="form-label">Seed:</label>
        <div class="input-group">
          <input type="text" id="seedInput" v-model="seed" class="form-control">
          <button @click="generateSeed" class="btn btn-secondary">Random</button>
        </div>
      </div>
    </div>
    <table v-if="data.length" class="table table-striped mt-3 h-100">
      <thead>
      <tr>
        <th>#</th>
        <th>UUID</th>
        <th>Name</th>
        <th>Address</th>
        <th>Phone</th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="item in data" :key="item.uuid">
        <td>{{ item.number }}</td>
        <td>{{ item.uuid }}</td>
        <td>{{ item.name }}</td>
        <td>{{ item.address }}</td>
        <td>{{ item.phone }}</td>
      </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      selectedLocale: 'en_US',
      errorsPerRecord: 0,
      seed: 0,
      data: [],
      page: 1,
      limit: 20,
      originalData: [],
      seedLocked: false,
      busy: false
    };
  },
  methods: {
    fetchData() {
      if (this.busy) return;
      this.busy = true;
      const isInitialLoad = this.page === 1;
      axios.post('/api/generate', {
        seed: this.seed,
        page: this.page,
        limit: 20,
        locale: this.selectedLocale,

      })
          .then(response => {
            if (isInitialLoad) {
              this.originalData = response.data;
            } else {
              this.originalData = [...this.originalData, ...response.data];
            }
            this.addErrorsToOriginalData();
            this.data = [...this.originalData];
            this.busy = response.data.length < (isInitialLoad ? 20 : 10);
            if (!this.busy) this.page++;
          })
          .catch(error => {
            console.error('Error fetching data:', error);
            this.busy = false;
          });
    },
    checkScroll(event) {
      let wrapper = event.target;
      if (wrapper.scrollHeight - (wrapper.scrollTop + wrapper.clientHeight) < 100) {
        this.fetchData();
      }

    },
    addErrorsToOriginalData() {
      axios.post('/api/add-errors', {
        data: JSON.parse(JSON.stringify(this.originalData)),
        errorsPerRecord: this.errorsPerRecord,
        locale: this.selectedLocale
      })
          .then(response => {
            this.data = response.data;
          })
          .catch(error => {
            console.error('Error adding errors:', error);
          });
    },
    generateSeed() {
      this.seed = Math.floor(Math.random() * 1000000).toString();
      this.errorsPerRecord = 0;
      this.resetData();
    },

    resetData() {
      this.data = [];
      this.originalData = [];
      this.page = 1;
      this.fetchData();
    }
  },
  watch: {
    selectedLocale() {
      this.resetData();
    },

    errorsPerRecord() {
      this.addErrorsToOriginalData();
    },
    seed() {
      setTimeout(() => {
        this.resetData();
      }, 500);
    }
  },
  mounted() {
    this.fetchData();
  }
}
</script>
