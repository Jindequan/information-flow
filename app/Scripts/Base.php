<?php

namespace App\Scripts;

abstract class Base {
    abstract public function run($params);
    abstract public function showParams();
}