# Лабораторная работа №8: Непрерывная интеграция с помощью Github Actions

## Выполнил

* Mihailov Piotr I2302
* Дата выполнения: 16.04.25

## Цель работы

В рамках данной работы студенты научатся настраивать непрерывную интеграцию с помощью Github Actions.

## Задание

Создать Web приложение, написать тесты для него и настроить непрерывную интеграцию с помощью Github Actions на базе контейнеров

## Подготовка

Для выполнения лабораторной работы необходимо:

* Установленный Docker

## Выполнение

### Шаг 1: Клонирование репозитория и создание структуры проекта

1. Создаю репозиторий `containers08`
2. Клонирую репозиторий на локальный компьютер с помощью команды `git clone`
3. В директорию `containers08` создаю директорию `/site` в которой будет распологаться Web приложение на базе PHP.
4. В директории ./site создаю следующую структуру:

![1](/images/stuct.png)

### Шаг 2: Web-приложение

* В `site/modules/database.php` реализован класс `Database` с методами: `__construct`, `Execute`, `Fetch`, `Create`, `Read`, `Update`, `Delete`, `Count`.

```php
<?php

class Database {
    private $db;

    public function __construct($path) {
        $this->db = new PDO("sqlite:$path");
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function Execute($sql) {
        return $this->db->exec($sql);
    }

    public function Fetch($sql) {
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function Create($table, $data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function Read($table, $id) {
        $stmt = $this->db->prepare("SELECT * FROM $table WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function Update($table, $id, $data) {
        $set = implode(", ", array_map(fn($key) => "$key = :$key", array_keys($data)));
        $sql = "UPDATE $table SET $set WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function Delete($table, $id) {
        $stmt = $this->db->prepare("DELETE FROM $table WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function Count($table) {
        $stmt = $this->db->query("SELECT COUNT(*) FROM $table");
        return $stmt->fetchColumn();
    }
}
```

Этот код определяет класс Database для работы с базой данных SQLite через PDO. Конструктор устанавливает соединение с базой по указанному пути и включает обработку ошибок. Метод Execute выполняет SQL-запросы, не возвращающие данные, а Fetch возвращает результаты запросов в виде ассоциативных массивов. Метод Create вставляет новую запись в таблицу, формируя запрос из переданных данных и возвращая ID созданной записи. Read извлекает запись по ID, Update обновляет запись, Delete удаляет запись, а Count возвращает количество записей в таблице.

* В `site/modules/page.php` реализован класс `Page` с методами `__construct` и `Render`

```php
<?php

class Page {
    private $template;

    public function __construct($template) {
        $this->template = $template;
    }

    public function Render($data) {
        ob_start();
        extract($data);
        include $this->template;
        return ob_get_clean();
    }
}
```

Код реализует класс Page для работы с шаблонами страниц. Конструктор принимает путь к файлу шаблона. Метод Render использует буферизацию вывода, извлекает данные из массива в переменные и подключает шаблон, возвращая сгенерированный HTML-код.

* `site/index.php` отображает данные из базы.

```php
<?php

require_once __DIR__ . '/modules/database.php';
require_once __DIR__ . '/modules/page.php';
require_once __DIR__ . '/config.php';

$db = new Database($config["db"]["path"]);
$page = new Page(__DIR__ . '/templates/index.tpl');

$pageId = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$data = $db->Read("page", $pageId);

echo $page->Render($data);
```

Скрипт подключает необходимые модули и конфигурацию, создает объекты Database и Page, получает ID страницы из GET-параметра (по умолчанию 1), извлекает данные из базы и отображает страницу, рендеря шаблон с полученными данными.

* `site/config.php` содержит путь к базе данных SQLite

```php
<?php

$config = [
    "db" => [
        "path" => "/var/www/db/db.sqlite"
    ]
];
```

Этот код определяет массив конфигурации с указанием пути к файлу базы данных SQLite, который используется для подключения в классе Database.

* Стили оформлены в `styles/style.css`

```css
body {
    font-family: Arial, sans-serif;
    margin: 20px;
}
h1 {
    color: #333;
}
```

Код задает базовые стили для страницы: шрифт Arial, отступы для тела документа и темный цвет для заголовков первого уровня, обеспечивая минимальное оформление.

* Шаблон страницы — в `templates/index.tpl`

```tpl
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="/styles/style.css">
</head>
<body>
    <h1><?php echo htmlspecialchars($title); ?></h1>
    <p><?php echo htmlspecialchars($content); ?></p>
</body>
</html>
```

