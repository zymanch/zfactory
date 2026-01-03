/**
 * Game Modes Enum
 */
export const GameMode = {
    NORMAL: 'NORMAL',                           // Игровой режим по умолчанию
    BUILD: 'BUILD',                             // Режим строительства entity
    DELETE: 'DELETE',                           // Режим удаления entity
    ENTITY_INFO: 'ENTITY_INFO',                 // Окно информации о выбранном entity
    ENTITY_SELECTION_WINDOW: 'ENTITY_SELECTION_WINDOW',  // Окно выбора entity для строительства
    LANDING_SELECTION_WINDOW: 'LANDING_SELECTION_WINDOW', // Окно выбора landing
    LANDING_EDIT: 'LANDING_EDIT',              // Режим редактирования landing
    DEPOSIT_SELECTION_WINDOW: 'DEPOSIT_SELECTION_WINDOW', // Окно выбора deposit (admin)
    DEPOSIT_BUILD: 'DEPOSIT_BUILD'             // Режим размещения deposit (admin)
};

/**
 * GameModeManager - управление режимами игры
 * Гарантирует, что только один режим активен в момент времени
 */
export class GameModeManager {
    constructor(game) {
        this.game = game;
        this.currentMode = GameMode.NORMAL;
        this.previousMode = null;
        this.modeData = {}; // Дополнительные данные текущего режима
    }

    /**
     * Получить текущий режим
     */
    getCurrentMode() {
        return this.currentMode;
    }

    /**
     * Проверить, активен ли указанный режим
     */
    isMode(mode) {
        return this.currentMode === mode;
    }

    /**
     * Переключить режим
     */
    switchMode(newMode, data = {}) {
        if (this.currentMode === newMode) {
            console.warn(`Already in ${newMode} mode`);
            return;
        }

        console.log(`Mode switch: ${this.currentMode} → ${newMode}`);

        // Деактивировать текущий режим
        this.deactivateCurrentMode();

        // Сохранить предыдущий режим
        this.previousMode = this.currentMode;
        this.currentMode = newMode;
        this.modeData = data;

        // Активировать новый режим
        this.activateNewMode();

        // Обновить подсказки по кнопкам
        if (this.game.controlsHint) {
            this.game.controlsHint.update();
        }
    }

    /**
     * Деактивировать текущий режим
     */
    deactivateCurrentMode() {
        switch (this.currentMode) {
            case GameMode.NORMAL:
                this.deactivateNormalMode();
                break;
            case GameMode.BUILD:
                this.deactivateBuildMode();
                break;
            case GameMode.DELETE:
                this.deactivateDeleteMode();
                break;
            case GameMode.ENTITY_INFO:
                this.deactivateEntityInfoMode();
                break;
            case GameMode.ENTITY_SELECTION_WINDOW:
                this.deactivateEntitySelectionWindow();
                break;
            case GameMode.LANDING_SELECTION_WINDOW:
                this.deactivateLandingSelectionWindow();
                break;
            case GameMode.LANDING_EDIT:
                this.deactivateLandingEditMode();
                break;
            case GameMode.DEPOSIT_SELECTION_WINDOW:
                this.deactivateDepositSelectionWindow();
                break;
            case GameMode.DEPOSIT_BUILD:
                this.deactivateDepositBuildMode();
                break;
        }
    }

    /**
     * Активировать новый режим
     */
    activateNewMode() {
        switch (this.currentMode) {
            case GameMode.NORMAL:
                this.activateNormalMode();
                break;
            case GameMode.BUILD:
                this.activateBuildMode();
                break;
            case GameMode.DELETE:
                this.activateDeleteMode();
                break;
            case GameMode.ENTITY_INFO:
                this.activateEntityInfoMode();
                break;
            case GameMode.ENTITY_SELECTION_WINDOW:
                this.activateEntitySelectionWindow();
                break;
            case GameMode.LANDING_SELECTION_WINDOW:
                this.activateLandingSelectionWindow();
                break;
            case GameMode.LANDING_EDIT:
                this.activateLandingEditMode();
                break;
            case GameMode.DEPOSIT_SELECTION_WINDOW:
                this.activateDepositSelectionWindow();
                break;
            case GameMode.DEPOSIT_BUILD:
                this.activateDepositBuildMode();
                break;
        }
    }

