# Free Hash

Мини-фреймворк на PHP в стиле Yii2 (упрощённый MVC): `web/index.php → Router → Controller → View/Layout`, простые компоненты из конфига, ActiveRecord/Query, i18n, ассеты, обработка ошибок и логирование.

> Репозиторий: `remetulla17771/free_hash`  
> Лицензия: Apache 2.0 (см. `LICENSE`)

---

## Возможности

- **Front Controller**: единая точка входа `web/index.php`.
- **Routing**: `/{controller}/{action}` + параметры через `$_GET` (Reflection).
- **MVC**: контроллеры в `app/controllers`, view в `views/<controller>`, layout в `views/layouts`.
- **Компоненты** (мини DI-контейнер): создаются из `app/config/web.php` и доступны как свойства `$this->user`, `$this->urlManager`, `$this->lang`, `$this->response`, и т.д.
- **DB**: PDO singleton (`app/Db.php`).
- **ActiveRecord**: базовый CRUD + `Query` (`where()`, `one()`, `all()`).
- **Response**: HTML/JSON/redirect, с `send()`.
- **i18n**: переводы в `app/messages/<lang>/<category>.php` (`ru`, `kk`, `pr`).
- **Ошибки**: `ErrorHandler` ловит exception/error/fatal, пишет логи в `runtime/logs`.

---

## Требования

- PHP **8.0+**
- Apache + `mod_rewrite` (или nginx с rewrite на `web/index.php`)
- MySQL/MariaDB (если используешь модели с БД)

---

## Структура проекта

```
app/
  App.php
  Router.php
  Controller.php
  Request.php
  Response.php
  UrlManager.php
  Db.php
  ActiveRecord.php
  Query.php
  Migration.php
  ErrorHandler.php
  AuthService.php
  config/
    web.php
    db.php
  controllers/
    SiteController.php
    ErrorController.php
    MoreController.php
  console/
    ConsoleApplication.php
    CommandInterface.php
    Input.php
    Output.php
    HelpCommand.php
    MakeControllerCommand.php
    MakeModelCommand.php
    MakeCrudCommand.php
    MakeMigrationCommand.php
    MigrateCommand.php
  helpers/
    I18n.php
    Html.php
    ActiveForm.php
    GridView.php
    DetailView.php
    Alert.php
    Modal.php
    Session.php
    MetaTagManager.php
    NavBar.php
  assets/
    AppAsset.php
    BootstrapAsset.php
    FontAwesomeAsset.php

views/
  layouts/
    main.php
    error.php
    new.php
  site/
    index.php
    view.php
  error/
    error.php
    trace.php

web/
  index.php
  .htaccess
  assets/...

migrations/

runtime/
  logs/
```

---

## Быстрый старт (OpenServer / Apache)

1) Размести проект в домене (например `free_hash.loc`).
2) **DocumentRoot должен быть `.../free_hash/web`** (важно).
3) Проверь, что `.htaccess` работает (mod_rewrite включён).

Открой:
- `http://free_hash.loc/` → по умолчанию `site/index`

### `web/.htaccess`

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

---

## Как работает запрос

1) `web/index.php`:
   - стартует session
   - регистрирует `ErrorHandler`
   - подключает autoload
   - создаёт `new app\App()` и вызывает `run()`

2) `App`:
   - создаёт `Request`, `Router`
   - грузит `app/config/web.php`
   - создаёт компоненты и кладёт их в `$this-><key>`

3) `Router::resolve()`:
   - определяет `controller/action` по сегментам URL
   - вызывает `Controller::actionX(...)` через Reflection
   - если action вернул `Response` → вызывает `send()`

---

## Routing

Формат:

- `/{controller}/{action}`
- параметры action берутся из `$_GET` по имени параметра

Пример:

- `/site/view?id=5` → `SiteController::actionView($id)`

Если в action есть обязательный параметр, а в `$_GET` его нет — будет ошибка **400**.

---

## Контроллеры

