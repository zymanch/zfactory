# Приоритетные разветвители

## Описание
Расширение системы splitters с умными приоритетами:
- Priority Input: сначала брать с одной линии
- Priority Output: сначала заполнять одну линию
- Overflow handling: если основная линия полна → направить в другую
- Smart distribution

## Оценка сложности
**Низкая (4/10)**

## Оценка интересности
**Высокая (8/10)**

## Краткий план реализации

1. **Priority Settings**:
   - UI: "Input Priority: Left/Right"
   - UI: "Output Priority: Left/Right"

2. **Логика**:
   - Priority Input: опустошить левую линию полностью, затем правую
   - Priority Output: заполнить левую полностью, затем правую

3. **Use Cases**:
   - Main bus: приоритет основной линии
   - Overflow: излишки направить на storage
   - Balancing: равномерное распределение
