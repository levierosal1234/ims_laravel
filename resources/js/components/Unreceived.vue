<template>
  <div class="vue-container">
    <h1 class="vue-title">Unreceived Module</h1>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>
              <input type="checkbox" @click="toggleAll" v-model="selectAll" />
              <span class="header-date"></span>
            </th>
            <th>Details</th>
            <th>Order Details</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in inventory" :key="item.id">
            <td>
              <div class="checkbox-container">
                <input type="checkbox" v-model="item.checked" />
                <span class="placeholder-date">{{ item.shipBy || 'N/A' }}</span>
              </div>
              <img :src="item.imageUrl" alt="Product Image" class="product-thumbnail" />
            </td>
            <td class="vue-details">
              <span class="product-name">{{ item.AStitle }}</span>
            </td>
            <td class="vue-details">
              <span><strong>ID:</strong> {{ item.ProductID }}</span><br />
              <span><strong>ASIN:</strong> {{ item.ProductModuleLoc }}</span><br />
              <span><strong>FNSKU:</strong> {{ item.serialnumber }}</span><br />
              <span><strong>Condition:</strong> {{ item.gradingview }}</span>
            </td>
            <td>
              {{ item.totalquantity }}
              <button @click="toggleDetails(index)" class="more-details-btn">
                {{ expandedRows[index] ? 'Less Details' : 'More Details' }}
              </button>
            </td>
          </tr>
          <tr v-if="expandedRows[index]" class="expanded-row">
            <td colspan="4">
              <div class="expanded-content">
                <strong>Product Name:</strong> {{ item.ProductTitle }}
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <!-- Pagination -->
      <div class="pagination">
        <button @click="prevPage" :disabled="currentPage === 1" class="pagination-button">Previous</button>
        <span class="pagination-info">Page {{ currentPage }} of {{ totalPages }}</span>
        <button @click="nextPage" :disabled="currentPage === totalPages" class="pagination-button">Next</button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { eventBus } from './eventBus'; // Using your event bus
import '../../css/modules.css';

export default {
  name: 'ProductList',
  data() {
    return {
      inventory: [],
      currentPage: 1,
      totalPages: 1,
      selectAll: false,
      expandedRows: {},
    };
  },
  computed: {
    searchQuery() {
      return eventBus.searchQuery; // Making search reactive
    },
  },
  methods: {
    async fetchInventory() {
      try {
        const response = await axios.get(`http://127.0.0.1:8000/products`, {
          params: { search: this.searchQuery, page: this.currentPage, location: 'stockroom', },
        });

        this.inventory = response.data.data;
        this.totalPages = response.data.last_page;
      } catch (error) {
        console.error('Error fetching inventory data:', error);
      }
    },
    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
        this.fetchInventory();
      }
    },
    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
        this.fetchInventory();
      }
    },
    toggleAll() {
      this.inventory.forEach((item) => (item.checked = this.selectAll));
    },
    toggleDetails(index) {
      this.$set(this.expandedRows, index, !this.expandedRows[index]);
    },
  },
  watch: {
    searchQuery() {
      this.currentPage = 1; // Reset to first page on search
      this.fetchInventory();
    },
  },
  mounted() {
    this.fetchInventory();
  },
};
</script>
