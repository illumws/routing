<?php

namespace App\Controllers;

class Bar {
    static $val = true;

    public function val(){
        self::$val = false;
    }
}