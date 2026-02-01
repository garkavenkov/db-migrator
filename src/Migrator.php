<?php

namespace DB\Migration;

use DBConnector\DBConnect;
use Error;

class Migrator
{
    private $db_driver = '';
    private $dbh;

    private $completedMigrations = [];
    // private $pendingMigrations = [];

    public function __construct(DBConnect $dbh)
    {
        if (defined('DB_DRIVER')) {
            $this->db_driver = constant('DB_DRIVER');            
        }       
        $this->dbh = $dbh; 
    }

    /**
     * Return Database driver
     *
     * @return string   Database driver (e.g. mysql, sqlite, etc.)
     */
    public function getDatabaseEngine(): string
    {
        return $this->db_driver;
    }

    /**
     * Register migration (insert record into migrations table)
     *
     * @param string $migration Migration's class name
     * @return int              Registered migration's ID
     */
    private function registerMigration(string $migration): int
    {
        $sql = "INSERT INTO 'migrations' (`name` ) VALUES (:name)";
        return $this->dbh->prepare($sql)->execute([':name' => $migration])->getLastInsertedId();
        
    }

    /**
     * Unregister migration (i.e. delete record from migrations table)
     *
     * @param string $migration Migration's class name
     * @return integer          1 if operation was successfull, 0 - otherwise
     */
    private function unregisterMigration(string $migration): int
    {
        $sql = "DELETE FROM 'migrations' WHERE name = :name";
        return $this->dbh->prepare($sql)->execute([':name' => $migration])->rowCount();        
    }

    /**
     * Load migration class and return Migration::class instance
     *
     * @param string $className Migration name
     * @return Migration
     */
    private function loadMigration(string $className): Migration
    {
        return require(constant("MIGRATIONS_DIR") . "/" . $className. '.php');
    }

    /**
     * Create migrations table
     *
     * @return void
     */
    public function init()
    {        
        $sql = '';
        echo $this->db_driver . PHP_EOL;
        if ($this->db_driver === 'sqlite') {
            $databaseName = ROOT_DIR . '/test/database.sqlite';
            echo $databaseName . PHP_EOL;
            if (!file_exists($databaseName)) {
                try {                    
                    if (touch($databaseName)) {
                        echo "$databaseName was successfuly created\n";
                    } else {
                        throw new Error("Cannot create $databaseName");
                    }
                    
                } catch (\Throwable $th) {
                    die($th->getMessage());
                }
            }
            
            $sql  = "CREATE TABLE 'migrations' (";
            $sql .= "   'id'	INTEGER NOT NULL,";
            $sql .= "   'name'	TEXT NOT NULL UNIQUE,";
            $sql .= "   'created_at' TIME DEFAULT CURRENT_TIMESTAMP ,";
            $sql .= "   PRIMARY KEY('id' AUTOINCREMENT)";
            $sql .= ")";
        }
        echo "SQL - $sql";
        if ($sql != '') {
            $res = $this->dbh->exec($sql);
            echo $res . PHP_EOL;
        }
    }

    /**
     * Display migrations status (i.e.  completed migrations and pending migrations)
     *
     * @return void
     */
    public function status()
    {
        echo "List migration status\n";
      
        $completedMigrations = $this->getCompletedMigrations();
        
        $pendingMigrations = $this->getPendingMigration();
        
        
        // return array_unique(array_merge($availableMigrations, $completedMigrations));
    }

   

    /**
     * Get completed migrations
     *
     * @return array
     */
    private function getCompletedMigrations(): array
    {        
        $sql = "SELECT * FROM `migrations`";       
        return  $this->dbh->query($sql)->getRows(); //getFieldValues('name');
    }

    /**
     * Get available migrations (i.e. migration class in MIGRATIONS_DIR)
     *
     * @return array
     */
    private function getAvailableMigrations(): array
    {
        $res = [];
        $migrationClasses = array_values(array_diff(scandir(constant("MIGRATIONS_DIR")),array('.', '..')));
        foreach ($migrationClasses as $className) {
            $res[] = pathinfo($className, PATHINFO_FILENAME);
        }       
        return $res;
    }


    /**
     * Get pending migrations
     *
     * @return void
     */
    private function getPendingMigration() : array
    {          
        $completedMigrations = array_map(fn($m) => $m['name'], $this->getCompletedMigrations());               
        
        // return array_diff($this->getAvaiableMigrations(), $this->getCompletedMigrations());
        return array_diff($this->getAvailableMigrations(), $completedMigrations);
    }

    /**
     * Rollback database migration
     *
     * @param integer $steps    How many steps need to rollback. 0 - rollback all migration.
     * @return void
     */
    public function rollback(int $steps=0)
    {        
        $completedMigrations = $this->getCompletedMigrations();
        
        if (count($completedMigrations) > 0) {
            if ($steps == 0) {
                // rollback  all migrations
                foreach($completedMigrations as $migration) {
                    $m = $this->loadMigration($migration);
                    echo "Rollback ";
                    $m->down();
                    $this->unregisterMigration($migration);
                    echo " -> done\n";
                }
            } else {
                for($step = 1; $step <= $steps; $step++) {
                    $migration = array_pop($completedMigrations);
                    $m = $this->loadMigration($migration);
                    echo "Rollback ";
                    $m->down();
                    $this->unregisterMigration($migration);
                    echo " -> done\n";
                }
                    
            }

        } else {
            echo "There is nothing to rollback\n";
        }

    }

    /**
     * Migrate database. 
     *
     * @param string|null $className Migration name. If null migrate all pending migrations
     * @return void
     */
    public function migrate(?string $className = null)
    {
        if (is_null($className)) {
            $pendingMigrations = [];
            $pendingMigrations = $this->getPendingMigration() ?? [];
                
            foreach($pendingMigrations as $migration) {
                // echo "Migrate $migration ... ";
                $m = $this->loadMigration($migration);
                $m->up();
                echo " -> done\n";
                $this->registerMigration($migration);
            }
        } else {
            try {
                $m = $this->loadMigration($className)->up();                
                echo " -> done\n";
                $this->registerMigration($className);                
            } catch (\Throwable $th) {
                echo $th->getMessage();
            }
        }
    }

}