<?php

namespace Adminerng\Drivers\MySql;

use Adminerng\Core\AbstractDriver;
use Adminerng\Drivers\Redis\Forms\MySqlItemForm;
use PDO;

class MySqlDriver extends AbstractDriver
{
    const TYPE_TABLE = 'table';
    const TYPE_VIEW = 'view';

    private $type;

    private $columns = null;

    public function check()
    {
        return extension_loaded('pdo_mysql');
    }

    public function type()
    {
        return 'mysql';
    }
    
    public function defaultCredentials()
    {
        return [
            'server' => 'localhost:3306'
        ];
    }

    protected function getCredentialsForm()
    {
        return new MySqlForm();
    }

    public function connect(array $credentials)
    {
        $host = $credentials['server'];
        $port = '3306';
        if (strpos($credentials['server'], ':') !== false) {
            list($host, $port) = explode(':', $credentials['server'], 2);
        }
        $dsn = 'mysql:;host=' . $host . ';port=' . $port . ';charset=utf8';
        $this->connection = new PDO($dsn, $credentials['user'], $credentials['password']);
    }

    public function databaseTitle()
    {
        return 'database';
    }

    public function databasesHeaders()
    {
        return [
            'database',
            'charset',
            'collation',
            'tables',
            'size'
        ];
    }

    public function databases()
    {
        $tableSchemas = [];
        foreach ($this->connection->query('SELECT TABLE_SCHEMA, count(*) AS tables_count, SUM(DATA_LENGTH) AS size FROM information_schema.TABLES GROUP BY TABLE_SCHEMA')->fetchAll(PDO::FETCH_ASSOC) as $tableSchema) {
            $tableSchemas[$tableSchema['TABLE_SCHEMA']] = [
                'tables_count' => $tableSchema['tables_count'],
                'size' => $tableSchema['size'],
            ];
        }
        $databases = [];
        foreach ($this->connection->query('SELECT * FROM information_schema.SCHEMATA')->fetchAll(PDO::FETCH_ASSOC) as $database) {
            $databases[$database['SCHEMA_NAME']] = [
                'charset' => $database['DEFAULT_CHARACTER_SET_NAME'],
                'collation' => $database['DEFAULT_COLLATION_NAME'],
                'tables_count' => isset($tableSchemas[$database['SCHEMA_NAME']]['tables_count']) ? $tableSchemas[$database['SCHEMA_NAME']]['tables_count'] : '0',
                'size' => isset($tableSchemas[$database['SCHEMA_NAME']]['size']) ? $tableSchemas[$database['SCHEMA_NAME']]['size'] : '0',
            ];
        }
        return $databases;
    }

    private function selectDatabase($database)
    {
        $this->connection->query('USE `' . $database . '`');
    }

    public function tablesHeaders()
    {
        return [
            self::TYPE_TABLE => ['Table', 'Engine', 'Collation', 'Data length', 'Index length', 'Data free', 'Auto increment', 'Rows'],
            self::TYPE_VIEW => ['View', 'Check option', 'Is updatable', 'Definer', 'Security type', 'Character set', 'Collation'],
        ];
    }

    public function tables($database)
    {
        $tables = [];
        foreach ($this->connection->query("SELECT * FROM information_schema.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA = '$database' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_ASSOC) as $table) {
            $tables[self::TYPE_TABLE][$table['TABLE_NAME']] = [
                $table['ENGINE'],
                $table['TABLE_COLLATION'],
                number_format($table['DATA_LENGTH'], 0),
                $table['INDEX_LENGTH'],
                number_format($table['DATA_FREE'], 0, ',', ' '),
                $table['AUTO_INCREMENT'],
                $table['TABLE_ROWS'],
            ];
        }
        foreach ($this->connection->query("SELECT * FROM information_schema.VIEWS WHERE TABLE_SCHEMA = '$database' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_ASSOC) as $view) {
            $tables[self::TYPE_VIEW][$view['TABLE_NAME']] = [
                $view['CHECK_OPTION'],
                $view['IS_UPDATABLE'],
                $view['DEFINER'],
                $view['SECURITY_TYPE'],
                $view['CHARACTER_SET_CLIENT'],
                $view['COLLATION_CONNECTION'],
            ];
        }
        return $tables;
    }

    public function itemsHeaders($type)
    {
        $columns = $this->getColumns($type, 'this parameter is missing but I dont care');   // TODO fix me
        $headers = [];
        foreach ($columns as $column) {
            $headers[] = $column['Field'];
        }
        return $headers;
    }

    public function itemsTitles($type = null)
    {
        $titles = [
            self::TYPE_TABLE => 'Items',
            self::TYPE_VIEW => 'Items',
        ];
        return $type === null ? $titles : $titles[$type];
    }

    public function itemsCount($database, $type, $table)
    {
        $this->selectDatabase($database);
        $query = 'SELECT count(*) FROM `' . $table . '`';
        return $this->connection->query($query)->fetch(PDO::FETCH_COLUMN);
    }
    
    public function items($database, $type, $table, $page, $onPage)
    {
        $this->type = $type;
        $this->selectDatabase($database);

        $primaryColumns = $this->getPrimaryColumns($type, $table);
        $items = [];
        foreach ($this->connection->query('SELECT * FROM `' . $table . '` LIMIT ' . (($page - 1) * $onPage) . ', ' . $onPage)->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $pk = [];
            foreach ($primaryColumns as $primaryColumn) {
                $pk[] = $item[$primaryColumn];
            }
            $items[md5(implode('|', $pk))] = $item;
        }
        return $items;
    }

    public function itemForm($database, $type, $table, $item)
    {
        $this->selectDatabase($database);
        return new MySqlItemForm($this->connection, $table, $item);
    }

    protected function getPermissions()
    {
        return new MySqlPermissions();
    }

    public function deleteItem($database, $type, $table, $item)
    {
        $this->selectDatabase($database);
        $primaryColumns = $this->getPrimaryColumns($type, $table);
        
        $query = 'DELETE FROM `' . $table . '` WHERE md5(concat(' . implode(', "|", ', $primaryColumns) . ')) = "' . $item . '"';
        return $this->connection->query($query);
    }

    private function getPrimaryColumns($type, $table)
    {
        $primaryColumns = [];
        $columns = [];
        foreach ($this->getColumns($type, $table) as $column) {
            $columns[] = $column['Field'];
            if ($column['Key'] == 'PRI') {
                $primaryColumns[] = $column['Field'];
            }
        }
        if (empty($primaryColumns)) {
            $primaryColumns = $columns;
        }
        return $primaryColumns;
    }

    private function getColumns($type, $table)
    {
        if ($this->columns === null) {
            $this->columns = $this->connection->query('SHOW FULL COLUMNS FROM `' . $table .'`')->fetchAll(PDO::FETCH_ASSOC);
        }
        return $this->columns;
    }
}
