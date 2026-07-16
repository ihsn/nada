<?php

spl_autoload_register(static function ($class) {
    $namespace = 'avadim\\FastExcelHelper\\';
    if (0 === strpos($class, $namespace)) {
        include __DIR__ . '/FastExcelHelper/' . str_replace($namespace, '', $class) . '.php';
    }
});

// EOF