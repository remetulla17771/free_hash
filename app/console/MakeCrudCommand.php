<?php
declare(strict_types=1);

namespace app\console;

use app\Db;
use RuntimeException;

class MakeCrudCommand implements CommandInterface
{
    public function name(): string { return 'make:crud'; }
    public function description(): string { return 'Generate CRUD controller + views from DB table (PHP 7.4)'; }

    public function execute(Input $in, Output $out): int
    {
        $modelShort = (string)$in->arg(0, '');
        if ($modelShort === '') {
            $out->line("Usage: php bin/console.php make:crud Post --table=post [--controller=Post] [--force]");
            return 1;
        }

        $force = $in->has('force');

        $modelNamespace = (string)$in->opt('modelNamespace', 'app\\models');
        $modelClass = (strpos($modelShort, '\\') !== false) ? $modelShort : ($modelNamespace . '\\' . $modelShort);

        // controller name/id
        $controllerBase = (string)$in->opt('controller', $modelShort);
        $controllerBase = preg_replace('/Controller$/', '', $controllerBase);
        $controllerClass = $controllerBase . 'Controller';
        $controllerId = strtolower($controllerBase);

        // table
        $table = (string)$in->opt('table', '');
        if ($table === '') {
            if (!class_exists($modelClass)) {
                $out->err("Model class not found: $modelClass. Pass --table=...");
                return 1;
            }
            $table = (string)call_user_func([$modelClass, 'tableName']);
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            $out->err("Bad table name: $table");
            return 1;
        }

        // schema
        $pdo = Db::getInstance();
        $stmt = $pdo->query("DESCRIBE `$table`");
        $cols = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        if (!$cols) {
            $out->err("Table not found or empty schema: $table");
            return 1;
        }

        $pk = $this->detectPrimaryKey($cols);

        $root = dirname(__DIR__, 2); // .../app/console -> project root
        $controllerFile = $root . DIRECTORY_SEPARATOR . "app/controllers/{$controllerClass}.php";

        $viewsDir = $root . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . $controllerId;
        $viewIndex  = $viewsDir . DIRECTORY_SEPARATOR . "index.php";
        $viewView   = $viewsDir . DIRECTORY_SEPARATOR . "view.php";
        $viewCreate = $viewsDir . DIRECTORY_SEPARATOR . "create.php";
        $viewUpdate = $viewsDir . DIRECTORY_SEPARATOR . "update.php";
        $viewForm   = $viewsDir . DIRECTORY_SEPARATOR . "_form.php";

        // controller code
        $controllerCode = $this->buildControllerCode($controllerClass, $modelClass, $controllerId, $pk);

        // views code
        $indexCode  = $this->buildIndexView($modelClass, $controllerId, $pk, $cols);
        $viewCode   = $this->buildViewView($modelClass, $controllerId, $pk, $cols);
        $createCode = $this->buildCreateView($modelClass);
        $updateCode = $this->buildUpdateView($modelClass);
        $formCode   = $this->buildFormView($cols, $pk);

        // write
        $this->writeFile($controllerFile, $controllerCode, $force);

        if (!is_dir($viewsDir)) mkdir($viewsDir, 0777, true);
        $this->writeFile($viewIndex,  $indexCode,  $force);
        $this->writeFile($viewView,   $viewCode,   $force);
        $this->writeFile($viewCreate, $createCode, $force);
        $this->writeFile($viewUpdate, $updateCode, $force);
        $this->writeFile($viewForm,   $formCode,   $force);

        $out->line("OK: $controllerFile");
        $out->line("OK: $viewsDir");
        return 0;
    }

    private function detectPrimaryKey(array $cols): string
    {
        foreach ($cols as $c) {
            if (($c['Key'] ?? '') === 'PRI') return (string)$c['Field'];
        }
        foreach ($cols as $c) {
            if (($c['Field'] ?? '') === 'id') return 'id';
        }
        return (string)($cols[0]['Field'] ?? 'id');
    }

