# Режим производительности и оптимизации

## Описание
Настройки для улучшения производительности на больших базах:
- Уровни качества графики (Low, Medium, High, Ultra)
- Отключение анимаций
- Упрощение спрайтов (LOD)
- Ограничение FPS
- Показ статистики производительности (FPS, entities count)

## Оценка сложности
**Средняя (6/10)**

- Разные уровни детализации спрайтов
- Настройки PIXI.js (renderer options)
- Ограничение обновлений (throttling)
- UI для настроек
- Профилирование производительности

## Оценка интересности
**Высокая (8/10)**

Критично для масштабируемости игры. Позволяет играть на слабых устройствах. Увеличивает размер возможных баз.

## Краткий план реализации

1. **Уровни качества**:

   **Ultra** (по умолчанию):
   - Все анимации
   - Высокое разрешение спрайтов
   - Частицы и эффекты
   - Shadows и lighting
   - 60 FPS

   **High**:
   - Большинство анимаций
   - Нормальное разрешение
   - Некоторые эффекты
   - Простые тени
   - 60 FPS

   **Medium**:
   - Только важные анимации (конвейеры)
   - Упрощённые спрайты
   - Минимум эффектов
   - Без теней
   - 30 FPS

   **Low**:
   - Без анимаций
   - Очень упрощённые спрайты (цветные квадраты)
   - Без эффектов
   - Без теней
   - 30 FPS

2. **LOD (Level of Detail)**:
   - Далёкие entities (вне экрана или далеко):
     - Не обновлять анимации
     - Использовать упрощённые спрайты (1x1 px квадрат цвета)
     - Не рендерить совсем (если очень далеко)

   - Близкие entities:
     - Полная детализация
     - Все анимации

3. **Настройки графики**:
   - **Animations**: On / Off
   - **Particles**: On / Off
   - **Shadows**: On / Off
   - **Lighting**: On / Off (day-night cycle)
   - **FPS Limit**: 30 / 60 / 120 / Unlimited
   - **Render Distance**: Low (50 tiles) / Medium (100) / High (200)

4. **Culling**:
   - Не рендерить entities вне экрана
   - Проверка: entity в viewport?
   - PIXI.js: использовать culling плагины
   ```javascript
   container.cull = function(renderer) {
     // Culling logic
   };
   ```

5. **Throttling updates**:
   - На Low settings: обновлять только каждый второй кадр
   - Или обновлять разные группы entities по очереди:
     - Кадр 1: обновить entities[0-999]
     - Кадр 2: обновить entities[1000-1999]
     - etc.

6. **Упрощённые спрайты**:
   - Вместо PNG: цветные прямоугольники
   - Каждый entity_type = свой цвет
   - Assembler = синий квадрат
   - Furnace = красный квадрат
   - Conveyor = серая линия
   - Drill = коричневый квадрат

7. **UI настроек**:
   - Menu → Settings → Graphics
   - Presets: Ultra / High / Medium / Low (кнопки)
   - Advanced: индивидуальные настройки (sliders, checkboxes)
   - "Apply" → перезагрузить renderer

8. **FPS Counter**:
   - В углу экрана: "FPS: 60"
   - Цвет:
     - Зелёный: >= 50 FPS
     - Жёлтый: 30-49 FPS
     - Красный: < 30 FPS
   - Можно включить/выключить в настройках

9. **Статистика производительности**:
   - Показать в debug панели:
     - FPS
     - Entities count (всего)
     - Rendered entities (на экране)
     - Draw calls
     - Memory usage (MB)
   - Toggle: F3 (как в Minecraft)

10. **Оптимизация PIXI.js**:
    ```javascript
    const app = new PIXI.Application({
      antialias: settings.graphics === 'ultra',
      resolution: settings.graphics === 'ultra' ? 2 : 1,
      powerPreference: 'high-performance'
    });

    // Batch rendering
    PIXI.settings.SPRITE_MAX_TEXTURES = 16;

    // Culling
    renderer.plugins.interaction.autoPreventDefault = false;
    ```

11. **Предупреждение о производительности**:
    - Если FPS падает ниже 20: показать уведомление
    - "Низкая производительность. Уменьшите настройки графики?"
    - Кнопка: "Auto-optimize" → переключить на Medium/Low

12. **Отключение фоновых процессов**:
    - При табе не в фокусе: снизить FPS до 5
    - Или полностью остановить рендеринг (только backend логика)
    - Экономия ресурсов