    /**
     * Вернуться к предыдущему режиму или NORMAL
     */
    returnToPreviousMode() {
        const targetMode = this.previousMode || GameMode.NORMAL;
        this.switchMode(targetMode);
    }

    /**
     * Вернуться в нормальный режим
     */
    returnToNormalMode() {
        this.switchMode(GameMode.NORMAL);
    }

    // ================ NORMAL MODE ================
    activateNormalMode() {
        // Включить обработку hover для entity (tooltip, selected sprites)
        this.setEntityInteractivity(true);
    }

    deactivateNormalMode() {
        // Скрыть tooltip если был открыт
        if (this.game.entityTooltip) {
            this.game.entityTooltip.hide();
        }
    }

    // ================ BUILD MODE ================
    activateBuildMode() {
        const entityTypeId = this.modeData.entityTypeId;
        if (!entityTypeId) {
            console.error('BUILD mode activated without entityTypeId');
            this.returnToNormalMode();
            return;
        }

        // Отключить hover на entity
        this.setEntityInteractivity(false);

        // Активировать buildMode
        if (this.game.buildMode) {
            this.game.buildMode.activate(entityTypeId);
        }
    }

    deactivateBuildMode() {
        if (this.game.buildMode) {
            this.game.buildMode.deactivate();
        }
    }

    // ================ DELETE MODE ================
    activateDeleteMode() {
        // Включить hover на entity (для tooltip и deleting sprite)
        this.setEntityInteractivity(true);

        // Показать визуальную индикацию режима удаления
        this.showDeleteModeIndicator();

        // Изменить курсор
        this.game.app.canvas.style.cursor = 'crosshair';
    }

    deactivateDeleteMode() {
        // Скрыть индикацию
        this.hideDeleteModeIndicator();

        // Вернуть курсор
        this.game.app.canvas.style.cursor = 'default';

        // Скрыть tooltip
        if (this.game.entityTooltip) {
            this.game.entityTooltip.hide();
        }
    }

