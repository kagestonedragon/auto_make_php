# auto_make_php

### auto_make на Си
https://github.com/kagestonedragon/auto_make

### Входящие аргументы
* `--S` — папки с исходниками (рекурсивые обход папки) (.c)
* `--I` — хедер файлы (.h)
* `--N` — название собранного файла после компиляции
* `--D` — "зависимости" (используемые в проекте, например, библиотека, которая собирается из исходников через мейкфайл)
* `--L` — подключаемые библиотке

### Пример
```bash
php AutoMake.php --N test_project --S sources/ other_sources/ --I includes/ other_includes/ --D dep/lib1 dep/lib2 --L "dep/lib1 -llib1 dep/lib2 -llib2"
```
