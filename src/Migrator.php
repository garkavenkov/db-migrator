<?php

namespace DB\Migration;

use DBConnector\DBConnect;

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

    public function status()
    {
        echo "List migration status\n";
      
        $completedMigrations = $this->getCompletedMigrations();
       
        
        // $availableMigrations = $this->getAvaiableMigrations();
        $pendingMigrations = $this->getPendingMigration();
        var_dump($completedMigrations);
        var_dump($pendingMigrations);
        exit;
        return array_unique(array_merge($availableMigrations, $completedMigrations));
    }

    public function init()
    {

    }

    /**
     * Undocumented function
     *
     * @return array
     */
    private function getCompletedMigrations(): array
    {        
        $sql = "SELECT * FROM `migrations`";       
        return  $this->dbh->query($sql)->getRows(); //getFieldValues('name');
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    private function getAvaiableMigrations(): array
    {
        $res = [];
        $migrationClasses = array_values(array_diff(scandir(constant("MIGRATIONS_DIR")),array('.', '..')));
        foreach ($migrationClasses as $className) {
            $res[] = pathinfo($className, PATHINFO_FILENAME);
        }       
        return $res;
    }


    /**
     * Undocumented function
     *
     * @return void
     */
    private function getPendingMigration() : array
    {  
        // echo "function getPendingMigration()....\n";
        $completedMigrations = array_map(fn($m) => $m['name']
            // echo "Completed migration - ". $m['name'] . PHP_EOL;
            // return $m['name'];
        , $this->getCompletedMigrations());
        var_dump(array_diff($this->getAvaiableMigrations(), $completedMigrations));
        exit;
        
        // return array_diff($this->getAvaiableMigrations(), $this->getCompletedMigrations());
        return array_diff($this->getAvaiableMigrations(), $completedMigrations);
    }

    /**
     * Rollback database migration
     *
     * @param integer $steps    
     * @return void
     */
    public function rollback(int $steps=0)
    {
        // $completedMigrations = array_reverse($this->getCompletedMigrations());
        $completedMigrations = $this->getCompletedMigrations();
        print_r($completedMigrations);
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

    private function loadMigration(string $className) 
    {
        return require(constant("MIGRATIONS_DIR") . "/" . $className. '.php');
    }

    
    public function migrate()
    {
        $pendingMigrations = [];
        $pendingMigrations = $this->getPendingMigration() ?? [];

        foreach($pendingMigrations as $migration) {
            // echo "Migrate $migration ... ";
            $m = $this->loadMigration($migration);
            $m->up();
            echo " -> done\n";
            $this->registerMigration($migration);
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

    private function registerMigration(string $migration)
    {
        $sql = "INSERT INTO 'migrations' (`name` ) VALUES (:name)";
        $id = $this->dbh->prepare($sql)->execute([':name' => $migration])->getLastInsertedId();
        echo "Last inserted ID is $id\n";
    }

    private function unregisterMigration(string $migration)
    {
        $sql = "DELETE FROM 'migrations' WHERE name = :name";
        $id = $this->dbh->prepare($sql)->execute([':name' => $migration])->rowCount();
        echo "Last inserted ID is $id\n";
    }
}