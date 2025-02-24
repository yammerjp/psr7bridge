<?php

// CMS_USER=user CMS_PASS=pass php -S localhost:9123 ./index.php

require_once __DIR__ . '/vendor/autoload.php';

use Yammerjp\Psr7bridgeexample\App;

define('APP_DIR', __DIR__);

$app = new App();
$app->run();
