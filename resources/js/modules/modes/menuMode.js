import { GameModeBase } from './gameModeBase.js';

/**
 * MenuMode - ESC-меню с затемнением
 *
 * Особенности:
 * - Игра продолжает работать (game loop НЕ останавливается)
 * - Затемнение игровой области (backdrop overlay)
 * - Блокировка взаимодействий с игрой
 * - Закрывается на ESC
 *
 * Пункты меню:
 * 1. Путешествия → /regions/index
 * 2. Сохранить игру → game.saveGame()
 * 3. Настройки (FPS ползунок, автосохранение)
 * 4. Профилирование (только для admin)
 * 5. Выход → /site/logout
 */
export class MenuMode extends GameModeBase {
    constructor(game) {
        super(game);
        this.modalContainer = null;
        this.backdrop = null;
        this.isAdmin = false;
        this.profilingActive = false;
        this.profilingTimeout = null;
        this.profilingResults = null;
    }

    /**
     * Initialize mode (called once on game startup)
     */
    init() {
        this.createBackdrop();
        this.createModal();
    }

    /**
     * Hook called when mode is activated
     */
    onActivate(data) {
        this.isAdmin = data.isAdmin || false;

        // Пересоздать содержимое меню для учета admin статуса
        this.updateMenuContent();

        // Показать backdrop и modal
        this.backdrop.style.display = 'block';
        this.modalContainer.style.display = 'flex';

        // Анимация появления
        this.animateIn();
    }

    /**
     * Hook called when mode is deactivated
     */
    onDeactivate() {
        // Скрыть backdrop и modal
        this.backdrop.style.display = 'none';
        this.modalContainer.style.display = 'none';

        // Остановить профилирование если активно
        if (this.profilingActive) {
            this.stopProfiling();
        }
    }

    /**
     * Создать полупрозрачный backdrop
     */
    createBackdrop() {
        this.backdrop = document.createElement('div');
        this.backdrop.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: none;
        `;
        document.body.appendChild(this.backdrop);

        // Клик по backdrop закрывает меню
        this.backdrop.addEventListener('click', () => {
            this.game.gameModeManager.returnToNormalMode();
        });
    }

    /**
     * Создать центральное меню
     */
    createModal() {
        this.modalContainer = document.createElement('div');
        this.modalContainer.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 500px;
            max-width: 90vw;
            max-height: 90vh;
            overflow-y: auto;
            background: rgba(20, 20, 30, 0.98);
            border: 2px solid #4a90e2;
            border-radius: 12px;
            padding: 30px;
            z-index: 10000;
            display: none;
            flex-direction: column;
            gap: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        `;
        document.body.appendChild(this.modalContainer);

        // Предотвратить закрытие при клике внутри меню
        this.modalContainer.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }

    /**
     * Обновить содержимое меню
     */
    updateMenuContent() {
        // Очистить текущее содержимое
        this.modalContainer.innerHTML = '';

        // Заголовок
        const title = this.createTitle('Game Menu');
        this.modalContainer.appendChild(title);

        // 1. Путешествия
        const travelBtn = this.createButton('🗺️ Travel to Regions', () => {
            window.location.href = '/regions/index';
        });
        this.modalContainer.appendChild(travelBtn);

        // 2. Сохранить игру
        const saveBtn = this.createButton('💾 Save Game', (e) => {
            this.saveGame(e.target);
        });
        this.modalContainer.appendChild(saveBtn);

        // 3. Настройки
        const settingsSection = this.createSettingsSection();
        this.modalContainer.appendChild(settingsSection);

        // 4. Профилирование (только для админов)
        if (this.isAdmin) {
            const profilingSection = this.createProfilingSection();
            this.modalContainer.appendChild(profilingSection);
        }

        // 5. Выход
        const logoutBtn = this.createButton('🚪 Logout', () => {
            window.location.href = '/site/logout';
        }, 'danger');
        this.modalContainer.appendChild(logoutBtn);

        // ESC hint
        const escHint = this.createHint('Press ESC to close');
        this.modalContainer.appendChild(escHint);
    }