    showDeleteModeIndicator() {
        let indicator = document.getElementById('delete-mode-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'delete-mode-indicator';
            indicator.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(180, 0, 0, 0.9);
                color: white;
                padding: 15px 30px;
                border-radius: 8px;
                font-size: 18px;
                font-weight: bold;
                z-index: 9999;
                pointer-events: none;
                box-shadow: 0 4px 12px rgba(0,0,0,0.5);
            `;
            document.body.appendChild(indicator);
        }
        indicator.textContent = 'Режим удаления (Esc - выход)';
        indicator.style.display = 'block';
    }

    hideDeleteModeIndicator() {
        const indicator = document.getElementById('delete-mode-indicator');
        if (indicator) {
            indicator.style.display = 'none';
        }
    }

    // ================ ENTITY_INFO MODE ================
    activateEntityInfoMode() {
        const entityId = this.modeData.entityId;
        if (!entityId) {
            console.error('ENTITY_INFO mode activated without entityId');
            this.returnToNormalMode();
            return;
        }

        // Отключить hover на других entity
        this.setEntityInteractivity(false);

        // Открыть окно информации об entity
        if (this.game.entityInfoWindow) {
            this.game.entityInfoWindow.open(entityId);
        }
    }

    deactivateEntityInfoMode() {
        if (this.game.entityInfoWindow) {
            this.game.entityInfoWindow.close();
        }
    }

    // ================ ENTITY_SELECTION_WINDOW MODE ================
    activateEntitySelectionWindow() {
        // Отключить hover на entity
        this.setEntityInteractivity(false);

        // Открыть окно выбора entity
        if (this.game.buildingWindow) {
            this.game.buildingWindow.open();
        }
    }

    deactivateEntitySelectionWindow() {
        if (this.game.buildingWindow) {
            this.game.buildingWindow.close();
        }
    }

    // ================ LANDING_SELECTION_WINDOW MODE ================
    activateLandingSelectionWindow() {
        // Отключить hover на entity
        this.setEntityInteractivity(false);

        // Открыть окно выбора landing
        if (this.game.landingWindow) {
            this.game.landingWindow.open();
        }
    }

    deactivateLandingSelectionWindow() {
        if (this.game.landingWindow) {
            this.game.landingWindow.close();
        }
    }

    // ================ LANDING_EDIT MODE ================
    activateLandingEditMode() {
        const landingId = this.modeData.landingId;
        if (landingId === undefined) {
            console.error('LANDING_EDIT mode activated without landingId');
            this.returnToNormalMode();
            return;
        }

        // Отключить hover на entity
        this.setEntityInteractivity(false);

        // Активировать режим редактирования landing
        if (this.game.landingEditMode) {
            this.game.landingEditMode.activate(landingId);
        }
    }

    deactivateLandingEditMode() {
        if (this.game.landingEditMode) {
            this.game.landingEditMode.deactivate();
        }
    }

    // ================ DEPOSIT_SELECTION_WINDOW MODE (admin) ================
    activateDepositSelectionWindow() {
        // Отключить hover на entity
        this.setEntityInteractivity(false);

        // Открыть окно выбора deposit
        if (this.game.depositWindow) {
            this.game.depositWindow.open();
        }
    }

    deactivateDepositSelectionWindow() {
        if (this.game.depositWindow) {
            this.game.depositWindow.close();
        }
    }

    // ================ DEPOSIT_BUILD MODE (admin) ================
    activateDepositBuildMode() {
        const depositType = this.modeData.depositType;
        const minAmount = this.modeData.minAmount;
        const maxAmount = this.modeData.maxAmount;

        if (!depositType) {
            console.error('DEPOSIT_BUILD mode activated without depositType');
            this.returnToNormalMode();
            return;
        }

        // Отключить hover на entity
        this.setEntityInteractivity(false);

        // Активировать режим размещения deposit
        if (this.game.depositBuildMode) {
            this.game.depositBuildMode.activate({
                depositType,
                minAmount,
                maxAmount
            });
        }
    }

    deactivateDepositBuildMode() {
        if (this.game.depositBuildMode) {
            this.game.depositBuildMode.deactivate();
        }
    }

    // ================ HELPER METHODS ================

    /**
     * Включить/выключить интерактивность entity (hover events)
     */
    setEntityInteractivity(enabled) {
        for (const [key, sprite] of this.game.loadedEntities) {
            const entity = this.game.entityData.get(key);
            if (!entity || entity.state === 'blueprint') continue;

            // Включить/выключить интерактивность
            sprite.eventMode = enabled ? 'static' : 'none';
            sprite.cursor = enabled ? 'pointer' : 'default';
        }
    }

    /**
     * Проверить, можно ли показывать tooltip в текущем режиме
     */
    shouldShowTooltip() {
        return this.currentMode === GameMode.NORMAL || this.currentMode === GameMode.DELETE;
    }

    /**
     * Проверить, можно ли кликать по entity в текущем режиме
     */
    canClickEntity() {
        return this.currentMode === GameMode.NORMAL ||
               this.currentMode === GameMode.DELETE ||
               this.currentMode === GameMode.ENTITY_INFO;
    }

    /**
     * Получить тип hover спрайта для entity в зависимости от режима
     * @returns 'selected' | 'deleting' | null
     */
    getHoverSpriteType() {
        if (this.currentMode === GameMode.DELETE) {
            return 'deleting';
        }
        if (this.currentMode === GameMode.NORMAL) {
            return 'selected';
        }
        return null;
    }
}

export default GameModeManager;
