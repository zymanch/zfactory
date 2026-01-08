# Дерево технологий

## Описание
Система исследований (technology tree). Технологии разблокируют:
- Другие технологии (зависимости)
- Рецепты
- Новые типы зданий (entity_type)

Исследование происходит через HQ (главное здание). Клик на HQ открывает окно исследований.
Изучение моментальное - нужны научные пакеты (Science Packs).

## Оценка сложности
**Средняя (6/10)**

- Таблицы для технологий и связей
- UI для дерева технологий (граф, визуализация)
- Логика разблокировки
- Научные пакеты как крафтящиеся ресурсы

## Оценка интересности
**Очень высокая (10/10)**

Даёт чувство прогресса, открытия нового. Структурирует игровой процесс. Мотивирует к развитию производства.

## Краткий план реализации

### 1. Научные пакеты (Science Packs)

Научные пакеты - это обычные ресурсы, которые крафтятся в зданиях.
Добавляются в таблицу `resource` с типом "science".

| Пакет | Рецепт | Tier технологий |
|-------|--------|-----------------|
| Red Science | 1 Iron Gear + 1 Copper Wire | Tier 1 |
| Green Science | 1 Circuit + 1 Manipulator | Tier 2 |
| Blue Science | 2 Steel + 1 Advanced Circuit | Tier 3 |
| Purple Science | 3 Steel + 2 Advanced Circuit + 1 Engine | Tier 4 |

Крафтятся в обычных Assembler'ах (или специальном Science Assembler).

### 2. База данных

**Таблица `technology`:**
| Поле | Тип | Описание |
|------|-----|----------|
| id | int | PK |
| name | string | Название |
| description | text | Описание |
| icon | string | Путь к иконке |
| tier | int | Уровень (для визуализации) |

**Таблица `technology_dependency`:**
| Поле | Тип | Описание |
|------|-----|----------|
| technology_id | int | FK → technology |
| required_technology_id | int | FK → technology (зависимость) |

**Таблица `technology_cost`:**
| Поле | Тип | Описание |
|------|-----|----------|
| technology_id | int | FK → technology |
| resource_id | int | FK → resource (научный пакет) |
| quantity | int | Количество |

**Таблица `technology_unlock_recipe`:**
| Поле | Тип | Описание |
|------|-----|----------|
| technology_id | int | FK → technology |
| recipe_id | int | FK → recipe |

**Таблица `technology_unlock_entity_type`:**
| Поле | Тип | Описание |
|------|-----|----------|
| technology_id | int | FK → technology |
| entity_type_id | int | FK → entity_type |

**Таблица `user_technology`:**
| Поле | Тип | Описание |
|------|-----|----------|
| user_id | int | FK → user |
| technology_id | int | FK → technology |
| researched_at | datetime | Когда изучено |

### 3. HQ (Главное здание)

- Уже существующий entity_type "hq" (или создать новый)
- При клике на HQ → открывается TechnologyWindow
- HQ должен быть уникальным на карте (1 на игрока)

### 4. Примеры технологий

**Tier 1 (Red Science):**
- **Автоматизация**: 10 Red → разблокирует Conveyor, Manipulator
- **Обработка камня**: 10 Red → разблокирует Stone Furnace

**Tier 2 (Green Science):**
- **Логистика** (требует: Автоматизация): 20 Green → Fast Conveyor
- **Металлургия** (требует: Обработка камня): 20 Green → Iron Furnace

**Tier 3 (Blue Science):**
- **Продвинутая логистика** (требует: Логистика): 30 Blue → Express Conveyor
- **Сталь** (требует: Металлургия): 30 Blue → рецепт Steel Ingot

**Tier 4 (Purple Science):**
- **Электричество** (требует: Сталь): 50 Purple → генераторы, столбы
- **Военное дело** (требует: Сталь): 50 Purple → башни

### 5. Frontend - TechnologyWindow

**Файл:** `resources/js/modules/windows/TechnologyWindow.js`

**Открытие:**
- Клик на HQ → проверка entity_type === 'hq' → открыть окно
- Или добавить в EntityInfoWindow кнопку "Research" для HQ

**UI элементы:**
- Граф технологий (canvas или DOM)
- Узлы технологий с иконками
- Линии связей между узлами
- Статусы узлов:
  - Серый = не доступна (нет зависимостей)
  - Жёлтый = доступна для изучения
  - Синий = изучена
