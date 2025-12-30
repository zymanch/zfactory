# Главное здание

## Описание
Добавить главное здание (HQ), которое принимает обработанные ресурсы и отображает их количество в верхней части экрана. 
Ресурсы отображаются без названий, только цифры. 
При количестве более 1000 используется приставка "к" (например, 3132 → 3к). 
Дробные части не отображаются. Главное здание принимает только реурсы с type=crafted.

## Оценка сложности
**Средняя (6/10)**

- Новый entity_type (главное здание)
- Новая таблица для хранения ресурсов главного здания (user_resources)
- UI компонент для отображения ресурсов в header
- Логика приёма ресурсов от транспортных систем
- Фильтрация необработанных ресурсов

## Оценка интересности
**Высокая (9/10)**

Это ключевая механика для прогресса игры. 
Даёт игроку чёткую цель (накопление ресурсов) и визуальный фидбек о достижениях.

## Краткий план реализации

1. **База данных**:
   - Создать таблицу `user_resource` с полями: user_id, resource_id, quantity
     ```sql
     CREATE TABLE user_resource (
       id INT AUTO_INCREMENT PRIMARY KEY,
       user_id INT NOT NULL,
       resource_id INT NOT NULL,
       quantity INT NOT NULL DEFAULT 0,
       FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
       FOREIGN KEY (resource_id) REFERENCES resource(id) ON DELETE CASCADE,
       UNIQUE KEY (user_id, resource_id)
     );
     ```

   - Добавить entity_type для главного здания:
     ```sql
     INSERT INTO entity_type (name, sprite_folder, max_inventory, size, is_hq)
     VALUES ('Headquarters', 'hq', 0, 3, 1);
     ```

   - Добавить поле `is_hq` в entity_type для идентификации главного здания

2. **Backend**:
   - ResourceTransport: проверка, является ли целевая entity главным зданием
     ```php
     if ($targetEntity->entityType->is_hq) {
       // Добавляем в user_resource
       UserResource::addResource($userId, $resourceId, $quantity);
     } else {
       // Добавляем в entity_resource
       EntityResource::addResource($entityId, $resourceId, $quantity);
     }
     ```

   - Фильтрация: принимать только ресурсы с type='crafted'
     ```php
     if ($resource->type !== 'crafted') {
       throw new \Exception('HQ accepts only crafted resources');
     }
     ```

   - API endpoint `game/user-resources` для получения user_resource:
     ```php
     public function actionUserResources() {
       $resources = UserResource::find()
         ->where(['user_id' => Yii::$app->user->id])
         ->with('resource')
         ->all();
       return $this->asJson($resources);
     }
     ```

3. **Frontend**:
   - Header компонент для отображения ресурсов (верхний правый угол)
     ```javascript
     class ResourceHeader {
       constructor(game) {
         this.game = game;
         this.container = new PIXI.Container();
         this.resourceIcons = [];
         this.init();
       }

       init() {
         // Создать контейнер для иконок ресурсов
         this.container.x = window.innerWidth - 200;
         this.container.y = 10;
         this.game.app.stage.addChild(this.container);
       }

       update(userResources) {
         // Обновить значения количества
         userResources.forEach((res, index) => {
           const formatted = this.formatNumber(res.quantity);
           this.resourceIcons[index].text.text = formatted;
         });
       }

       formatNumber(num) {
         if (num >= 1000) {
           return Math.floor(num / 1000) + 'к';
         }
         return num.toString();
       }
     }
     ```

   - Иконки ресурсов без подписей (только изображение + число)
   - Обновление при изменениях через ResourceRenderer или polling
   - Позиционирование: верхний правый угол, горизонтальный ряд

4. **Спрайты**:
   - Создать красивый спрайт главного здания (размер 3x3 тайлов, 96×96px)
   - Стиль: большое, монументальное здание с флагом или антеннами
   - 5 состояний: normal, damaged, blueprint, normal_selected, damaged_selected
   - Папка: `public/assets/tiles/entities/hq/`
   - Главное здание не может быть уничтожено (durability всегда max)

5. **Балансировка**:
   - Главное здание уже построено при старте игры (создаётся при регистрации пользователя)
   - Позиция: центр карты, координаты (0, 0)
   - Нельзя построить второе главное здание
   - Нельзя удалить главное здание

6. **Дополнительные фичи** (опционально):
   - Анимация при получении ресурса (+1 всплывающий текст)
   - Звук при доставке ресурса в HQ
   - Подсветка главного здания при первом запуске (tutorial)
