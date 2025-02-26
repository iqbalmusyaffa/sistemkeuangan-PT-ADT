<template>
    <div class="dashboard">
      <b-container fluid>
        <b-row>
          <b-col md="12">
            <h1 class="text-center">Dashboard</h1>
          </b-col>
        </b-row>
        <b-row>
          <b-col md="6">
            <b-card title="Sales Overview" class="mb-4">
              <canvas id="salesChart"></canvas>
            </b-card>
          </b-col>
          <b-col md="6">
            <b-card title="User Statistics" class="mb-4">
              <canvas id="userChart"></canvas>
            </b-card>
          </b-col>
        </b-row>
        <b-row>
          <b-col md="12">
            <b-card title="Recent Transactions">
              <b-table :items="transactions" :fields="fields" striped hover></b-table>
            </b-card>
          </b-col>
        </b-row>
      </b-container>
    </div>
  </template>

  <script>
  import { Chart } from 'chart.js';

  export default {
    data() {
      return {
        transactions: [
          { id: 1, user: 'John Doe', amount: '$100', date: '2023-10-01' },
          { id: 2, user: 'Jane Smith', amount: '$200', date: '2023-10-02' },
        ],
        fields: [
          { key: 'id', label: 'ID' },
          { key: 'user', label: 'User' },
          { key: 'amount', label: 'Amount' },
          { key: 'date', label: 'Date' },
        ],
      };
    },
    mounted() {
      this.renderCharts();
    },
    methods: {
      renderCharts() {
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const userCtx = document.getElementById('userChart').getContext('2d');

        new Chart(salesCtx, {
          type: 'line',
          data: {
            labels: ['January', 'February', 'March', 'April', 'May'],
            datasets: [{
              label: 'Sales',
              data: [12, 19, 3, 5, 2],
              borderColor: 'rgba(75, 192, 192, 1)',
              fill: false,
            }],
          },
        });

        new Chart(userCtx, {
          type: 'bar',
          data: {
            labels: ['User A', 'User B', 'User C'],
            datasets: [{
              label: 'Users',
              data: [10, 20, 30],
              backgroundColor: 'rgba(153, 102, 255, 0.2)',
              borderColor: 'rgba(153, 102, 255, 1)',
              borderWidth: 1,
            }],
          },
        });
      },
    },
  };
  </script>

  <style scoped>
  .dashboard {
    padding: 20px;
  }
  </style>
