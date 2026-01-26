<?php

namespace DB\Migration;

abstract class Migration
{
    abstract public function up();

    abstract public function down();
}