Файл: `app/controllers/SiteController.php`  
Класс: `SiteController`  
Методы: `actionIndex()`, `actionView($id)`, и т.д.

Пример:

```php
namespace app\controllers;

use app\Controller;

class SiteController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index', ['title' => 'Hello']);
    }

    public function actionView(int $id)
    {
        return $this->render('view', ['id' => $id]);
    }
}
```

---

## Views и Layouts

- View: `views/<controller>/<view>.php`
- Layout: `views/layouts/main.php` (по умолчанию)

Рендер из контроллера:
- `render($view, $params)` — view + layout
- `renderPartial($view, $params)` — только view

---

## Компоненты и конфиг

`app/config/web.php`:

```php
return [
  'components' => [
    'user' => ['class' => '\app\AuthService'],
    'urlManager' => ['class' => '\app\UrlManager'],
    'response' => ['class' => '\app\Response'],
    'lang' => ['class' => '\app\helpers\I18n'],
    'session' => ['class' => '\app\helpers\Session'],
  ],
];
```

После этого внутри контроллера/вью доступны:
- `$this->user`
- `$this->urlManager`
- `$this->response`
- `$this->lang`
- `$this->session`

---

## i18n (переводы)

Файлы переводов: `app/messages/<lang>/<category>.php`  
Доступные языки в проекте: `ru`, `kk`, `pr`.

Использование:

```php
use app\App;

echo App::$app->t('app', 'Hello');
```

Или напрямую:

```php
use app\helpers\I18n;

echo I18n::t('app', 'Hello');
```

Язык хранится в `$_SESSION['lang']`. В `Router::resolve()` он берётся из:
- `$_GET['lang']` или `$_SESSION['lang']`, иначе `ru`.

---

## База данных

### Настройка

`app/config/db.php`:

```php
return [
  'dsn' => 'mysql:host=localhost;dbname=auth;charset=utf8',
  'user' => 'root',
  'password' => 'root',
];
```

**Важно:** не храни реальные пароли в git. Вынеси в `db.local.php` и добавь в `.gitignore`.

### Db (PDO)

```php
$pdo = \app\Db::getInstance();
```

### ActiveRecord

Модель:

```php
namespace app\models;

use app\ActiveRecord;

class User extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'user';
    }
}
```

Примеры:

```php
$users = User::find()->all();
$user  = User::findOne(5);

$user = new User();
$user->load(['login' => 'admin', 'password' => '123']);
$user->save();

$user->delete();
```

### Query

```php
User::find()->where(['login' => 'admin'])->one();
User::find()->where(['token' => 123])->all();
```

---

## Response (HTML / JSON / Redirect)

Если action возвращает объект `Response`, роутер сам вызовет `send()`.

Пример:

```php
use app\Response;

return Response::json(['ok' => true]);
```

---

## Ошибки и логи

`ErrorHandler` регистрируется в `web/index.php` и ловит:

- исключения (`set_exception_handler`)
- php-ошибки (`set_error_handler`)
- fatal на shutdown (`register_shutdown_function`)

Логи:
- `runtime/logs/error.json`
- `runtime/logs/error.counter`

Страницы ошибок:
- `views/error/error.php`
- `views/error/trace.php`

---

## Безопасность

Сейчас в проекте встречается **plain-text** работа с паролями (простое сравнение строк). Для реального проекта:
- используй `password_hash()` / `password_verify()`
- вынеси секреты (DB, токены) из репозитория
- исключи логи и конфиги из git

Рекомендуемый `.gitignore`:

```gitignore
/vendor/
node_modules/

runtime/logs/
app/config/db.local.php
.env

*.db
```

---

## Известные проблемы (то, что реально стоит исправить)
1) Несостыковка языков:
   - В layout есть переключатель `ru/kk`
   - В `UrlManager::$languages` указаны `ru/en/kz`, а папка переводов — `kk`  
     Рекомендуется привести всё к одному набору (`ru/kk`).

2) Дать 777 права на все файлы
---


## Console Gii (консольный генератор кода)

