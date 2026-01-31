# Настройка GitHub Token для приватного репозитория

Если ваш репозиторий **приватный**, нужно создать Personal Access Token для доступа к GitHub API.

## Шаг 1: Создать GitHub Token

1. Зайдите на GitHub: https://github.com/settings/tokens
2. Нажмите **"Generate new token"** → **"Generate new token (classic)"**
3. Настройки токена:
   - **Note**: `WordPress Auto-Updates`
   - **Expiration**: `No expiration` (или выберите срок)
   - **Scopes**: Отметьте только `repo` (Full control of private repositories)
4. Нажмите **"Generate token"**
5. **Скопируйте токен** (он показывается только один раз!)

## Шаг 2: Добавить токен в WordPress

### Вариант А: Через wp-config.php (рекомендуется)

Добавьте в `wp-config.php` на продакшн-сайте:

```php
define('TGCB_GITHUB_TOKEN', 'ghp_ваш_токен_здесь');
```

### Вариант Б: Через админку WordPress

Можно добавить настройку в админке плагина для ввода токена.

## Шаг 3: Обновить код плагина

Код уже готов использовать токен из `wp-config.php` - просто добавьте константу на продакшене.

---

## Альтернатива: Сделать репозиторий публичным

Если код не содержит секретных данных, проще всего сделать репозиторий публичным:

1. GitHub → Repository Settings
2. Danger Zone → Change visibility → Make public

Тогда токен не нужен!
