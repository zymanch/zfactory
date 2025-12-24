# FLUX.1 Dev Setup Guide для RTX 3060 12GB

## ⚠️ Важно: Компенсация 16GB RAM

У вас 16GB RAM, а FLUX рекомендует 32GB. Решение: увеличить **pagefile** (виртуальную память).

### 1. Увеличение Pagefile до 32GB

**Windows 10/11:**

1. Нажмите `Win + Pause/Break` → "Дополнительные параметры системы"
2. Вкладка "Дополнительно" → "Быстродействие" → "Параметры"
3. Вкладка "Дополнительно" → "Виртуальная память" → "Изменить"
4. Снимите галочку "Автоматически выбирать объем файла подкачки"
5. Выберите диск (желательно SSD)
6. "Указать размер":
   - **Исходный размер:** 32768 МБ (или 49152 для 48GB, 65536 для 64GB)
   - **Максимальный размер:** 49152 МБ (или 65536, или 81920)
7. Нажмите "Задать" → "ОК"
8. **⚠️ ОБЯЗАТЕЛЬНО ПЕРЕЗАГРУЗИТЕ КОМПЬЮТЕР!**
   - Изменения pagefile применяются только после перезагрузки
   - Без перезагрузки FLUX будет падать с Segmentation Fault

**Проверка:**
```bash
# В PowerShell:
Get-WmiObject Win32_PageFileUsage | Select-Object Name, AllocatedBaseSize
```

---

## 📦 Шаг 2: Установка ComfyUI

### A. Установка через portable версию (рекомендую)

```bash
# 1. Перейдите в папку ai
cd C:\Sites\zfactory.local\ai

# 2. Скачайте ComfyUI Portable
# Ссылка: https://github.com/comfyanonymous/ComfyUI/releases
# Файл: ComfyUI_windows_portable_nvidia_cu121_or_cpu.7z (~2.5GB)

# 3. Распакуйте в папку ai/ComfyUI
```

### B. Или установка через git (альтернатива)

```bash
cd C:\Sites\zfactory.local\ai

# Клонируем репозиторий
git clone https://github.com/comfyanonymous/ComfyUI.git

cd ComfyUI

# Создаем виртуальное окружение
python -m venv venv

# Активируем
venv\Scripts\activate

# Устанавливаем зависимости
pip install torch torchvision torchaudio --index-url https://download.pytorch.org/whl/cu121
pip install -r requirements.txt
```

---

## 🤖 Шаг 3: Скачивание FLUX.1 Dev FP8

### Вариант A: Через браузер (проще)

1. **FLUX.1 Dev FP8** (~11.9 GB):
   - Ссылка: https://huggingface.co/Comfy-Org/flux1-dev/resolve/main/flux1-dev-fp8.safetensors
   - Сохраните в: `ai/ComfyUI/models/checkpoints/flux1-dev-fp8.safetensors`

2. **T5 Text Encoder** (~9.8 GB):
   - Ссылка: https://huggingface.co/comfyanonymous/flux_text_encoders/resolve/main/t5xxl_fp8_e4m3fn.safetensors
   - Сохраните в: `ai/ComfyUI/models/clip/t5xxl_fp8_e4m3fn.safetensors`

3. **CLIP-L Text Encoder** (~246 MB):
   - Ссылка: https://huggingface.co/comfyanonymous/flux_text_encoders/resolve/main/clip_l.safetensors
   - Сохраните в: `ai/ComfyUI/models/clip/clip_l.safetensors`

4. **VAE** (~335 MB):
   - Ссылка: https://huggingface.co/black-forest-labs/FLUX.1-dev/resolve/main/ae.safetensors
   - Сохраните в: `ai/ComfyUI/models/vae/ae.safetensors`

### Вариант B: Через wget (быстрее)

```bash
cd ai/ComfyUI

# FLUX model
wget -P models/checkpoints/ https://huggingface.co/Comfy-Org/flux1-dev/resolve/main/flux1-dev-fp8.safetensors

# Text encoders
wget -P models/clip/ https://huggingface.co/comfyanonymous/flux_text_encoders/resolve/main/t5xxl_fp8_e4m3fn.safetensors
wget -P models/clip/ https://huggingface.co/comfyanonymous/flux_text_encoders/resolve/main/clip_l.safetensors

# VAE
wget -P models/vae/ https://huggingface.co/black-forest-labs/FLUX.1-dev/resolve/main/ae.safetensors
```

