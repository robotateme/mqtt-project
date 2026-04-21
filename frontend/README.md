# Frontend

Vue 3 + Bootstrap 5 frontend для MQTT Project.

## Локальный деплой

Добавьте домены в `/etc/hosts`:

```text
127.0.0.1 api.mqtt.local
127.0.0.1 mqtt.local
```

Из корня проекта:

```bash
make up
make frontend-install
make frontend-build
```

Frontend обслуживается nginx из `laradock/nginx/sites/00-frontend.conf` на
`http://mqtt.local`. API доступен на `http://api.mqtt.local`.

## Разработка

```bash
npm install
npm run dev
npm run build
```
