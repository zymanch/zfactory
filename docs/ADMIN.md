# Admin Panel - Документация

## Обзор

Admin Panel - инструмент администратора для управления регионами, пользователями и редактирования карты игрового мира. Функционал редактирования ландшафта и размещения депозитов вынесен из основной игры в отдельную админ-панель.

## Доступ

### Требования
- Авторизованный пользователь
- Флаг `is_admin = true` в таблице `user`

### URL
```
/admin/index        - Главная страница админки
/admin/edit-map?region_id=1  - Редактор карты региона
```

### Проверка прав
```php
// AdminController::behaviors()
'matchCallback' => function($rule, $action) {
    return \Yii::$app->user->identity->is_admin ?? false;
}
```

Если пользователь не админ - возвращается HTTP 403.

---

## Структура файлов

### Backend (PHP)

```
src/controllers/AdminController.php       - Контроллер с проверкой is_admin
src/actions/admin/
├── Index.php                             - Главная страница (регионы + юзеры)
├── Regions.php                           - JSON API: список регионов
├── Users.php                             - JSON API: список юзеров
├── EditMap.php                           - Страница редактора карты
├── UpdateLanding.php                     - AJAX: изменение/удаление landing
└── CreateDeposit.php                     - AJAX: создание депозита (без валидации)

src/views/layouts/admin.php               - Layout с navbar
src/views/admin/
├── index.php                             - Главная страница (2 таблицы)
└── edit-map.php                          - Редактор карты (fullscreen)
```

### Frontend (JavaScript)

```
resources/js/
├── admin.js                              - Главная страница админки
├── admin-map-editor.js                   - Редактор карты (extends ZFactoryGame)
└── modules/admin/
    ├── depositWindow.js                  - Окно выбора типа депозита
    └── depositBuildMode.js               - Режим размещения депозитов

resources/css/
├── admin.scss                            - Стили главной страницы
└── admin-map-editor.scss                 - Стили редактора карты
```

### Shared Modules (используются админкой)

```
resources/js/modules/
├── windows/landingWindow.js              - Окно выбора ландшафта
└── modes/landingEditMode.js              - Режим редактирования ландшафта
```

**ВАЖНО:** Эти модули удалены из основной игры, но используются в админ-редакторе!

---

## Главная страница (/admin/index)

### UI

Две колонки:
- **Слева**: Таблица регионов (ID, Name, Difficulty, Size, Actions)
- **Справа**: Таблица пользователей (ID, Username, Email, Admin, Region)

### Функционал

#### Регионы
- Фильтрация по имени (debounce 300ms)
- Кнопка "Edit Map" для каждого региона

#### Пользователи
- Фильтрация по username (debounce 300ms)
- Фильтр по is_admin (все / админы / не админы)

### API Endpoints

**GET /admin/regions?name=xxx**
```json
{
  "result": "ok",
  "regions": [
    {
      "region_id": 1,
      "name": "Floating Island",
      "difficulty": 1,
      "width": 250,
      "height": 250
    }
  ]
}
```

**GET /admin/users?username=xxx&is_admin=1**
```json
{
  "result": "ok",
  "users": [
    {
      "user_id": 1,
      "username": "admin",
      "email": "admin@example.com",
      "is_admin": true,
      "current_region_id": 1,
      "created_at": "2025-01-01 12:00:00"
    }
  ]
}
```

---

## Редактор карты (/admin/edit-map)

### Архитектура

```javascript
class AdminMapEditor extends ZFactoryGame {
    constructor() {
        super('/game/config');
        this.regionId = window.REGION_ID;
        this.regionName = window.REGION_NAME;
    }

    initAdminModules() {
        // Landing editing (removed from main game)
        this.landingWindow = new LandingWindow(this);
        this.landingEditMode = new LandingEditMode(this);

        // Deposit placement (admin-only)
        this.depositWindow = new DepositWindow(this);
        this.depositBuildMode = new DepositBuildMode(this);

        // Override URLs for admin endpoints
        this.config.updateLandingUrl = '/admin/update-landing';
        this.config.createDepositUrl = '/admin/create-deposit';
    }
}
```

### UI Elements

```
┌─────────────────────────────────────┐
│ [X: 123, Y: 45]   [← Back to Admin] │  ← Фиксированный header
│                                     │
│                                     │
│         Fullscreen Game Canvas      │
│                                     │
│                                     │
└─────────────────────────────────────┘
```

- **Sprite Coords** (top-left): Координаты тайла под курсором
- **Back Button** (top-right): Возврат на главную страницу админки

### Хоткеи