**Итого скачать:** ~22 GB

---

## 🚀 Шаг 4: Запуск ComfyUI

### Создайте bat файл для запуска

Создайте `ai/start_comfyui.bat`:

```bat
@echo off
cd ComfyUI
python main.py --listen 0.0.0.0 --port 8188 --lowvram --preview-method auto
pause
```

**Параметры:**
- `--listen 0.0.0.0` - доступ из PHP
- `--port 8188` - порт API
- `--lowvram` - оптимизация для 12GB VRAM
- `--preview-method auto` - превью во время генерации

### Запустите:

```bash
cd C:\Sites\zfactory.local\ai
start_comfyui.bat
```

**Проверка:**
- Откройте http://localhost:8188
- Должен загрузиться веб-интерфейс ComfyUI

---

## 🎨 Шаг 5: Тестирование FLUX

### Простой тест через веб-интерфейс:

1. Откройте http://localhost:8188
2. Загрузите базовый workflow (должен быть по умолчанию)
3. Поменяйте модель на `flux1-dev-fp8.safetensors`
4. Промпт: `seamless tileable grass texture, top-down view`
5. Нажмите "Queue Prompt"

**Ожидаемое время:** 1-2 минуты (первый раз дольше из-за загрузки модели)

---

## 📊 Ожидаемая производительность

| Параметр | Время |
|----------|-------|
| Загрузка модели (первый раз) | ~2-3 мин |
| Генерация 512×384 | ~40-90 сек |
| Генерация 768×576 | ~2-3 мин |

**С 16GB RAM + pagefile:** добавится еще ~20-30% времени (обращение к диску)

---

## ✅ Следующие шаги

После успешного запуска:
1. ✅ Создать workflow для seamless tiling
2. ✅ Настроить ComfyUI API
3. ✅ Переписать PHP код для работы с ComfyUI
4. ✅ Протестировать генерацию всех лендингов

---

## 🆘 Решение проблем

### ⚠️ Segmentation Fault / ComfyUI падает при генерации

**Симптомы:**
- ComfyUI загружает VAE и CLIP, затем падает
- В логах: "Segmentation fault"
- PHP скрипт: "Timeout waiting for generation"

**Решения (по порядку):**

1. **НЕ ПЕРЕЗАГРУЗИЛИ ПОСЛЕ ИЗМЕНЕНИЯ PAGEFILE**
   ```
   ⚠️ ОБЯЗАТЕЛЬНО перезагрузите компьютер!
   Pagefile применяется только после перезагрузки.
   ```

2. **Pagefile мал (нужно 48-64GB)**
   - Увеличьте до 48GB или 64GB (см. раздел "Увеличение Pagefile")
   - Перезагрузите компьютер
   - Попробуйте снова

3. **16GB RAM всё ещё недостаточно**
   - Вернитесь к Stable Diffusion (Realistic Vision ~4GB)
   - Или используйте FLUX.1-schnell (легче чем Dev)
   - Или добавьте физической RAM до 32GB

### Out of Memory (OOM)

```bash
# Если вылетает с OOM, добавьте:
--lowvram --normalvram
```

### Очень медленно

```bash
# Нормально для 16GB RAM + pagefile
# Убедитесь что pagefile на SSD, не HDD
```

### Model not found

```bash
# FLUX использует папку unet/, не checkpoints/!
# Проверьте пути:
ai/ComfyUI/models/unet/flux1-dev-fp8.safetensors     (НЕ checkpoints!)
ai/ComfyUI/models/clip/t5xxl_fp8_e4m3fn.safetensors
ai/ComfyUI/models/clip/clip_l.safetensors
ai/ComfyUI/models/vae/ae.safetensors
```

---

## 📝 Полезные ссылки

- [ComfyUI GitHub](https://github.com/comfyanonymous/ComfyUI)
- [FLUX Models](https://huggingface.co/black-forest-labs)
- [ComfyUI Wiki](https://comfyui-wiki.com)
