<template>
  <CModal :visible="visible" @close="onClose" size="lg" title="Detail Invoice">
    <CModalHeader>
      <CModalTitle>Detail Invoice</CModalTitle>
    </CModalHeader>
    <CModalBody>
      <div><strong>No Invoice:</strong> {{ invoice.invoice_number }}</div>
      <div><strong>Tanggal:</strong> {{ invoice.invoice_date }}</div>
      <div><strong>Status:</strong> {{ invoice.status }}</div>
      <div><strong>Catatan:</strong> {{ invoice.notes }}</div>
      <div><strong>Proyek:</strong> {{ invoice.proyek?.nama_proyek }}</div>
      <div><strong>Customer:</strong> {{ invoice.proyek?.nama_customer }}</div>
      <h5 class="mt-3">Items</h5>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Unit</th>
            <th>Harga</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in invoice.purchase_materials" :key="item.id">
            <td>{{ item.item }}</td>
            <td>{{ item.qty }}</td>
            <td>{{ item.unit?.unit_name }}</td>
            <td>{{ item.harga }}</td>
            <td>{{ item.total_harga }}</td>
          </tr>
        </tbody>
      </table>
      <h5 class="mt-3">Tax Breakdown</h5>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Tax Type</th>
            <th>Amount</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>PPN (11%)</td>
            <td>{{ invoice.ppn_amount }}</td>
          </tr>
          <tr>
            <td>PPH Non-Final</td>
            <td>{{ invoice.pph_non_final_amount }}</td>
          </tr>
          <tr>
            <td>PPH Final</td>
            <td>{{ invoice.pph_final_amount }}</td>
          </tr>
        </tbody>
      </table>
    </CModalBody>
    <CModalFooter>
      <CButton color="secondary" @click="onClose">Tutup</CButton>
    </CModalFooter>
  </CModal>
</template>

<script setup>
defineProps({
  visible: Boolean,
  invoice: Object,
  onClose: Function
});
</script>
