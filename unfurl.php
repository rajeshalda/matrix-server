<?php

use XF\Entity\UnfurlResult;
use XF\Pub\App;
use XF\Service\Unfurl\FetcherService;

$dir = __DIR__;
require $dir . '/src/XF.php';

\XF::start($dir);
$app = \XF::setupApp(App::class);
$app->start(false);

$request = $app->request();

$templater = $app->templater();
$visitor = \XF::visitor();

if (!$request->isPost())
{
	header('Content-type: text/plain; charset=utf-8', true, 405);
	echo 'This action is available via POST only.';
	return;
}

header('Content-Type: application/json; charset=UTF-8');
header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$resultIds = $request->filter('result_ids', 'array-uint');

if (!$resultIds)
{
	// note: line-break used as a delimiter between responses
	echo json_encode([]) . "\n";
	exit;
}

@set_time_limit(0);
@ob_implicit_flush(1);
@ob_end_flush();

$results = $app->em()->findByIds(UnfurlResult::class, $resultIds);

if (!$results || !$results->count())
{
	// note: line-break used as a delimiter between responses
	echo json_encode([]) . "\n";
}

$style = $app->style($visitor->style_id);

if (!$style->isUsable($visitor))
{
	$style = $app->style();
}

$templater->setStyle($style);
$templater->addDefaultParam('xf', $app->getGlobalTemplateData());

foreach ($results AS $result)
{
	if (!$result->pending)
	{
		continue;
	}

	$fetcher = $app->service(FetcherService::class, $result);

	if ($fetcher->fetch())
	{
		$response = [
			'html' => $fetcher->render(),
			'success' => true,
		];
	}
	else
	{
		$response = [
			'success' => false,
		];
	}

	$response['result_id'] = $result->result_id;

	// note: line-break used as a delimiter between responses
	echo json_encode($response) . "\n";
}