Этот шаблон представляет HTML-страницу, отображающую заголовок и содержимое, переданные в переменных $title и $content. Функция htmlspecialchars экранирует данные для защиты от XSS-атак. Стили подключаются из файла style.css.

### Шаг 3: База данных

В корневом каталоге была создана директория `./sql` и в ней был создан файл `schema.sql` со следующим содержимым:

```sql
CREATE TABLE page (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    content TEXT
);

INSERT INTO page (title, content) VALUES ('Page 1', 'Content 1');
INSERT INTO page (title, content) VALUES ('Page 2', 'Content 2');
INSERT INTO page (title, content) VALUES ('Page 3', 'Content 3');
```

Код создает таблицу page с полями для ID, заголовка и содержимого, где ID автоматически увеличивается. Также добавляет три тестовые записи для страниц.

### Шаг 4 : Создание тестов

В корневом каталоге была создана директория `./tests` и в ней был создан файл `testframework.php` со следующим содержимым:

```php
<?php

function message($type, $message) {
    $time = date('Y-m-d H:i:s');
    echo "{$time} [{$type}] {$message}" . PHP_EOL;
}

function info($message) {
    message('INFO', $message);
}

function error($message) {
    message('ERROR', $message);
}

function assertExpression($expression, $pass = 'Pass', $fail = 'Fail'): bool {
    if ($expression) {
        info($pass);
        return true;
    }
    error($fail);
    return false;
}

class TestFramework {
    private $tests = [];
    private $success = 0;

    public function add($name, $test) {
        $this->tests[$name] = $test;
    }

    public function run() {
        foreach ($this->tests as $name => $test) {
            info("Running test {$name}");
            if ($test()) {
                $this->success++;
            }
            info("End test {$name}");
        }
    }

    public function getResult() {
        return "{$this->success} / " . count($this->tests);
    }
}
```

Код реализует простой тестовый фреймворк. Функции message, info и error выводят сообщения с метками времени и типом. Функция assertExpression проверяет условие, логируя успех или ошибку. Класс TestFramework позволяет добавлять тесты, запускать их и подсчитывать успешные выполнения, возвращая результат в формате "успешно/всего".

Далее в директории `./tests` был создан файл `test.php` со следующим содержимым:

```php
<?php

require_once __DIR__ . '/testframework.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../modules/database.php';
require_once __DIR__ . '/../modules/page.php';

$testFramework = new TestFramework();

function testDbConnection() {
    global $config;
    try {
        $db = new Database($config["db"]["path"]);
        return assertExpression($db !== null, "Database connection successful", "Failed to connect to database");
    } catch (Exception $e) {
        return assertExpression(false, "", "Database connection error: " . $e->getMessage());
    }
}

function testDbCount() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $count = $db->Count("page");
    return assertExpression($count === 3, "Table count is correct", "Table count is incorrect");
}

function testDbCreate() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $data = ['title' => 'Test Page', 'content' => 'Test Content'];
    $id = $db->Create("page", $data);
    return assertExpression($id > 0, "Record created successfully", "Failed to create record");
}

function testDbRead() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $data = $db->Read("page", 1);
    return assertExpression($data['title'] === 'Page 1', "Record read successfully", "Failed to read record");
}

function testDbUpdate() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $data = ['title' => 'Updated Page', 'content' => 'Updated Content'];
    $result = $db->Update("page", 1, $data);
    $updated = $db->Read("page", 1);
    return assertExpression($updated['title'] === 'Updated Page', "Record updated successfully", "Failed to update record");
}

function testDbDelete() {
    global $config;
    $db = new Database($config["db"]["path"]);
    $result = $db->Delete("page", 2);
    $data = $db->Read("page", 2);
    return assertExpression($data === false, "Record deleted successfully", "Failed to delete record");
}

function testPageRender() {
    $page = new Page(__DIR__ . '/../site/templates/index.tpl');
    $data = ['title' => 'Test', 'content' => 'Content'];
    $output = $page->Render($data);
    return assertExpression(strpos($output, 'Test') !== false, "Page rendered successfully", "Failed to render page");
}

$testFramework->add('Database connection', 'testDbConnection');
$testFramework->add('Table count', 'testDbCount');
$testFramework->add('Data create', 'testDbCreate');
$testFramework->add('Data read', 'testDbRead');
$testFramework->add('Data update', 'testDbUpdate');
$testFramework->add('Data delete', 'testDbDelete');
$testFramework->add('Page render', 'testPageRender');

$testFramework->run();
echo $testFramework->getResult();
```

