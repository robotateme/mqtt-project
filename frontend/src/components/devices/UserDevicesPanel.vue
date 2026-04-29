<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';

const props = defineProps({
  apiBaseUrl: {
    type: String,
    required: true,
  },
  authHeader: {
    type: String,
    required: true,
  },
  devices: {
    type: Array,
    required: true,
  },
  loading: {
    type: Boolean,
    required: true,
  },
  error: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(['refresh', 'create', 'update', 'delete']);

const activeTab = ref('devices');
const editingId = ref(null);
const selectedDeviceId = ref('');
const streamStatus = ref('idle');
const streamError = ref('');
const formError = ref('');
const packets = ref([]);
const source = ref(null);
const demoTimer = ref(null);
const form = reactive({
  external_id: '',
  name: '',
  metadata: '{\n  "status": "online"\n}',
});

const selectedDevice = computed(() => props.devices.find((item) => String(item.id) === String(selectedDeviceId.value)));

function resetForm() {
  editingId.value = null;
  form.external_id = '';
  form.name = '';
  form.metadata = '{\n  "status": "online"\n}';
}

function editDevice(device) {
  editingId.value = device.id;
  form.external_id = device.external_id || '';
  form.name = device.name || '';
  form.metadata = JSON.stringify(device.metadata || {}, null, 2);
}

function submitDevice() {
  let metadata = null;
  formError.value = '';

  if (form.metadata.trim() !== '') {
    try {
      metadata = JSON.parse(form.metadata);
    } catch {
      formError.value = 'Metadata должен быть валидным JSON.';
      return;
    }
  }

  const payload = {
    external_id: form.external_id,
    name: form.name || null,
    metadata,
  };

  emit(editingId.value ? 'update' : 'create', {
    id: editingId.value,
    payload,
  });
  resetForm();
}

function packetTime(packet) {
  return packet.ingested_at || new Date().toISOString();
}

function packetPayload(packet) {
  return packet.payload_json || packet.payload || '';
}

function stopDemo() {
  if (demoTimer.value) {
    window.clearInterval(demoTimer.value);
    demoTimer.value = null;
  }
}

function closeStream() {
  stopDemo();

  if (source.value) {
    source.value.close();
    source.value = null;
  }

  streamStatus.value = 'idle';
}

function pushDemoPacket() {
  const device = selectedDevice.value || props.devices[0] || { external_id: 'demo-device' };
  const temperature = (18 + Math.random() * 9).toFixed(2);
  const voltage = (3.1 + Math.random() * 0.7).toFixed(2);
  const rssi = Math.round(-82 + Math.random() * 30);
  const payload = {
    device_id: device.external_id,
    temperature: Number(temperature),
    voltage: Number(voltage),
    rssi,
  };

  packets.value = [{
    ingested_at: new Date().toISOString(),
    kafka_topic: 'mqtt.events',
    kafka_partition: Math.floor(Math.random() * 3),
    kafka_offset: Math.floor(Math.random() * 90000),
    mqtt_topic: `devices/${device.external_id}/telemetry`,
    device_identifier: device.external_id,
    payload_type: 'json',
    payload: JSON.stringify(payload),
    payload_json: JSON.stringify(payload),
    headers_json: '{"source":"demo-sniffer"}',
    demo: true,
  }, ...packets.value].slice(0, 100);
}

function startDemo() {
  closeStream();
  packets.value = [];
  streamError.value = '';
  streamStatus.value = 'demo';
  pushDemoPacket();
  demoTimer.value = window.setInterval(pushDemoPacket, 900);
}

async function openStream() {
  closeStream();
  packets.value = [];
  streamError.value = '';

  if (!selectedDevice.value) {
    return;
  }

  streamStatus.value = 'connecting';

  try {
    const response = await fetch(`${props.apiBaseUrl}/api/v1/devices/${selectedDevice.value.id}/stream`, {
      headers: {
        Accept: 'application/json',
        Authorization: props.authHeader,
      },
    });
    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'Stream request failed.');
    }

    const url = new URL(data.mercure_url);
    url.searchParams.append('topic', data.topic);
    source.value = new EventSource(url.toString());
    source.value.onopen = () => {
      streamStatus.value = 'live';
    };
    source.value.onerror = () => {
      streamStatus.value = 'reconnecting';
    };
    source.value.onmessage = (event) => {
      const packet = JSON.parse(event.data);
      packets.value = [packet, ...packets.value].slice(0, 100);
    };
  } catch (exception) {
    streamStatus.value = 'error';
    streamError.value = exception.message;
  }
}