    private function buildControllerCode(string $controllerClass, string $modelClass, string $controllerId, string $pk): string
    {
        return "<?php\n" .
            "namespace app\\controllers;\n\n" .
            "use app\\Controller;\n" .
            "use app\\helpers\\Alert;\n" .
            "use {$modelClass};\n\n" .
            "class {$controllerClass} extends Controller\n" .
            "{\n" .
            "    public function actionIndex()\n" .
            "    {\n" .
            "        \$models = {$this->short($modelClass)}::find()->all();\n" .
            "        return \$this->render('index', ['models' => \$models]);\n" .
            "    }\n\n" .
            "    public function actionView(\$id)\n" .
            "    {\n" .
            "        \$model = {$this->short($modelClass)}::find()->where(['{$pk}' => (int)\$id])->one();\n" .
            "        if (!\$model) throw new \\Exception('Not found', 404);\n" .
            "        return \$this->render('view', ['model' => \$model]);\n" .
            "    }\n\n" .
            "    public function actionCreate()\n" .
            "    {\n" .
            "        \$model = new {$this->short($modelClass)}();\n" .
            "        if (\$this->request->isPost() && \$model->load(\$this->request->post())) {\n" .
            "            \$model->save();\n" .
            "            Alert::add('success', 'Created');\n" .
            "            return \$this->redirect(['{$controllerId}/index']);\n" .
            "        }\n" .
            "        return \$this->render('create', ['model' => \$model]);\n" .
            "    }\n\n" .
            "    public function actionUpdate(\$id)\n" .
            "    {\n" .
            "        \$model = {$this->short($modelClass)}::find()->where(['{$pk}' => (int)\$id])->one();\n" .
            "        if (!\$model) throw new \\Exception('Not found', 404);\n" .
            "        if (\$this->request->isPost() && \$model->load(\$this->request->post())) {\n" .
            "            \$model->save();\n" .
            "            Alert::add('success', 'Updated');\n" .
            "            return \$this->redirect(['{$controllerId}/view', 'id' => \$model->{$pk}]);\n" .
            "        }\n" .
            "        return \$this->render('update', ['model' => \$model]);\n" .
            "    }\n\n" .
            "    public function actionDelete(\$id)\n" .
            "    {\n" .
            "        \$model = {$this->short($modelClass)}::find()->where(['{$pk}' => (int)\$id])->one();\n" .
            "        if (\$model) {\n" .
            "            \$model->delete();\n" .
            "            Alert::add('warning', 'Deleted');\n" .
            "        } else {\n" .
            "            Alert::add('danger', 'Not found');\n" .
            "        }\n" .
            "        return \$this->redirect(['{$controllerId}/index']);\n" .
            "    }\n" .
            "}\n";
    }

    private function buildIndexView(string $modelClass, string $controllerId, string $pk, array $cols): string
    {
        $modelShort = $this->short($modelClass);
        $fields = $this->pickIndexColumns($cols, $pk, 6);

        $columnsPhp = "";
        foreach ($fields as $f) {
            $columnsPhp .= "        '{$f}',\n";
        }

        // Action column (без data-confirm, потому что твой Html::a там опасно сделан)
        $columnsPhp .=
            "        [\n" .
            "            'label' => 'Action',\n" .
            "            'value' => function (\$data) {\n" .
            "                \$id = \$data->{$pk};\n" .
            "                return Html::a('View', ['/$controllerId/view', 'id' => \$id], ['class' => 'btn btn-success btn-sm'])\n" .
            "                    . ' ' . Html::a('Update', ['/$controllerId/update', 'id' => \$id], ['class' => 'btn btn-warning btn-sm'])\n" .
            "                    . ' ' . Html::a('Delete', ['/$controllerId/delete', 'id' => \$id], ['class' => 'btn btn-danger btn-sm', 'onclick' => \"return confirm('Delete?');\"]);\n" .
            "            }\n" .
            "        ],\n";

        return "<?php\n" .
            "/** @var \$models {$modelClass}[] */\n\n" .
            "use app\\helpers\\GridView;\n" .
            "use app\\helpers\\Html;\n\n" .
            "\$this->title = '{$modelShort} list';\n" .
            "?>\n\n" .
            "<h1><?= Html::encode(\$this->title) ?></h1>\n\n" .
            "<p>\n" .
            "    <?= Html::a('Create {$modelShort}', ['/$controllerId/create'], ['class' => 'btn btn-primary']) ?>\n" .
            "</p>\n\n" .
            "<?= GridView::widget([\n" .
            "    'dataProvider' => \$models,\n" .
            "    'columns' => [\n" .
            $columnsPhp .
            "    ]\n" .
            "]); ?>\n";
    }

//    private function buildViewView(string $modelClass, string $controllerId, string $pk, array $cols): string
//    {
//        $rows = "";
//        foreach ($cols as $c) {
//            $f = (string)($c['Field'] ?? '');
//            if ($f === '') continue;
/*            $rows .= "<tr><th>{$f}</th><td><?= Html::encode(\$model->{$f}) ?></td></tr>\n";*/
//        }
//
//        return "<?php\n" .
//            "/** @var \$model {$modelClass} */\n\n" .
//            "use app\\helpers\\Html;\n\n" .
//            "\$this->title = 'View';\n" .
/*            "?>\n\n" .*/
/*            "<h1><?= Html::encode(\$this->title) ?></h1>\n\n" .*/
//            "<p>\n" .
/*            "    <?= Html::a('Back', ['/$controllerId/index'], ['class' => 'btn btn-secondary']) ?>\n" .*/
/*            "    <?= Html::a('Update', ['/$controllerId/update', 'id' => \$model->{$pk}], ['class' => 'btn btn-warning']) ?>\n" .*/
/*            "    <?= Html::a('Delete', ['/$controllerId/delete', 'id' => \$model->{$pk}], ['class' => 'btn btn-danger', 'onclick' => \"return confirm('Delete?');\"]) ?>\n" .*/
//            "</p>\n\n" .
//            "<table class=\"table table-bordered\">\n" .
//            $rows .
//            "</table>\n";
//    }

