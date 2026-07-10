<?php

namespace Adereksisusanto\Laravel\Generator\Contracts;

interface DriverContract
{
    public function getTables();
    public function getColumnDetails($table);
    public function getForeignKeys($table);
}