- При наведении: tooltip с описанием и стоимостью (научные пакеты)
- При клике: кнопка "Изучить" (если доступна и хватает пакетов)

**Расположение узлов:**
- Tier 1 слева, Tier 4 справа
- Или сверху вниз по уровням

### 6. Backend API

**`GET /research/tree`**
Возвращает все технологии + статус для текущего пользователя:
```json
{
  "technologies": [
    {
      "id": 1,
      "name": "Автоматизация",
      "description": "...",
      "icon": "automation.svg",
      "tier": 1,
      "cost": [
        {"resource_id": 101, "name": "Red Science", "icon": "red_science.svg", "quantity": 10}
      ],
      "requires": [],
      "unlocks": {
        "recipes": [1, 2],
        "entity_types": [5, 6]
      },
      "status": "available"
    }
  ],
  "sciencePacks": [
    {"id": 101, "name": "Red Science", "icon": "red_science.svg", "userQuantity": 25},
    {"id": 102, "name": "Green Science", "icon": "green_science.svg", "userQuantity": 0}
  ]
}
```

**`POST /research/unlock`**
Изучить технологию:
```json
{"technology_id": 1}
```
Ответ:
```json
{
  "success": true,
  "unlocked": {
    "recipes": [...],
    "entity_types": [...]
  },
  "resourcesSpent": [
    {"resource_id": 101, "quantity": 10}
  ]
}
```

### 7. Процесс изучения

1. Игрок производит научные пакеты (крафт в Assembler)
2. Пакеты складируются в HQ или на складе
3. Игрок кликает на HQ → TechnologyWindow
4. Видит дерево, доступные технологии подсвечены
5. Кликает на технологию → видит стоимость в пакетах
6. Нажимает "Изучить":
   - Сервер проверяет зависимости
   - Сервер проверяет наличие пакетов
   - Списывает пакеты
   - Добавляет запись в user_technology
7. UI обновляется моментально

### 8. Интеграция с существующим кодом

**Новые ресурсы (resource):**
- Red Science Pack
- Green Science Pack
- Blue Science Pack
- Purple Science Pack

**Новые рецепты (recipe):**
- Крафт каждого научного пакета

**BuildingWindow:**
- Фильтровать entity_types по изученным технологиям
- Добавить поле `required_technology_id` в entity_type

**Recipe система:**
- Фильтровать рецепты по изученным технологиям
- Добавить поле `required_technology_id` в recipe

**EntityInfoWindow:**
- Для HQ: добавить кнопку "Research" или сразу открывать TechnologyWindow

### 9. Структура дерева (пример)

```
[Автоматизация]──────────────────┐
   (10 Red)                      │
      │                          ▼
      ▼                   [Обработка камня]
[Логистика]                  (10 Red)
 (20 Green)                      │
      │                          ▼
      ▼                   [Металлургия]
[Быстрая логистика]          (20 Green)
   (30 Blue)                     │
                                 ▼
                              [Сталь]
                             (30 Blue)
                                 │
                    ┌────────────┴────────────┐
                    ▼                         ▼
             [Электричество]           [Военное дело]
              (50 Purple)               (50 Purple)
```

### 10. Балансировка стоимости

| Tier | Пакет | Стоимость | Сложность крафта пакета |
|------|-------|-----------|------------------------|
| 1 | Red | 10-20 шт | Простой (базовые ресурсы) |
| 2 | Green | 20-40 шт | Средний (обработанные) |
| 3 | Blue | 30-60 шт | Сложный (сталь, схемы) |
| 4 | Purple | 50-100 шт | Очень сложный (двигатели) |

### 11. Файлы для создания

```
# Миграции
src/migrations/m_create_technology_tables.php
src/migrations/m_add_science_pack_resources.php
src/migrations/m_add_science_pack_recipes.php

# Модели
src/models/Technology.php
src/models/TechnologyDependency.php
src/models/TechnologyCost.php
src/models/TechnologyUnlockRecipe.php
src/models/TechnologyUnlockEntityType.php
src/models/UserTechnology.php

# API
src/controllers/ResearchController.php
src/actions/research/Tree.php
src/actions/research/Unlock.php

# Frontend
resources/js/modules/windows/TechnologyWindow.js
resources/css/technology.css

# Иконки
public/assets/tiles/resources/red_science.svg
public/assets/tiles/resources/green_science.svg
public/assets/tiles/resources/blue_science.svg
public/assets/tiles/resources/purple_science.svg
public/assets/tiles/technologies/*.svg
```
