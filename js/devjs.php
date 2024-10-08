<?php

use XF\Pub\App;

$dir = __DIR__ . '/..';
require $dir . '/src/XF.php';

\XF::start($dir);
$app = \XF::setupApp(App::class);

$request = $app->request();

$addOnId = $request->filter('addon_id', 'str');
$jsPath = $request->filter('js', 'str');

$jsResponse = $app->developmentJsResponse();
$response = $jsResponse->run($jsPath, $addOnId);

$response->send($request);
