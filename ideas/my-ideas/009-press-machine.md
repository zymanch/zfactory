# Пресс (Press Machine)

## Описание
Новый entity_type "Пресс" для производства промежуточных компонентов из слитков: пруты (rods), листы (plates), винты (screws), болты (bolts), провода (wires) и т.д. Эти рецепты убрать из других entity (assembler, furnace).

## Оценка сложности
**Низкая (4/10)**

- Новый entity_type (стандартная процедура)
- Создание спрайтов
- Перенос рецептов
- Обновление balance

## Оценка интересности
**Средняя (6/10)**

Добавляет специализацию зданий, усложняет производственные цепочки. Делает планирование базы более интересным.

## Краткий план реализации

1. **База данных**:
   - Новый entity_type "Пресс"
     - name: "Press"
     - sprite_folder: "press"
     - max_inventory: 20
     - size: 2x2
     - requires_power: true
     - power_consumption: 20

2. **Новые ресурсы**:
   - Прут (Rod): из 1 слитка → 2 прута
   - Лист (Plate): из 2 слитков → 1 лист
   - Провод (Wire): из 1 медного слитка → 4 провода
   - Винт (Screw): из 1 прута → 4 винта
   - Болт (Bolt): из 1 прута + 1 винт → 2 болта

3. **Рецепты для пресса**:
   ```sql
   -- Copper Rod
   INSERT INTO recipe (name, entity_type_id, crafting_time)
   VALUES ('Copper Rod', <press_id>, 2);

   INSERT INTO recipe_input (recipe_id, resource_id, quantity)
   VALUES (<recipe_id>, <copper_ingot_id>, 1);

   INSERT INTO recipe_output (recipe_id, resource_id, quantity)
   VALUES (<recipe_id>, <copper_rod_id>, 2);
   ```

4. **Обновление рецептов других зданий**:
   - Assembler: теперь использует прутья/листы вместо слитков
   - Пример: "Gear" = 2 Iron Rods → 1 Gear (было 2 Iron Ingots)

5. **Спрайты**:
   - Нарисовать пресс (hydraulic press, механический пресс)
   - 5 состояний: normal, damaged, blueprint, normal_selected, damaged_selected
   - Возможно анимация работы (поршень вверх-вниз)

6. **Балансировка**:
   - Скорость крафта: медленнее чем furnace, быстрее чем assembler
   - Входные слоты: 2
   - Выходные слоты: 4
   - Стоимость постройки: 10 Iron Ingots, 5 Copper Ingots, 20 Stone

7. **Визуальная анимация** (опционально):
   - Анимация опускания пресса при крафте
   - Частицы при производстве
