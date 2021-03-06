<?php

namespace Tests;

use App\Http\Middleware\Traits\ResponseCache;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use ResponseCache;

    public function setUp(): void
    {
        parent::setUp();
        $this->useTableWithData('airlines');
        $this->useTableWithData('countries');
        $this->useTableWithData('cities');
        $this->useTableWithData('airports');
        $this->useTableWithData('aircrafts');
    }

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        if (!defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        parent::__construct($name, $data, $dataName);
    }

    protected function useTable(string $table)
    {
        $mainDb = config('database.connections.mysql.main_database');
        DB::statement("DROP TABLE IF EXISTS $table");
        DB::statement("CREATE TABLE $table LIKE $mainDb.$table");
    }

    protected function useTableWithData(string $table, $where = null)
    {
        $this->useTable($table);
        $mainDb = config('database.connections.mysql.main_database');
        DB::statement("INSERT INTO $table SELECT * FROM $mainDb.$table" . ($where ? ' WHERE ' . $where : ''));
    }
}
