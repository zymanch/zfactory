# 🚀 Быстрая установка ComfyUI для FLUX

## Вариант 1: Автоматическая установка (рекомендую)

```bash
cd C:\Sites\zfactory.local\ai
install_comfyui.bat
```

**Что делает скрипт:**
1. ✅ Проверяет наличие Git
2. ✅ Клонирует ComfyUI (~2-5 мин)
3. ✅ Устанавливает PyTorch + зависимости (~5-10 мин)

**Требования:**
- Git: https://git-scm.com/download/win
- Python 3.10 или 3.11: https://www.python.org/downloads/

---

## Вариант 2: Portable версия (проще, но больше)

1. Скачайте готовую portable версию (~2.5GB):
   - https://github.com/comfyanonymous/ComfyUI/releases
   - Файл: `ComfyUI_windows_portable_nvidia_cu121_or_cpu.7z`

2. Распакуйте в: `C:\Sites\zfactory.local\ai\ComfyUI\`

3. Готово! Переходите к скачиванию моделей ⬇️

---

## После установки: Скачайте модели FLUX

**Важно:** Сохраняйте в правильные папки!

### 1. FLUX Model (11.9 GB)
- Ссылка: https://huggingface.co/Comfy-Org/flux1-dev/resolve/main/flux1-dev-fp8.safetensors
- Сохраните в: `ai/ComfyUI/models/checkpoints/`

### 2. T5 Text Encoder (9.8 GB)
- Ссылка: https://huggingface.co/comfyanonymous/flux_text_encoders/resolve/main/t5xxl_fp8_e4m3fn.safetensors
- Сохраните в: `ai/ComfyUI/models/clip/`

### 3. CLIP-L (246 MB)
- Ссылка: https://huggingface.co/comfyanonymous/flux_text_encoders/resolve/main/clip_l.safetensors
- Сохраните в: `ai/ComfyUI/models/clip/`

### 4. VAE (335 MB)
- Ссылка: https://huggingface.co/black-forest-labs/FLUX.1-dev/resolve/main/ae.safetensors
- Сохраните в: `ai/ComfyUI/models/vae/`

**Итого: ~22 GB**

---

## Проверка установки

### Структура должна быть:
```
ai/
├── ComfyUI/
│   ├── main.py                                    ✓
│   ├── models/
│   │   ├── checkpoints/
│   │   │   └── flux1-dev-fp8.safetensors          ✓ 11.9 GB
│   │   ├── clip/
│   │   │   ├── t5xxl_fp8_e4m3fn.safetensors       ✓ 9.8 GB
│   │   │   └── clip_l.safetensors                 ✓ 246 MB
│   │   └── vae/
│   │       └── ae.safetensors                     ✓ 335 MB
│   └── ...
├── start_comfyui.bat
├── install_comfyui.bat
└── workflow_seamless_sprite.json
```

### Тест запуска:
```bash
cd C:\Sites\zfactory.local\ai
start_comfyui.bat
```

**Ожидаемый результат:**
```
Starting ComfyUI with RTX 3060 12GB optimizations...
[INFO] ComfyUI will be available at: http://localhost:8188
Wait for model loading (~2-3 min on first run)...
```

Откройте: http://localhost:8188

---

## ❌ Решение проблем

### "Git not found"
Установите Git: https://git-scm.com/download/win

### "Python not found"
Установите Python 3.10 или 3.11: https://www.python.org/downloads/
⚠️ При установке поставьте галочку "Add Python to PATH"!

### "Model not found"
Проверьте что модели в правильных папках (см. структуру выше)

### "Out of Memory"
1. Увеличьте pagefile до 32GB (см. FLUX_SETUP.md)
2. Закройте другие программы
3. Перезагрузите компьютер

---

## ✅ Готово!

После установки ComfyUI и скачивания моделей:

```bash
# 1. Запустите ComfyUI
cd ai
start_comfyui.bat

# 2. В другом окне - генерация
cd C:\Sites\zfactory.local
php yii landing/generate-ai-flux grass
```

**Успехов!** 🚀