| Кнопка | Действие |
|--------|----------|
| `L` / `Д` | Открыть окно выбора ландшафта |
| `R` / `К` | Открыть окно выбора депозита (Resources) |
| `Click` | Разместить выбранный ландшафт/депозит |
| `Esc` | Отмена / закрыть окно |

**Примечание**: Клавиша для депозитов была изменена с `D` на `R` (2026-01), чтобы не конфликтовать с клавишей движения камеры направо.

---

## Landing Editor (Редактирование ландшафта)

### Workflow

1. Нажать `L` → открывается LandingWindow
2. Выбрать тип ландшафта (grass, dirt, sand, etc.)
3. Режим переключается в `LANDING_EDIT`
4. Клик по карте → ландшафт меняется
5. `Esc` → выход из режима редактирования

### API

**POST /admin/update-landing**
```json
{
  "region_id": 1,
  "changes": [
    { "x": 10, "y": 20, "landing_id": 1 },    // Создать/изменить
    { "x": 11, "y": 20, "landing_id": null }  // Удалить
  ]
}
```

**Response:**
```json
{
  "result": "ok",
  "updated": [
    { "x": 10, "y": 20, "landing_id": 1 }
  ],
  "deleted": [
    { "x": 11, "y": 20 }
  ]
}
```

### Features

- **Adjacency Resolution**: Автоматическое обновление соседних тайлов (из landingEditMode)
- **Island Edge**: Автогенерация под краями острова
- **Sky**: Удаление тайлов (landing_id = 9)

---

## Deposit Builder (Размещение депозитов)

### Workflow

1. Нажать `R` → открывается DepositWindow
2. Выбрать категорию: **Trees** / **Rocks** / **Ores**
3. Выбрать тип депозита (oak, stone, iron_ore, etc.)
4. Настроить диапазон ресурсов:
   - **Min**: 0-1000 (default: 10 для trees/rocks, 50 для ores)
   - **Max**: 0-1000 (default: 30 для trees/rocks, 100 для ores)
5. Клик по карте → депозит создается с рандомным amount в диапазоне

### UI: DepositWindow

```
┌─────────────────────────────────────┐
│  Select Deposit Type            [×] │
├─────────────────────────────────────┤
│ [Trees]  [Rocks]  [Ores]            │ ← Tabs
├─────────────────────────────────────┤
│  [🌳 Oak]    [🌲 Pine]   [🌴 Palm]  │
│  [🌿 Bush]   [🌾 Grass]              │ ← Grid 3×N
├─────────────────────────────────────┤
│  Resource Amount Range:             │
│  10 - 30                            │
│  Min: [━━━━━○━━━━] 10               │
│  Max: [━━━━━━━━○━] 30               │
└─────────────────────────────────────┘
```

### Preview Sprite

- **Полупрозрачный спрайт** (alpha = 0.5) следует за курсором
- **Зеленый цвет** (0x00ff00): можно разместить (есть landing)
- **Красный цвет** (0xff0000): нельзя разместить (нет landing)

### API

**POST /admin/create-deposit**
```json
{
  "region_id": 1,
  "deposit_type_id": 5,
  "x": 100,
  "y": 150,
  "resource_amount": 25
}
```

**Response:**
```json
{
  "result": "ok",
  "deposit": {
    "deposit_id": 123,
    "deposit_type_id": 5,
    "x": 100,
    "y": 150,
    "resource_amount": 25
  }
}
```

### Особенности

- **Без валидации**: Депозит создается без проверок (как указано в требованиях)
- **Рандомный amount**: Генерируется в диапазоне [minAmount, maxAmount]
- **Только на landing**: Можно разместить только на тайлах с ландшафтом

### Исправления (2026-01)

**Recursion Fix**: Исправлена бесконечная рекурсия в `depositWindow.close()`:
- Проблема: `selectDeposit()` → `close()` → `returnToNormalMode()` → `deactivateDepositSelectionWindow()` → `close()` снова
- Решение:
  - `selectDeposit()` больше не вызывает `close()` напрямую
  - `close()` проверяет `if (!this.isOpen) return;`
  - `close()` вызывает `returnToNormalMode()` только если режим `DEPOSIT_SELECTION_WINDOW`

---

## GameModeManager Integration

### Новые режимы

```javascript
export const GameMode = {
    // ... existing modes
    DEPOSIT_SELECTION_WINDOW: 'DEPOSIT_SELECTION_WINDOW',
    DEPOSIT_BUILD: 'DEPOSIT_BUILD'
};
```

### Переключение режимов

