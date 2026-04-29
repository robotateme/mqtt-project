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

## Авторизация и admin-таблицы

Frontend хранит JWT-сессию в `localStorage` и работает с API
`/api/v1/auth/*`. После входа пользователя с ролью `admin` дополнительно
загружаются таблицы:

- `GET /api/v1/admin/users`
- `GET /api/v1/admin/devices`

Для локальной проверки можно заполнить демо-данные:

```bash
make core-fresh-seed
```

Admin-логин: `admin@example.com` / `password123`.

## Устройства и live-пакеты

Для любого авторизованного пользователя доступна вкладка устройств:

- `Devices` - таблица своих устройств и CRUD-форма.
- `Live packets` - sniffer-таблица пакетов выбранного устройства через Mercure
  `EventSource`.

Пока реальный поток не подключен, кнопка `Demo` запускает временную анимацию
sniffer-лотка с синтетическими MQTT-пакетами.

В верхней панели доступно переключение Bootstrap-тем: default,
`empire-night`, `republic-day`, `tron-neon-night`,
`cellular-automata-day`, `cellular-automata-night`.

## Разработка

```bash
npm install
npm run dev
npm run build
```