watch(() => props.devices, () => {
  if ((!selectedDeviceId.value || !selectedDevice.value) && props.devices.length > 0) {
    selectedDeviceId.value = String(props.devices[0].id);
  }
}, { immediate: true });

onBeforeUnmount(closeStream);
</script>

<template>
  <section class="device-section">
    <div class="device-tabs" role="tablist">
      <button :class="{ active: activeTab === 'devices' }" type="button" @click="activeTab = 'devices'">
        Devices
      </button>
      <button :class="{ active: activeTab === 'packets' }" type="button" @click="activeTab = 'packets'">
        Live packets
      </button>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div v-if="activeTab === 'devices'" class="device-grid">
      <form class="device-form" @submit.prevent="submitDevice">
        <h2>{{ editingId ? 'Редактирование устройства' : 'Новое устройство' }}</h2>
        <div v-if="formError" class="alert alert-danger mb-0">{{ formError }}</div>
        <label>
          External ID
          <input v-model="form.external_id" class="form-control" required maxlength="255" />
        </label>
        <label>
          Название
          <input v-model="form.name" class="form-control" maxlength="255" />
        </label>
        <label>
          Metadata JSON
          <textarea v-model="form.metadata" class="form-control metadata-input" rows="6" />
        </label>
        <div class="actions-row">
          <button class="btn btn-primary" type="submit" :disabled="loading">
            {{ editingId ? 'Сохранить' : 'Создать' }}
          </button>
          <button class="btn btn-outline-secondary" type="button" :disabled="loading" @click="resetForm">
            Сбросить
          </button>
        </div>
      </form>

      <div class="table-block">
        <div class="table-title">
          <h3>Мои устройства</h3>
          <button class="btn btn-outline-secondary btn-sm" type="button" :disabled="loading" @click="$emit('refresh')">
            Обновить
          </button>
        </div>
        <div class="table-responsive">
          <table class="table catalog-table align-middle">
            <thead>
              <tr>
                <th>ID</th>
                <th>External ID</th>
                <th>Название</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="device in devices" :key="device.id">
                <td>{{ device.id }}</td>
                <td><code>{{ device.external_id }}</code></td>
                <td>{{ device.name || '-' }}</td>
                <td><span class="role-badge">{{ device.metadata?.status || 'unknown' }}</span></td>
                <td class="row-actions">
                  <button class="btn btn-outline-secondary btn-sm" type="button" @click="editDevice(device)">
                    Edit
                  </button>
                  <button class="btn btn-outline-danger btn-sm" type="button" @click="$emit('delete', device.id)">
                    Delete
                  </button>
                </td>
              </tr>
              <tr v-if="!devices.length">
                <td class="empty-cell" colspan="5">Нет устройств</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-else class="packet-panel">
      <div class="packet-toolbar">
        <select v-model="selectedDeviceId" class="form-select">
          <option value="" disabled>Выберите устройство</option>
          <option v-for="device in devices" :key="device.id" :value="device.id">
            {{ device.external_id }}
          </option>
        </select>
        <button class="btn btn-primary" type="button" :disabled="!selectedDevice" @click="openStream">
          Start
        </button>
        <button class="btn btn-outline-secondary" type="button" @click="closeStream">
          Stop
        </button>
        <button class="btn btn-outline-secondary" type="button" @click="startDemo">
          Demo
        </button>
        <span class="status-pill">{{ streamStatus }}</span>
      </div>

      <div v-if="streamError" class="alert alert-danger">{{ streamError }}</div>

      <div class="packet-sniffer">
        <table class="table catalog-table align-middle">
          <thead>
            <tr>
              <th>Time</th>
              <th>Topic</th>
              <th>Type</th>
              <th>Payload</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(packet, index) in packets" :key="`${packet.kafka_offset}-${index}`">
              <td>{{ packetTime(packet) }}</td>
              <td><code>{{ packet.mqtt_topic }}</code></td>
              <td>{{ packet.payload_type }}<span v-if="packet.demo" class="demo-mark">demo</span></td>
              <td><code>{{ packetPayload(packet) }}</code></td>
            </tr>
            <tr v-if="!packets.length">
              <td class="empty-cell" colspan="4">Пакетов пока нет</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>
