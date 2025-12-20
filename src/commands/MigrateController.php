<?php
/**
 * Created by PhpStorm.
 * User: aleksey
 * Date: 25.10.16
 * Time: 13:35
 */

namespace commands;

use yii\console\Exception;
use yii\console\ExitCode;
use yii\helpers\Console;
use yii\helpers\FileHelper;

class MigrateController extends \yii\console\controllers\MigrateController
{
    public function actionCreate($name)
    {
        if (!preg_match('/^[\w\\\\]+$/', $name)) {
            throw new Exception('The migration name should contain letters, digits, underscore and/or backslash characters only.');
        }

        [$namespace, $className] = $this->generateClassName($name);
        // Abort if name is too long
        $nameLimit = $this->getMigrationNameLimit();
        if ($nameLimit !== null && strlen($className) > $nameLimit) {
            throw new Exception('The migration name is too long.');
        }

        $migrationPath = $this->findMigrationPath($namespace);

        $file = $migrationPath . DIRECTORY_SEPARATOR . $className . '.php';
        if ($this->confirm("Create new migration '$file'?")) {
            $content = $this->generateMigrationSourceCode([
                  'name' => $name,
                  'className' => $className,
                  'namespace' => $namespace,
              ]);
            FileHelper::createDirectory($migrationPath);
            if (file_put_contents($file, $content, LOCK_EX) === false) {
                $this->stdout("Failed to create new migration.\n", Console::FG_RED);

                return ExitCode::IOERR;
            }

            $this->stdout("New migration created successfully.\n", Console::FG_GREEN);
        }

        return ExitCode::OK;
    }

    private function generateClassName($name)
    {
        $name = trim($name, '\\');
        $class = 'm' . gmdate('ymd_His') . '_' . $name;

        return [null, $class];
    }

    private function findMigrationPath($namespace)
    {
        return end($this->migrationPath);
    }
}