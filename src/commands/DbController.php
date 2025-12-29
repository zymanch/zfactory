<?php

namespace commands;

use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Database query commands
 */
class DbController extends Controller
{
    /**
     * Execute SQL query and display results
     *
     * Usage:
     *   php yii db/query "SELECT * FROM entity_type LIMIT 5"
     *   php yii db/query "SELECT COUNT(*) as total FROM entity"
     *
     * @param string $sql SQL query to execute
     * @return int Exit code
     */
    public function actionQuery($sql)
    {
        if (empty($sql)) {
            $this->stderr("Error: SQL query is required\n");
            return ExitCode::USAGE;
        }

        try {
            $db = \Yii::$app->db;
            $command = $db->createCommand($sql);

            // Determine if it's a SELECT query
            $isSelect = stripos(trim($sql), 'SELECT') === 0;

            if ($isSelect) {
                // For SELECT queries, fetch and display results
                $rows = $command->queryAll();

                if (empty($rows)) {
                    $this->stdout("No results found.\n");
                    return ExitCode::OK;
                }

                // Display as table
                $this->displayTable($rows);
            } else {
                // For INSERT/UPDATE/DELETE, execute and show affected rows
                $affectedRows = $command->execute();
                $this->stdout("Query executed successfully. Rows affected: {$affectedRows}\n");
            }

            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stderr("Error: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Display query results as formatted table
     *
     * @param array $rows Query results
     */
    protected function displayTable($rows)
    {
        if (empty($rows)) return;

        // Get column names from first row
        $columns = array_keys($rows[0]);

        // Calculate column widths
        $widths = [];
        foreach ($columns as $col) {
            $widths[$col] = strlen($col);
        }

        foreach ($rows as $row) {
            foreach ($row as $col => $value) {
                $len = strlen((string)$value);
                if ($len > $widths[$col]) {
                    $widths[$col] = $len;
                }
            }
        }

        // Print header
        $this->printSeparator($widths);
        $this->printRow($columns, $widths, true);
        $this->printSeparator($widths);

        // Print rows
        foreach ($rows as $row) {
            $this->printRow($row, $widths);
        }

        $this->printSeparator($widths);
        $this->stdout("\nTotal rows: " . count($rows) . "\n");
    }

    /**
     * Print table separator line
     *
     * @param array $widths Column widths
     */
    protected function printSeparator($widths)
    {
        $this->stdout('+');
        foreach ($widths as $width) {
            $this->stdout(str_repeat('-', $width + 2) . '+');
        }
        $this->stdout("\n");
    }

    /**
     * Print table row
     *
     * @param array $data Row data
     * @param array $widths Column widths
     * @param bool $isHeader Is this a header row
     */
    protected function printRow($data, $widths, $isHeader = false)
    {
        $this->stdout('|');
        foreach ($data as $col => $value) {
            $key = $isHeader ? $value : $col;
            $displayValue = $isHeader ? $value : (string)$value;
            $width = $widths[$key];
            $this->stdout(' ' . str_pad($displayValue, $width) . ' |');
        }
        $this->stdout("\n");
    }

    /**
     * List all tables in database
     *
     * Usage:
     *   php yii db/tables
     *
     * @return int Exit code
     */
    public function actionTables()
    {
        try {
            $db = \Yii::$app->db;
            $tables = $db->schema->getTableNames();

            $this->stdout("Tables in database:\n\n");
            foreach ($tables as $i => $table) {
                $this->stdout(sprintf("%2d. %s\n", $i + 1, $table));
            }
            $this->stdout("\nTotal: " . count($tables) . " tables\n");

            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stderr("Error: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Describe table structure
     *
     * Usage:
     *   php yii db/describe entity_type
     *   php yii db/describe entity
     *
     * @param string $table Table name
     * @return int Exit code
     */
    public function actionDescribe($table)
    {
        if (empty($table)) {
            $this->stderr("Error: Table name is required\n");
            return ExitCode::USAGE;
        }

        try {
            $db = \Yii::$app->db;
            $schema = $db->getTableSchema($table);

            if ($schema === null) {
                $this->stderr("Error: Table '{$table}' not found\n");
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $this->stdout("Table: {$table}\n\n");

            // Prepare column data
            $rows = [];
            foreach ($schema->columns as $column) {
                $rows[] = [
                    'Field' => $column->name,
                    'Type' => $column->dbType,
                    'Null' => $column->allowNull ? 'YES' : 'NO',
                    'Key' => $column->isPrimaryKey ? 'PRI' : '',
                    'Default' => $column->defaultValue === null ? 'NULL' : $column->defaultValue,
                    'Extra' => $column->autoIncrement ? 'auto_increment' : ''
                ];
            }

            $this->displayTable($rows);

            // Show primary key
            if (!empty($schema->primaryKey)) {
                $this->stdout("\nPrimary Key: " . implode(', ', $schema->primaryKey) . "\n");
            }

            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stderr("Error: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
