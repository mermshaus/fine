<?php

if (
    (!$loader = @include __DIR__ . '/../../../autoload.php')
    && (!$loader = @include __DIR__ . '/../vendor/autoload.php')
) {
    die("You have to set up the project dependencies via Composer:\n"
        . "\$ composer install\n");
}