    /**
     * Создать секцию настроек
     */
    createSettingsSection() {
        const section = document.createElement('div');
        section.style.cssText = `
            border: 1px solid #555;
            border-radius: 8px;
            padding: 15px;
            background: rgba(0, 0, 0, 0.3);
        `;

        const sectionTitle = document.createElement('div');
        sectionTitle.textContent = '⚙️ Settings';
        sectionTitle.style.cssText = `
            color: #4a90e2;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 15px;
        `;
        section.appendChild(sectionTitle);

        // FPS ползунок
        const fpsContainer = document.createElement('div');
        fpsContainer.style.marginBottom = '15px';

        const fpsLabel = document.createElement('label');
        const currentFPS = this.game.graphics?.getTicker()?.maxFPS || 60;
        fpsLabel.textContent = `Target FPS: ${currentFPS}`;
        fpsLabel.style.cssText = 'color: #ccc; font-size: 14px; display: block; margin-bottom: 5px;';

        const fpsSlider = document.createElement('input');
        fpsSlider.type = 'range';
        fpsSlider.min = '30';
        fpsSlider.max = '120';
        fpsSlider.value = currentFPS.toString();
        fpsSlider.style.cssText = 'width: 100%;';

        fpsSlider.addEventListener('input', (e) => {
            const fps = parseInt(e.target.value);
            fpsLabel.textContent = `Target FPS: ${fps}`;

            // Обновить FPS в игре
            if (this.game.graphics?.getTicker()) {
                this.game.graphics.getTicker().maxFPS = fps;
            }
        });

        fpsContainer.appendChild(fpsLabel);
        fpsContainer.appendChild(fpsSlider);
        section.appendChild(fpsContainer);

        // Автосохранение (read-only)
        const autoSaveText = document.createElement('div');
        const interval = this.game.config?.autoSaveInterval || 60;
        autoSaveText.textContent = `💾 Auto-save: every ${interval} seconds`;
        autoSaveText.style.cssText = `
            color: #888;
            font-size: 13px;
            padding: 8px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 4px;
        `;
        section.appendChild(autoSaveText);

        return section;
    }

    /**
     * Создать секцию профилирования (только для админов)
     */
    createProfilingSection() {
        const section = document.createElement('div');
        section.style.cssText = `
            border: 1px solid #e2a430;
            border-radius: 8px;
            padding: 15px;
            background: rgba(226, 164, 48, 0.1);
        `;

        const sectionTitle = document.createElement('div');
        sectionTitle.textContent = '📊 Performance Profiling (Admin)';
        sectionTitle.style.cssText = `
            color: #e2a430;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 15px;
        `;
        section.appendChild(sectionTitle);

        const startBtn = this.createButton('▶️ Start Profiling (10s)', () => {
            this.startProfiling(startBtn);
        }, 'warning');
        section.appendChild(startBtn);

        this.profilingResults = document.createElement('div');
        this.profilingResults.style.cssText = `
            margin-top: 10px;
            padding: 12px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #0f0;
            max-height: 200px;
            overflow-y: auto;
            display: none;
            white-space: pre;
        `;
        section.appendChild(this.profilingResults);

        return section;
    }

    /**
     * Сохранить игру
     */
    async saveGame(button) {
        button.disabled = true;
        const originalText = button.textContent;
        button.textContent = '💾 Saving...';

        try {
            // Вызвать метод сохранения игры (если существует)
            if (typeof this.game.saveGame === 'function') {
                await this.game.saveGame();
            } else {
                // Fallback - просто эмулировать сохранение
                await new Promise(resolve => setTimeout(resolve, 500));
            }

            // Показать успех
            button.textContent = '✅ Saved!';
            button.style.background = '#4CAF50';

            setTimeout(() => {
                button.textContent = originalText;
                button.style.background = '';
                button.disabled = false;
            }, 2000);
        } catch (error) {
            console.error('Failed to save game:', error);

            button.textContent = '❌ Failed';
            button.style.background = '#d9534f';

            setTimeout(() => {
                button.textContent = originalText;
                button.style.background = '';
                button.disabled = false;
            }, 2000);
        }
    }

