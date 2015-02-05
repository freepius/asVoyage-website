<?php

/* TODO: Keep in production ? */
ini_set('display_errors', 1);
error_reporting(-1);

require_once __DIR__.'/../src/bootstrap.php';

$app->run();
