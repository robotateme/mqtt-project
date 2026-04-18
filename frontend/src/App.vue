<script setup>
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://api.mqtt.local';
</script>

<template>
  <main class="app-shell">
    <nav class="navbar navbar-expand-lg bg-body border-bottom">
      <div class="container-fluid px-4">
        <span class="navbar-brand mb-0 h1">MQTT Project</span>
        <span class="badge text-bg-secondary">Vue 3 / Bootstrap 5</span>
      </div>
    </nav>

    <section class="container-fluid px-4 py-4">
      <div class="row g-4">
        <div class="col-12 col-xl-8">
          <div class="panel">
            <div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center mb-4">
              <div>
                <h1 class="h3 mb-1">Frontend service</h1>
                <p class="text-secondary mb-0">
                  Интерфейс обслуживается через nginx на mqtt.local.
                </p>
              </div>
              <button class="btn btn-primary" type="button">Создать устройство</button>
            </div>

            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead>
                  <tr>
                    <th>Компонент</th>
                    <th>Хост</th>
                    <th>Статус</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Frontend</td>
                    <td>mqtt.local</td>
                    <td><span class="badge text-bg-success">ready</span></td>
                  </tr>
                  <tr>
                    <td>API</td>
                    <td>{{ apiBaseUrl }}</td>
                    <td><span class="badge text-bg-info">hosted</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-4">
          <div class="panel h-100">
            <h2 class="h5 mb-3">Локальный деплой</h2>
            <p class="text-secondary mb-3">
              Соберите production-ассеты командой make frontend-build и перезапустите nginx.
            </p>
            <div class="d-grid gap-2">
              <a class="btn btn-outline-primary" :href="`${apiBaseUrl}/health`">API health</a>
              <a class="btn btn-outline-secondary" :href="`${apiBaseUrl}/ready`">API ready</a>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>
