<?php

namespace Slc\SeoLinksCrawler\Container; 

class DependencyInjectionContainer {
    private $services = [];

    public function register($key, $class) {
        $this->services[$key] = $class;
    }

    public function get($key) {
        $args = array_slice(func_get_args(), 1); // Get the arguments starting from the second argument
        if (isset($this->services[$key])) {
            $class = $this->services[$key];
            $reflectionClass = new \ReflectionClass($class);
            return $reflectionClass->newInstanceArgs($args);
        }
        return null;
    }
}