<template>
  <div class="orders-container">
    <h1 class="orders-title">Cleaning</h1>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>
              <input type="checkbox" @click="toggleAll" />
              <span class="header-date"></span>
            </th>
            <th>Details</th>
            <th>Order Details</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in paginatedInventory" :key="item.id">
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
            <td class="order-details">
              <span><strong>ID:</strong> {{ item.id }}</span><br />
              <span><strong>FNSKU:</strong> {{ item.fnsku }}</span><br />
              <span><strong>MSKU:</strong> {{ item.msku }}</span><br />
              <span><strong>Condition:</strong> {{ item.condition }}</span>
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
                <strong>Product Name:</strong> {{ item.productname }}
              </div>
            </td>
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
      expandedRows: {},
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
        const response = await axios.get('http://127.0.0.1:8000/test');
        this.inventory = response.data;
      } catch (error) {
        console.error('Error fetching inventory data:', error);
      }
    },
    prevPage() {
      if (this.currentPage > 1) this.currentPage--;
    },
    nextPage() {
      if (this.currentPage < this.totalPages) this.currentPage++;
    },
    toggleAll() {
      this.selectAll = !this.selectAll;
      this.inventory.forEach((item) => (item.checked = this.selectAll));
    },
    toggleDetails(index) {
      this.$set(this.expandedRows, index, !this.expandedRows[index]);
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
  color: #333;
  text-align: left;
  white-space: normal;
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

.order-details {
  font-size: 0.9rem;
  line-height: 1.5;
}

.more-details-btn {
  background-color: #0073bb;
  color: white;
  border: none;
  padding: 5px 10px;
  cursor: pointer;
  font-size: 0.85rem;
  border-radius: 4px;
  margin-left: 10px;
}

.more-details-btn:hover {
  background-color: #0056a3;
}

.expanded-row {
  background-color: #eef7ff;
}

.expanded-content {
  padding: 10px;
  font-size: 0.9rem;
  color: #333;
  border-top: 1px solid #ddd;
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

/* Mobile Adjustments */
@media (max-width: 768px) {
  .orders-container {
    padding: 10px;
  }

  .orders-title {
    font-size: 1.2rem;
  }

  .table-container {
    overflow-x: auto;
  }

  th, td {
    padding: 8px;
    font-size: 0.85rem;
  }

  .pagination {
    flex-direction: column;
    gap: 5px;
  }
}
</style>