Код содержит тесты для проверки функциональности классов Database и Page. Подключаются необходимые файлы, создается объект TestFramework. Тесты проверяют: подключение к базе данных, подсчет записей в таблице, создание, чтение, обновление и удаление записи, а также рендеринг страницы. Каждый тест использует assertExpression для проверки условий и логирования результатов. Тесты добавляются в фреймворк, запускаются, и выводится результат выполнения.

### Шаг 5 : Создание Dockerfile

В корневом каталоге был создан `Dockefile` со следующим содержимым:

```dockerfile
FROM php:7.4-fpm as base

RUN apt-get update && \
    apt-get install -y sqlite3 libsqlite3-dev && \
    docker-php-ext-install pdo_sqlite

VOLUME ["/var/www/db"]

COPY sql/schema.sql /var/www/db/schema.sql

RUN echo "prepare database" && \
    cat /var/www/db/schema.sql | sqlite3 /var/www/db/db.sqlite && \
    chmod 777 /var/www/db/db.sqlite && \
    rm -rf /var/www/db/schema.sql && \
    echo "database is ready"

COPY site /var/www/html
```

Код описывает сборку Docker-образа на базе PHP 7.4. Устанавливаются SQLite и расширение PDO, создается том для базы данных, копируется SQL-схема, инициализируется база данных, задаются права доступа, удаляется временный файл схемы, и файлы приложения копируются в рабочую директорию контейнера.

### Шаг 6 : Настройка Github Actions

В корневом каталоге репозитория была создана следующая структура папок `.github/workflows` и в директории `workflows` был создан файл `main.yml` со следующим содержимым:

```yml
name: CI

on:
  push:
    branches:
      - main

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4
      - name: Build the Docker image
        run: docker build -t containers08 .
      - name: Create `container`
        run: docker create --name container --volume database:/var/www/db containers08
      - name: Copy tests to the container
        run: docker cp ./tests container:/var/www/html
      - name: Up the container
        run: docker start container
      - name: Run tests
        run: docker exec container php /var/www/html/tests/tests.php
      - name: Stop the container
        run: docker stop container
      - name: Remove the container
        run: docker rm container
```

Код настраивает процесс непрерывной интеграции, который запускается при каждом push в ветку main. На сервере Ubuntu выполняется checkout кода, сборка Docker-образа, создание и запуск контейнера, копирование тестов, выполнение тестов, остановка и удаление контейнера, обеспечивая автоматизированное тестирование приложения.

### Шаг 7 : Запуск и тестирование

В ходе работы я отправил два изменения в репозиторий и все тесты прошли успешно.

Для этого я перешел в вкладку `Actions` и дождался окончания выполнения задачи

Результат:
![image](/images/check1.png)
![image2](/images/check2.png)

### Вопросы и ответы

* Что такое непрерывная интеграция?

Непрерывная интеграция (CI) — это практика автоматической сборки и тестирования приложения при каждом изменении кода, чтобы быстро обнаруживать ошибки и поддерживать стабильность проекта.

* Для чего нужны юнит-тесты? Как часто их нужно запускать?

Юнит-тесты проверяют отдельные модули программы на корректность. Их следует запускать при каждом изменении кода, желательно автоматически (через CI).

* Что нужно изменить в файле `.github/workflows/main.yml` для того, чтобы тесты запускались при каждом создании запроса на слияние (Pull Request)?

Чтобы тесты запускались при каждом создании запроса на слияния необходимо добавить в блок кода `on`

```yml
on:
  push:
    branches:
      - main
  pull_request:
    branches:
      - main
 ```

* Что нужно добавить в файл .github/workflows/main.yml для того, чтобы удалять созданные образы после выполнения тестов?

Для того, чтобы удалять созданные образы после выполнения тестов необходимо добавить в файл следующий шаг:

```yml
- name: Remove Docker image
  run: docker rmi containers08
```

## Выводы

В ходе лабораторной работы я:

* Освоил основы CI с использованием Github Actions.
* Реализовал приложение на PHP с SQLite.
* Разработал тесты и выполнил автоматизацию тестирования через Docker.
* Понял значение юнит-тестов и практики CI в профессиональной разработке.

## Библиография

* [Repository by M.Croitor](https://github.com/mcroitor/app_containerization_ru/commits?author=mcroitor)
* [GitHub Docs. *"About GitHub Actions"*](https://docs.github.com/en/actions)
* [Docker Docs](https://docs.docker.com/get-started/)
* [SQLite Documentation](https://www.sqlite.org/index.html)
