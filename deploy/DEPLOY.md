# Деплой на VPS

## 1. SSH без пароля (с твоего компьютера)

### На Windows (PowerShell)

```powershell
# если ключа ещё нет
ssh-keygen -t ed25519 -C "viktor@local"

# скопировать публичный ключ на сервер (замени user и IP)
type $env:USERPROFILE\.ssh\id_ed25519.pub | ssh user@YOUR_SERVER_IP "mkdir -p ~/.ssh && chmod 700 ~/.ssh && cat >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys"
```

Проверка:

```powershell
ssh user@YOUR_SERVER_IP
```

Должен пускать без пароля.

### На сервере (если ключ добавляешь вручную)

```bash
mkdir -p ~/.ssh
chmod 700 ~/.ssh
nano ~/.ssh/authorized_keys   # вставь содержимое id_ed25519.pub с локальной машины
chmod 600 ~/.ssh/authorized_keys
```

Опционально отключить вход по паролю (только после проверки ключа!):

```bash
sudo nano /etc/ssh/sshd_config
# PasswordAuthentication no
sudo systemctl restart sshd
```

---

## 2. Подготовка сервера (один раз)

```bash
# Docker
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
# перелогинься или: newgrp docker

# Git
sudo apt update && sudo apt install -y git

# Клонировать репозиторий (deploy key с сервера должен быть в GitHub → Settings → Deploy keys)
sudo mkdir -p /opt/hat
sudo chown $USER:$USER /opt/hat
git clone git@github.com:YOUR_USER/YOUR_REPO.git /opt/hat

# env для продакшена (пароли поменяй!)
cat > /opt/hat/docker/.env << 'EOF'
MYSQL_ROOT_PASSWORD=сложный_пароль_root
MYSQL_PASSWORD=сложный_пароль_user
HTTP_PORT=80
CORS_ALLOW_ORIGIN=*
EOF

chmod +x /opt/hat/deploy/deploy.sh

# Первый запуск
cd /opt/hat
docker compose -f docker/docker-compose.prod.yml up --build -d
```

### Firewall — открыть HTTP

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw enable
```

Фронтенд будет на `http://IP_СЕРВЕРА/`.

---

## 3. Автодеплой при push в main

### Ключ для GitHub Actions → сервер

На сервере (или локально) создай **отдельную** пару для деплоя:

```bash
ssh-keygen -t ed25519 -f deploy_key -N ""
```

- `deploy_key.pub` → в `~/.ssh/authorized_keys` на сервере
- `deploy_key` (приватный) → секрет в GitHub

### Секреты репозитория (GitHub → Settings → Secrets → Actions)

| Секрет | Значение |
|--------|----------|
| `SERVER_HOST` | IP или домен сервера |
| `SERVER_USER` | пользователь SSH (например `ubuntu`) |
| `DEPLOY_SSH_KEY` | содержимое приватного `deploy_key` |
| `SERVER_PORT` | `22` (если нестандартный — укажи свой) |

После push в `main` workflow `.github/workflows/deploy.yml` зайдёт по SSH и выполнит `deploy/deploy.sh`.

---

## 4. Домен и HTTPS (опционально)

Для продакшена лучше поставить Caddy или nginx на хосте + Let's Encrypt:

```bash
sudo apt install -y caddy
```

`/etc/caddy/Caddyfile`:

```
your-domain.com {
    reverse_proxy localhost:80
}
```

Тогда в `docker/.env` можно оставить `HTTP_PORT=80`, а снаружи будет HTTPS.

---

## Поведение деплоя

`deploy/deploy.sh` при каждом деплое:

1. тянет `main` и пересобирает контейнеры
2. **полностью очищает таблицы БД**
3. накатывает миграции заново
4. заливает свежий словарь (`app:import-words`)
5. прогревает кеш и проверяет `POST /api/session/start`

Старые игры на сервере после деплоя не сохраняются.

## Полезные команды

```bash
docker compose -f docker/docker-compose.prod.yml logs -f
docker compose -f docker/docker-compose.prod.yml ps
bash deploy/deploy.sh   # ручной деплой на сервере
```
