<template>
  <div class="orders-container">
    <h1 class="orders-title">Labelling</h1>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>
              <input type="checkbox" @click="toggleAll" />
              <span class="header-date"></span>
            </th>
            <th>Details</th>
            <th>Order ID</th>
            <th>FNSKU</th>
            <th>MSKU</th>
            <th>Condition</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in paginatedInventory" :key="item.id">
            <td>
              <div class="checkbox-container">
                <input type="checkbox" v-model="item.checked" />
                <span class="placeholder-date">{{ item.shipBy || 'Placeholder Date' }}</span>
              </div>
            </td>
            <td class="product-cell">
              <img
                :src="item.imageUrl"
                alt="Product Image"
                class="product-thumbnail"
              />
              <span class="product-name">{{ item.productname }}</span>
            </td>
            <td>{{ item.id }}</td>
            <td>{{ item.fnsku }}</td>
            <td>{{ item.msku }}</td>
            <td>{{ item.condition }}</td>
            <td>{{ item.totalquantity }}</td>
          </tr>
        </tbody>
      </table>
      <div class="pagination">
        <button
          @click="prevPage"
          :disabled="currentPage === 1"
          class="pagination-button"
        >
          Previous
        </button>
        <span class="pagination-info">Page {{ currentPage }} of {{ totalPages }}</span>
        <button
          @click="nextPage"
          :disabled="currentPage === totalPages"
          class="pagination-button"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { eventBus } from './eventBus';
import axios from 'axios';

export default {
  name: 'orders',
  data() {
    return {
      inventory: [],
      currentPage: 1,
      itemsPerPage: 10,
      selectAll: false,
    };
  },
  computed: {
    filteredInventory() {
      const searchQuery = eventBus.searchQuery.toLowerCase();
      return this.inventory.filter((item) =>
        item.productname.toLowerCase().includes(searchQuery)
      );
    },
    totalPages() {
      return Math.ceil(this.filteredInventory.length / this.itemsPerPage);
    },
    paginatedInventory() {
      const startIndex = (this.currentPage - 1) * this.itemsPerPage;
      return this.filteredInventory.slice(
        startIndex,
        startIndex + this.itemsPerPage
      );
    },
  },
  methods: {
    async fetchInventory() {
      try {
        const response = await axios.get('testing123');
        this.inventory = response.data.map((item) => ({
          ...item,
          imageUrl: item.imageUrl || 'https://via.placeholder.com/60x60?text=No+Image',
          checked: false,
          shipBy: '', // Placeholder for future dates
        }));
      } catch (error) {
        console.error('Error fetching inventory data:', error);
      }
    },
    toggleAll() {
      this.selectAll = !this.selectAll;
      this.paginatedInventory.forEach((item) => {
        item.checked = this.selectAll;
      });
    },
    nextPage() {
      if (this.currentPage < this.totalPages) this.currentPage++;
    },
    prevPage() {
      if (this.currentPage > 1) this.currentPage--;
    },
  },
  mounted() {
    this.fetchInventory();
  },
};
</script>

<style scoped>
.orders-container {
  padding: 20px;
  font-family: Arial, sans-serif;
  background-color: #f5f5f5;
}

.orders-title {
  text-align: left;
  font-size: 1.5rem;
  margin-bottom: 10px;
  color: #111;
}

.table-container {
  overflow-x: auto;
  border: 1px solid #ddd;
  border-radius: 6px;
  background: #fff;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

thead {
  background-color: #f7f7f7;
  border-bottom: 2px solid #ddd;
}

th {
  text-align: left;
  padding: 10px 12px;
  font-weight: bold;
  color: #333;
  white-space: nowrap;
}

tbody tr:nth-child(even) {
  background-color: #f9f9f9;
}

tbody tr:hover {
  background-color: #f1f1f1;
}

td {
  padding: 12px;
  vertical-align: middle;
  white-space: nowrap;
  color: #333;
  text-align: left;
}

.checkbox-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
}

.placeholder-date {
  font-size: 0.85rem;
  color: #666;
}

.product-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.product-thumbnail {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 4px;
  border: 1px solid #ddd;
}

.product-name {
  color: #0073bb;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
}

.product-name:hover {
  text-decoration: underline;
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
}

.pagination-info {
  font-size: 1rem;
  color: #555;
}

.pagination-button {
  padding: 8px 12px;
  font-size: 0.9rem;
  color: #fff;
  background-color: #0073bb;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.pagination-button:disabled {
  background-color: #ddd;
  cursor: not-allowed;
}

.pagination-button:not(:disabled):hover {
  background-color: #0056a3;
}
</style>
