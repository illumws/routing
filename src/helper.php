<?php

if(function_exists('route')){

    /**
     * retorna uma instancia do router
     * @return Closure|mixed|object|null
     */
    function route(){
        return \Illuminate\Container\Container::getInstance()->get('router');
    }
}