<template>
  <div v-if="termin" class="mt-3">
    <CCard>
      <CCardHeader>
        <strong>Detail Termin</strong>
        <CButton color="secondary" size="sm" class="float-end ms-2" @click="onClose">Tutup</CButton>
      </CCardHeader>
      <CCardBody>
        <CForm @submit.prevent="handleSave">
          <CRow class="mb-2">
            <CCol md="6">
              <CFormLabel>Nama Termin</CFormLabel>
              <CFormInput :value="termin.nama_termin" readonly />
            </CCol>
            <CCol md="6">
              <CFormLabel>Nilai Termin</CFormLabel>
              <CFormInput :value="formatCurrency(termin.nilai_termin)" readonly />
            </CCol>
          </CRow>
          <CRow class="mb-2">
            <CCol md="4">
              <CFormLabel>DP (%)</CFormLabel>
              <CFormInput :value="`${termin.dp_percentage}%`" readonly />
            </CCol>
            <CCol md="4">
              <CFormLabel>Nilai DP</CFormLabel>
              <CFormInput :value="formatCurrency(termin.nilai_dp)" readonly />
            </CCol>
            <CCol md="4">
              <CFormLabel>Nilai Pelunasan</CFormLabel>
              <CFormInput :value="formatCurrency(termin.nilai_pelunasan)" readonly />
            </CCol>
          </CRow>
          <CRow class="mb-2">
            <CCol md="4">
              <CFormLabel>Status Termin</CFormLabel>
              <CFormSelect v-model="statusForm.status_termin" required>
                <option value="Belum Dibayar">Belum Dibayar</option>
                <option value="DP Dibayar">DP Dibayar</option>
                <option value="Lunas">Lunas</option>
              </CFormSelect>
            </CCol>
            <CCol md="4">
              <CFormLabel>Tanggal DP</CFormLabel>
              <CFormInput type="date" v-model="statusForm.tanggal_dp" />
            </CCol>
            <CCol md="4">
              <CFormLabel>Tanggal Pelunasan</CFormLabel>
              <CFormInput type="date" v-model="statusForm.tanggal_pelunasan" />
            </CCol>
          </CRow>
          <CRow class="mb-2">
            <CCol md="12">
              <CFormLabel>Keterangan</CFormLabel>
              <CFormTextarea :value="termin.keterangan || '-'" readonly rows="2" />
            </CCol>
          </CRow>
          <CButton type="submit" color="primary" class="me-2">Simpan</CButton>
          <CButton type="button" color="secondary" @click="onClose">Batal</CButton>
        </CForm>
      </CCardBody>
    </CCard>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  termin: {
    type: Object,
    default: null
  },
  onClose: {
    type: Function,
    required: true
  },
  onSave: {
    type: Function,
    required: true
  }
});

const statusForm = ref({
  status_termin: '',
  tanggal_dp: '',
  tanggal_pelunasan: ''
});

// Watch for changes in termin prop
watch(() => props.termin, (newTermin) => {
  if (newTermin) {
    statusForm.value = {
      status_termin: newTermin.status_termin,
      tanggal_dp: newTermin.tanggal_dp || '',
      tanggal_pelunasan: newTermin.tanggal_pelunasan || ''
    };
  }
}, { immediate: true });

const handleSave = () => {
  props.onSave(statusForm.value);
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID').format(value || 0);
};
</script>

<style scoped>
.card {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
</style> 