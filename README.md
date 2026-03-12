# Proxy Manager

Тестовое задание для Advert Technology Solutions
Приложение для управления списком прокси-серверов.

## Стек

- **Backend**: PHP 8.3, Laravel 12
- **Frontend**: Vue 3 (Composition API) + Vite + Pinia
- **БД**: MySQL 8.0
- **Очередь**: Redis
- **Контейнеризация**: Docker + Docker Compose

## Быстрый старт

```bash
# Запустить все контейнеры
docker compose up -d --build

# Выполнить миграции
docker compose exec php php artisan migrate

# (Опционально) заполнить тестовыми данными
docker compose exec php php artisan db:seed
```

### Доступные адреса

| Сервис   | URL                        |
|----------|----------------------------|
| Frontend | http://localhost:5173      |
| API      | http://localhost:8080/api/v1 |
| MySQL    | localhost:3306             |
| Redis    | localhost:6379             |

## API эндпоинты

| Метод  | URL                              | Описание                    |
|--------|----------------------------------|-----------------------------|
| GET    | `/api/v1/proxies`                | Список прокси (пагинация)   |
| POST   | `/api/v1/proxies`                | Создать прокси              |
| GET    | `/api/v1/proxies/{id}`           | Получить прокси             |
| PUT    | `/api/v1/proxies/{id}`           | Обновить прокси             |
| DELETE | `/api/v1/proxies/{id}`           | Удалить прокси              |
| POST   | `/api/v1/proxies/{id}/check`     | Ручная проверка статуса     |
| POST   | `/api/v1/proxies/check-all`      | Проверить все прокси        |

### Параметры фильтрации (GET /proxies)

- `status` — фильтр по статусу: `active`, `inactive`, `checking`
- `search` — поиск по имени и хосту
- `page`, `per_page` — пагинация

## Архитектура

```
ProxyManager/
├── backend/                    # Laravel 12
│   ├── app/
│   │   ├── Http/Controllers/Api/ProxyController.php
│   │   ├── Http/Requests/      # Валидация
│   │   ├── Http/Resources/     # API ресурсы
│   │   ├── Jobs/CheckProxyStatus.php
│   │   ├── Models/Proxy.php
│   │   └── Services/ProxyCheckerService.php
│   ├── database/migrations/
│   └── routes/api.php
├── frontend/                   # Vue 3
│   └── src/
│       ├── api/proxy.js
│       ├── components/
│       │   ├── ProxyTable.vue
│       │   ├── ProxyForm.vue
│       │   └── ProxyStatusBadge.vue
│       ├── stores/proxy.js     # Pinia store
│       └── views/ProxiesView.vue
├── docker/
│   ├── nginx/default.conf
│   └── php/Dockerfile
└── docker-compose.yml
```

## Как работает автопроверка

1. **Каждые 5 минут** Laravel Scheduler запускает задачу, которая ставит в очередь `CheckProxyStatus` job для каждого прокси
2. **Worker** (`queue:work`) обрабатывает задачи из очереди Redis
3. `ProxyCheckerService` подключается к целевому URL (`https://api.ipify.org`) через прокси с помощью cURL, измеряет время отклика
4. Результат (`active` / `inactive`) и время отклика сохраняются в БД
5. **Фронтенд** и API общаются через Laravel Reverb

## Управление контейнерами

```bash
# Остановить
docker compose down

# Просмотр логов
docker compose logs -f

# Логи конкретного сервиса
docker compose logs -f worker

# Пересборка после изменений
docker compose up -d --build
```

