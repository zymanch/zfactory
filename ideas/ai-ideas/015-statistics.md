# Статистика и графики

## Описание
Детальная статистика производства, потребления, энергии:
- Графики производства ресурсов (units/min)
- Графики потребления электричества
- История за 1 час / 10 часов / 50 часов / всё время
- Сравнение производства vs потребления
- Выявление bottlenecks (узких мест)

## Оценка сложности
**Высокая (8/10)**

- Сбор данных (каждый ресурс, каждую минуту)
- Хранение исторических данных (БД или агрегация)
- Рендеринг графиков (Chart.js или custom)
- UI с фильтрами и временными диапазонами
- Оптимизация (не хранить ВСЁ навечно)

## Оценка интересности
**Очень высокая (9/10)**

Критически важно для оптимизации больших фабрик. Помогает найти проблемы. Удовлетворяет любителей data-driven подхода.

## Краткий план реализации

1. **Сбор данных**:
   - Каждую минуту (или каждые 10 секунд):
     - Подсчитать production каждого ресурса
     - Подсчитать consumption каждого ресурса
     - Подсчитать generation электричества
     - Подсчитать consumption электричества
   - Сохранить snapshot

2. **База данных**:
   - Таблица `statistics_production`:
     - user_id, timestamp, resource_id, amount

   - Таблица `statistics_power`:
     - user_id, timestamp, generation, consumption

   - Агрегация:
     - 1 минута: хранить всё (last 1 hour)
     - 10 минут: агрегировать (last 10 hours)
     - 1 час: агрегировать (last 100 hours)
     - Удалять старые данные

3. **Что трекать**:
   - **Производство**: сколько ресурса произведено
     - Drill → руда
     - Furnace → слитки
     - Assembler → продукты

   - **Потребление**: сколько ресурса использовано
     - Assembler → входные ресурсы
     - Boiler → топливо

   - **Электричество**:
     - Generation: сумма всех генераторов
     - Consumption: сумма всех потребителей
     - Satisfaction: 100% если generation >= consumption

4. **UI - Production Graph**:
   - График линии (line chart)
   - Ось X: время (last 1 hour / 10 hours / etc.)
   - Ось Y: units per minute
   - Несколько линий: каждый ресурс свой цвет
   - Легенда: список ресурсов (можно включить/выключить)
   - Фильтр: показать Production / Consumption / Both

5. **UI - Power Graph**:
   - График площади (area chart)
   - Зелёная зона: generation
   - Красная зона: consumption
   - Если красная выше зелёной: дефицит энергии!

6. **Фильтры**:
   - Временной диапазон: 5 min / 1 hour / 10 hours / all
   - Ресурсы: выбор каких ресурсов показать
   - Тип: Production / Consumption / Net (разница)

7. **Рендеринг графиков**:
   - Использовать Chart.js (легко интегрировать)
   ```javascript
   new Chart(ctx, {
     type: 'line',
     data: {
       labels: timestamps,
       datasets: [{
         label: 'Iron Plate',
         data: productionData,
         borderColor: 'rgb(255, 99, 132)',
       }]
     }
   });
   ```

8. **API**:
   - `statistics/production?timeRange=1hour&resourceId=5`
     - Возвращает: `[{timestamp, amount}, ...]`

   - `statistics/power?timeRange=10hours`
     - Возвращает: `[{timestamp, generation, consumption}, ...]`

9. **Дополнительная статистика**:
   - Текущие значения:
     - "Iron Plate: 500/min produced, 450/min consumed"
     - "Net: +50/min"
   - Всего за игру:
     - "Total Iron Plate produced: 1,234,567"
   - Top producers/consumers:
     - "Top producer: Furnace Array (200/min)"

10. **Bottleneck detection**:
    - Автоматически: если consumption > production → предупреждение
    - "Warning: Copper Plate consumption exceeds production!"
    - Показать на графике красным

11. **Оптимизация**:
    - Не хранить данные старше 100 часов (или агрегировать сильнее)
    - Кэшировать результаты на клиенте (не запрашивать каждую секунду)
    - Lazy loading: загружать данные только при открытии окна