В проекте есть консольная утилита, которая генерирует заготовки кода “как Yii2 Gii”, но **без веб-интерфейса**:

- `make:controller` — контроллер + папка views
- `make:model` — ActiveRecord модель по таблице
- `make:crud` — CRUD (контроллер + views) по модели/таблице
- `make:migration` — Создает файл для миграцию
- `make:migrate` — Создает таблица на базе данных

### Запуск

Показать список команд:

```bash
php bin/console.php help
```

### make:controller

```bash
php bin/console.php make:controller Site
```

Создаст:
- `app/controllers/SiteController.php`
- `views/site/index.php`

Опции:
- `--force` — перезаписать файлы, если уже существуют.

### make:model

Генерация модели по таблице (используется `DESCRIBE`):

```bash
php bin/console.php make:model User --table=user
```

Создаст:
- `app/models/User.php`


### make:crud

Генерация CRUD по таблице:

```bash
php bin/console.php make:crud Post --table=post
```

Создаст:
- `app/controllers/PostController.php`
- `views/post/index.php`
- `views/post/view.php`
- `views/post/create.php`
- `views/post/update.php`
- `views/post/_form.php`

Опции:
- `--table=...` — **обязательно**
- `--controller=Имя` — имя контроллера (без `Controller`), например `--controller=AdminPost`
- `--modelNamespace=app\models` — где искать модель (по умолчанию `app\models`)
- `--force`

> Важно: `make:model` и `make:crud` требуют рабочее подключение к БД (`Db::getInstance()`), иначе не смогут прочитать схему таблицы.

---


### make:migration

Создаёт файл миграции в папке `migrations/`.

```bash
php bin/console.php make:migration create_user_table
```

Результат: файл вида `migrations/mYYMMDD_HHMMSS_create_user_table.php`.

Опции:
- `--dir=migrations` — папка миграций (по умолчанию `migrations`)
- `--force` — перезаписать файл, если уже существует

### make:migrate

Применяет/откатывает миграции. Состояние хранится в таблице `migration` (по умолчанию).

Применить все новые миграции:

```bash
php bin/console.php migrate
```

Откатить последнюю миграцию:

```bash
php bin/console.php migrate down 1
```

Опции:
- `--dir=migrations` — папка миграций
- `--table=migration` — имя таблицы для учёта применённых миграций

### Базовый класс Migration

Файл: `app/Migration.php`. В миграциях доступны хелперы, чтобы не писать “портянки” SQL:

- `createTable($table, $columns, $options)`
- `dropTable($table)`
- `addColumn($table, $column, $type)`
- `dropColumn($table, $column)`
- `createIndex($name, $table, $columns, $unique=false)`
- `dropIndex($name, $table)`

Типы (строители строк):
- `$this->pk()`, `$this->int()`, `$this->bool()`, `$this->string($len)`, `$this->text()`, `$this->datetime()`, `$this->timestamp()`, `$this->decimal($p,$s)`
- модификаторы: `$this->notNull()`, `$this->defaultValue($v)`, `$this->defaultExpr('CURRENT_TIMESTAMP')`

Пример миграции (создание таблицы `user` + уникальный индекс):

```php
<?php
declare(strict_types=1);

use app\Migration;

class m250217_120000_create_user_table extends Migration
{
    public function up(): void
    {
        $this->createTable('user', [
            'id' => $this->pk(),
            'login' => $this->string(64) . ' ' . $this->notNull(),
            'password_hash' => $this->string(255) . ' ' . $this->notNull(),
            'created_at' => $this->timestamp() . ' ' . $this->notNull() . ' ' . $this->defaultExpr('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('ux_user_login', 'user', 'login', true);
    }

    public function down(): void
    {
        $this->dropIndex('ux_user_login', 'user');
        $this->dropTable('user');
    }
}
```

> Важно: имя класса в твоём файле будет другое (с твоим timestamp). Оставляй имя класса как сгенерировалось, меняй только содержимое `up()` / `down()`.


---


## Лицензия

Apache License 2.0 — см. файл `LICENSE`.
