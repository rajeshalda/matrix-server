<?php

use XF\Pub\App;
use XF\Style;
use XF\WebManifestRenderer;

$dir = __DIR__;
require $dir . '/src/XF.php';

\XF::start($dir);
$app = \XF::setupApp(App::class);

/** @var WebManifestRenderer $renderer */
$renderer = $app['webManifestRenderer'];

$style = $app->style(0);
if ($style->isVariationsEnabled())
{
	$style->setVariation(Style::VARIATION_DEFAULT);
}

$language = $app->language(0);

$response = $renderer->render($style, $language);

$request = $app->request();
$response->send($request);
