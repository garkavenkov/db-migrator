<?php

namespace DB\Migration;

class Migrator
{
    private $db_driver = '';

    private $completedMigrations = ['2026_01_26_205723_create_roles_table.php'];
    // private $pendingMigrations = [];

    public function __construct()
    {
        if (defined('DB_DRIVER')) {
            $this->db_driver = constant('DB_DRIVER');            
        }        
    }

    public function status()
    {
        echo "List migration status\n";
    }

    public function init()
    {

    }

    private function getCompletedMigrations()
    {
        // select from migrations table;
        $this->completedMigrations = [];
    }

    public function getPendingMigration() 
    {
        $migrationFiles =array_values(array_diff(scandir(MIGRATIONS_DIR), array('.', '..')));

        return array_diff($migrationFiles, $this->completedMigrations);

        // foreach($migrations as $migration) {
        //     $t = require(MIGRATIONS_DIR . "/" . $migration);
        //     $t->up();
        // }
    }

    /**
     * Rollback database migration
     *
     * @param integer $steps    
     * @return void
     */
    public function rollback(int $steps=0)
    {
        if ($steps == 0) {
            // rollback all migrations
        }

    }


    public function migrate()
    {
        $pendingMigrations = $this->getPendingMigration();

        foreach($pendingMigrations as $migration) {
            // echo "Migrate $migration ... ";
            $t = require(MIGRATIONS_DIR . "/" . $migration);
            $t->up();
            echo " -> done\n";
        }
    }
    /**
     * Return Database driver
     *
     * @return string
     */
    public function getDatabaseEngine(): string
    {
        return $this->db_driver;
    }
}