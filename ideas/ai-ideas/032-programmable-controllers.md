# Программируемые контроллеры (скриптинг)

## Описание
Возможность писать скрипты для управления базой:
- Programmable Controller (entity)
- Язык программирования (Lua или JavaScript)
- Доступ к API (read sensors, control machines)
- Автоматизация сложной логики
- Для продвинутых игроков

## Оценка сложности
**Экстремально высокая (10/10)**

## Оценка интересности
**Для целевой аудитории (10/10)**

## Краткий план реализации

1. **Scripting Environment**:
   - Встроенный редактор кода
   - Syntax highlighting
   - Sandbox для безопасности

2. **API**:
   ```lua
   -- Пример скрипта
   if getResource("Iron Plate") < 1000 then
     startMachine("Iron Smelter Array")
   else
     stopMachine("Iron Smelter Array")
   end
   ```

3. **Безопасность**:
   - Ограничение CPU (timeout)
   - Ограничение операций в секунду
   - Sandbox (нет доступа к файловой системе)
