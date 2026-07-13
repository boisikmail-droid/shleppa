<?php

return [
    'APP_ENV' => $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'dev',
    'APP_DEBUG' => (bool) ($_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true),
];
