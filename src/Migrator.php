<?php

namespace DB\Migrator;

class Migrator
{
    protected string $db_driver = '';

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