    private function buildViewView(string $modelClass, string $controllerId, string $pk, array $cols): string
    {
        $attrs = "";
        foreach ($cols as $c) {
            $f = (string)($c['Field'] ?? '');
            if ($f === '') continue;
            $attrs .= "        '{$f}',\n";
        }

        return "<?php\n" .
            "/** @var \$model {$modelClass} */\n\n" .
            "use app\\helpers\\Html;\n" .
            "use app\\helpers\\DetailView;\n\n" .
            "\$this->title = 'View';\n" .
            "?>\n\n" .
            "<h1><?= Html::encode(\$this->title) ?></h1>\n\n" .
            "<p>\n" .
            "    <?= Html::a('Back', ['/$controllerId/index'], ['class' => 'btn btn-secondary']) ?>\n" .
            "    <?= Html::a('Update', ['/$controllerId/update', 'id' => \$model->{$pk}], ['class' => 'btn btn-warning']) ?>\n" .
            "    <?= Html::a('Delete', ['/$controllerId/delete', 'id' => \$model->{$pk}], ['class' => 'btn btn-danger', 'onclick' => \"return confirm('Delete?');\"]) ?>\n" .
            "</p>\n\n" .
            "<?= DetailView::widget([\n" .
            "    'model' => \$model,\n" .
            "    'attributes' => [\n" .
            $attrs .
            "    ],\n" .
            "]); ?>\n";
    }


    private function buildCreateView(string $modelClass): string
    {
        $short = $this->short($modelClass);
        return "<?php\n" .
            "/** @var \$model {$modelClass} */\n" .
            "use app\helpers\Html; \n".
            "\$this->title = 'Create {$short}';\n" .
            "?>\n\n" .
            "<h1><?= Html::encode(\$this->title) ?></h1>\n\n" .
            "<?= \$this->renderPartial('_form', ['model' => \$model, 'button' => 'Create']); ?>\n";
    }

    private function buildUpdateView(string $modelClass): string
    {
        $short = $this->short($modelClass);
        return "<?php\n" .
            "/** @var \$model {$modelClass} */\n" .
            "use app\helpers\Html; \n".
            "\$this->title = 'Update {$short}';\n" .
            "?>\n\n" .
            "<h1><?= Html::encode(\$this->title) ?></h1>\n\n" .
            "<?= \$this->renderPartial('_form', ['model' => \$model, 'button' => 'Save']); ?>\n";
    }

    private function buildFormView(array $cols, string $pk): string
    {
        $fields = [];
        foreach ($cols as $c) {
            $f = (string)($c['Field'] ?? '');
            if ($f === '' || $f === $pk) continue;

            // не трогаем автоинкремент, даже если pk не id
            $extra = strtolower((string)($c['Extra'] ?? ''));
            if (strpos($extra, 'auto_increment') !== false) continue;

            $type = $this->guessInputType($f, (string)($c['Type'] ?? ''));
            $opt = ($type !== 'text') ? ", ['type' => '{$type}']" : "";
            $fields[] = "<?= ActiveForm::field(\$model, '{$f}'{$opt}) ?>";
        }

        $fieldsStr = implode("\n", $fields);

        return "<?php\n" .
            "use app\\helpers\\ActiveForm;\n\n" .
            "\$button = isset(\$button) ? \$button : 'Save';\n" .
            "?>\n\n" .
            "<?php ActiveForm::begin('post'); ?>\n\n" .
            $fieldsStr . "\n\n" .
            "<?= ActiveForm::submitButton(\$button, ['class' => 'btn btn-primary mt-3']) ?>\n\n" .
            "<?php ActiveForm::end(); ?>\n";
    }

    private function guessInputType(string $field, string $sqlType): string
    {
        $f = strtolower($field);
        $t = strtolower($sqlType);

        if (strpos($f, 'password') !== false) return 'password';
        if (strpos($f, 'email') !== false) return 'email';

        if (strpos($t, 'date') !== false && strpos($t, 'datetime') === false) return 'date';
        if (strpos($t, 'datetime') !== false || strpos($t, 'timestamp') !== false) return 'datetime-local';

        return 'text';
    }

    private function pickIndexColumns(array $cols, string $pk, int $max): array
    {
        $out = [];
        // pk первым
        $out[] = $pk;

        foreach ($cols as $c) {
            $f = (string)($c['Field'] ?? '');
            if ($f === '' || $f === $pk) continue;

            $type = strtolower((string)($c['Type'] ?? ''));
            // длинные тексты в гриде обычно не нужны
            if (strpos($type, 'text') !== false || strpos($type, 'blob') !== false) continue;

            $out[] = $f;
            if (count($out) >= $max) break;
        }

        return array_values(array_unique($out));
    }

    private function short(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');
        return ($pos === false) ? $fqcn : substr($fqcn, $pos + 1);
    }

    private function writeFile(string $path, string $content, bool $force): void
    {
        if (file_exists($path) && !$force) {
            throw new RuntimeException("File exists: {$path} (use --force)");
        }
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        file_put_contents($path, $content);
    }
}