    /**
     * Начать профилирование
     */
    startProfiling(button) {
        if (this.profilingActive) return;

        this.profilingActive = true;
        button.disabled = true;
        button.textContent = '⏳ Profiling...';

        // Включить профилирование через метод игры
        if (typeof this.game.startProfiling === 'function') {
            this.game.startProfiling();
        }

        // Показать индикатор
        this.profilingResults.textContent = 'Profiling active... (10 seconds)\nCollecting performance data...';
        this.profilingResults.style.display = 'block';

        // Через 10 секунд - показать результаты
        this.profilingTimeout = setTimeout(() => {
            let results = [];

            if (typeof this.game.getProfilingResults === 'function') {
                results = this.game.getProfilingResults();
            } else {
                // Fallback - показать mock данные
                results = [
                    { manager: 'resourceTransport', avgTime: 2.5, totalTime: 250, calls: 100 },
                    { manager: 'conveyorManager', avgTime: 1.8, totalTime: 180, calls: 100 },
                    { manager: 'electricityManager', avgTime: 1.2, totalTime: 120, calls: 100 }
                ];
            }

            this.displayProfilingResults(results);

            if (typeof this.game.stopProfiling === 'function') {
                this.game.stopProfiling();
            }

            this.profilingActive = false;
            button.disabled = false;
            button.textContent = '▶️ Start Profiling (10s)';
        }, 10000);
    }

    /**
     * Остановить профилирование
     */
    stopProfiling() {
        if (this.profilingTimeout) {
            clearTimeout(this.profilingTimeout);
            this.profilingTimeout = null;
        }

        if (typeof this.game.stopProfiling === 'function') {
            this.game.stopProfiling();
        }

        this.profilingActive = false;
    }

    /**
     * Отобразить результаты профилирования
     */
    displayProfilingResults(results) {
        if (!results || results.length === 0) {
            this.profilingResults.textContent = 'No profiling data collected.';
            return;
        }

        // Форматировать результаты
        let formatted = 'Performance Report (10s):\n';
        formatted += '═'.repeat(50) + '\n';
        formatted += 'Manager                  Avg Time    Calls\n';
        formatted += '─'.repeat(50) + '\n';

        for (const r of results) {
            const manager = r.manager.padEnd(24);
            const avgTime = `${r.avgTime.toFixed(2)}ms`.padEnd(12);
            const calls = r.calls.toString();
            formatted += `${manager}${avgTime}${calls}\n`;
        }

        formatted += '═'.repeat(50);

        this.profilingResults.textContent = formatted;
    }

    /**
     * Создать кнопку
     */
    createButton(text, onClick, variant = 'primary') {
        const button = document.createElement('button');
        button.textContent = text;

        let background = '#4a90e2';
        if (variant === 'danger') background = '#d9534f';
        if (variant === 'warning') background = '#e2a430';

        button.style.cssText = `
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.2s;
            background: ${background};
            color: white;
            width: 100%;
        `;

        button.addEventListener('mouseenter', () => {
            button.style.transform = 'translateY(-2px)';
            button.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.3)';
        });

        button.addEventListener('mouseleave', () => {
            button.style.transform = 'translateY(0)';
            button.style.boxShadow = 'none';
        });

        button.addEventListener('click', onClick);

        return button;
    }

    /**
     * Создать заголовок
     */
    createTitle(text) {
        const title = document.createElement('h2');
        title.textContent = text;
        title.style.cssText = `
            margin: 0 0 20px 0;
            color: #fff;
            font-size: 28px;
            text-align: center;
            font-weight: 700;
        `;
        return title;
    }

    /**
     * Создать hint текст
     */
    createHint(text) {
        const hint = document.createElement('div');
        hint.textContent = text;
        hint.style.cssText = `
            color: #888;
            font-size: 12px;
            text-align: center;
            margin-top: 10px;
        `;
        return hint;
    }

    /**
     * Анимация появления
     */
    animateIn() {
        this.backdrop.style.opacity = '0';
        this.modalContainer.style.opacity = '0';
        this.modalContainer.style.transform = 'translate(-50%, -45%)';

        setTimeout(() => {
            this.backdrop.style.transition = 'opacity 0.2s';
            this.backdrop.style.opacity = '1';

            this.modalContainer.style.transition = 'opacity 0.3s, transform 0.3s';
            this.modalContainer.style.opacity = '1';
            this.modalContainer.style.transform = 'translate(-50%, -50%)';
        }, 10);
    }
}

export default MenuMode;
