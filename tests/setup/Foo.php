<?php

namespace App\Controllers;

class Foo {
    protected Bar $bar;
    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }

    public function valFromBar(){
        $this->bar->val();
    }
}