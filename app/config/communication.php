<?php

$dbName = getenv('DATABASE_NAME') ?: (getenv('APP_ENV') === 'test' ? 'teste' : 'applications');

return [
    'host'  =>  getenv('DATABASE_HOST') ?: "postgres",
    'port'  =>  getenv('DATABASE_PORT') ?: "5432",
    'name'  =>  $dbName,
    'user'  =>  getenv('DATABASE_USER') ?: "postgres",
    'pass'  =>  getenv('DATABASE_PASS') ?: "postgres",
    'type'  =>  "pgsql",
    'prep'  =>  "1"
];