```javascript
// Открыть окно выбора депозита
game.gameModeManager.switchMode(GameMode.DEPOSIT_SELECTION_WINDOW);

// Войти в режим размещения
game.gameModeManager.switchMode(GameMode.DEPOSIT_BUILD, {
    depositType: depositTypeObject,
    minAmount: 10,
    maxAmount: 30
});

// Выход в нормальный режим
game.gameModeManager.returnToNormalMode();
```

---

## Миграция БД

### Создание is_admin

**Файл:** `src/migrations/m260102_000000_add_is_admin_to_user.php`

```php
public function safeUp()
{
    $this->addColumn('{{%user}}', 'is_admin',
        $this->boolean()->notNull()->defaultValue(false)->after('email'));

    // Установить первого пользователя как админа
    $this->update('{{%user}}', ['is_admin' => true], ['user_id' => 1]);
}

public function safeDown()
{
    $this->dropColumn('{{%user}}', 'is_admin');
}
```

### Применение

```bash
php yii migrate
```

---

## Webpack Build

### Конфигурация

**webpack.mix.js:**
```javascript
mix
    .js('resources/js/admin.js', 'public/js')
    .js('resources/js/admin-map-editor.js', 'public/js')
    .sass('resources/css/admin.scss', 'public/css')
    .sass('resources/css/admin-map-editor.scss', 'public/css');
```

### Компиляция

```bash
npm run assets
```

**Результат:**
```
✔ Compiled Successfully
┌───────────────────────────────────┬───────────┐
│ /public/js/admin-map-editor.js    │ 523 KiB   │
│ /public/js/admin.js               │ 6.17 KiB  │
│ /public/css/admin-map-editor.css  │ ...       │
│ /public/css/admin.css             │ ...       │
└───────────────────────────────────┴───────────┘
```

---

## Что удалено из основной игры

### JavaScript

- ❌ Импорты LandingWindow, LandingEditMode из `game.js`
- ❌ Инициализация landingWindow, landingEditMode в `game.js`
- ❌ Метод `hasLandingAdjacency()` из `game.js`
- ❌ Загрузка `landingAdjacencies` из config
- ❌ Hotkey `L`/`Д` из `inputManager.js`
- ❌ Подсказки по landing из `ControlsHint.js`

### CSS

- ❌ Стили `#landing-window` из `game.scss`
- ❌ Стили `#landing-status` из `game.scss`

### PHP

- ❌ Action `update-landing` из `MapController.php`
- ❌ URL `updateLandingUrl` из `Config.php`
- ❌ Файл `src/actions/map/UpdateLanding.php` (перенесен в `src/actions/admin/`)

### ⚠️ Что НЕ удалено

- ✅ `landingTypes` - нужны для рендеринга карты
- ✅ `resources/js/modules/windows/landingWindow.js` - используется в админке
- ✅ `resources/js/modules/modes/landingEditMode.js` - используется в админке

---

## Troubleshooting

### База данных не подключена

**Проблема:**
```bash
$ php yii migrate
SQLSTATE[HY000] [2002] Подключение не установлено
```

**Решение:**
- Запустить MySQL/PostgreSQL сервер
- Проверить настройки `config/db.php`

### Webpack ошибка: Module not found

**Проблема:**
```
ERROR in ./resources/js/admin-map-editor.js
Module not found: Error: Can't resolve './modules/windows/landingWindow.js'
```

**Причина:**
Файлы landingWindow.js и landingEditMode.js были удалены.

**Решение:**
```bash
git checkout HEAD -- resources/js/modules/windows/landingWindow.js
git checkout HEAD -- resources/js/modules/modes/landingEditMode.js
```

### 403 Forbidden при доступе к /admin

**Проблема:**
Пользователь не имеет прав админа.

**Решение:**
```sql
UPDATE user SET is_admin = 1 WHERE user_id = 1;
```

---

## Roadmap

### Текущая версия (1.0)
- ✅ Управление регионами (просмотр, фильтрация)
- ✅ Управление пользователями (просмотр, фильтрация)
- ✅ Редактор ландшафта (landing)
- ✅ Размещение депозитов (deposits)

### Планы на будущее (2.0)
- [ ] CRUD операции для регионов
- [ ] Редактирование пользователей (email, is_admin)
- [ ] Размещение entity на карте
- [ ] Управление рецептами и ресурсами
- [ ] Логи действий администраторов
- [ ] Экспорт/импорт карты (JSON)

---

## См. также

- [PROJECT.md](PROJECT.md) - Общее описание проекта
- [DATABASE.md](DATABASE.md) - Схема базы данных
- [GAME_ENGINE.md](GAME_ENGINE.md) - Игровой движок (PixiJS)
