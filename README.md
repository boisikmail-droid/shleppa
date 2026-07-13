# Шляпа — игра

Проект состоит из трёх частей:

- `back/` — Symfony 7 API + MySQL
- `front/` — Vue 3 + Pinia + Vite (+ VK Bridge для мини-приложения)
- `docker/` — Docker Compose для запуска всего стека

Режим игры: **party на одном телефоне** (имена вводятся вручную). ВКонтакте — оболочка Mini App; онлайн-мультиплеер не нужен.

## Быстрый старт (Docker)

```bash
cd docker
docker compose up --build
```

После запуска:

| Сервис   | URL                    |
|----------|------------------------|
| Frontend | http://localhost:5173  |
| Backend  | http://localhost:8080  |
| MySQL    | localhost:3306         |

При первом запуске автоматически выполняются миграции и импорт 500 слов.

Обычный браузер: открывай `http://localhost:5173` — игра работает без ВК (Bridge тихо пропускается).

## Отладка как VK Mini App (локальный Docker + туннель)

1. Подними стек:

```bash
cd docker
docker compose up --build
```

2. Создай мини-приложение на [dev.vk.com](https://dev.vk.com/).

3. На **хосте Windows** (не внутри `docker exec` / контейнера `hat_frontend`) пробрось HTTPS к порту `5173`.

Нужен [cloudflared](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/) (без аккаунта Cloudflare). В проекте уже может быть `front/bin/cloudflared.exe`:

```powershell
cd C:\vibe\hat\front
npm run tunnel
```

В консоли появится URL вида `https://….trycloudflare.com` — его и вставляй в настройки ВК.

Не используй `localtunnel` для ВК: у него страница «введите IP», которая ломает iframe мини-приложения.  
Официальный `npm run vk-tunnel` сейчас часто недоступен.

4. Вставь полученный HTTPS-URL в настройки приложения ВК (URL приложения).
5. Открой приложение из ВК — iframe загрузит твой локальный Docker.

API идёт на тот же origin (`/api`) и проксируется Vite → nginx в Docker.

В лобби из профиля ВК подставляется только имя текущего пользователя в первую команду; остальные игроки — вручную.

## Ручной запуск команд в backend

```bash
docker exec -it hat_php php bin/console doctrine:migrations:migrate
docker exec -it hat_php php bin/console app:import-words
```

## API

Базовый URL: `http://localhost:8080/api` (из фронта — относительный `/api`)

- `POST /session/start` — создать игру
- `GET /session/{id}/state` — состояние игры
- `GET /game/next-word` — следующее слово
- `POST /game/turn/start` — начать ход
- `POST /game/action` — угадано / пропуск
- `POST /game/turn/finish` — завершить ход с коррекцией
- `POST /round/next` — принудительный переход раунда

## Переменные окружения

См. `back/.env` и `docker/docker-compose.yml`.
