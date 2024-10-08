<?php

namespace XF\ControllerPlugin;

use XF\Mvc\Entity\Entity;
use XF\Service\Report\CreatorService;

use function strlen;

/**
 * @method void assertNotFlooding($action, $floodingLimit = null)
 */
class ReportPlugin extends AbstractPlugin
{
	/**
	 * @return CreatorService
	 */
	protected function setupReportCreate($contentType, Entity $content)
	{
		$message = $this->request->filter('message', 'str');
		if (!strlen($message))
		{
			throw $this->exception($this->error(\XF::phrase('please_enter_reason_for_reporting_this_message')));
		}

		/** @var CreatorService $creator */
		$creator = $this->service(CreatorService::class, $contentType, $content);
		$creator->setMessage($message);

		return $creator;
	}

	protected function finalizeReportCreate(CreatorService $creator)
	{
		$creator->sendNotifications();
	}

	public function actionReport($contentType, Entity $content, $confirmUrl, $returnUrl, $options = [])
	{
		$options = array_merge([
			'view' => 'XF:Report\Report',
			'template' => 'report_create',
			'extraViewParams' => [],
		], $options);

		if ($this->request->isPost())
		{
			$creator = $this->setupReportCreate($contentType, $content);
			if (!$creator->validate($errors))
			{
				return $this->error($errors);
			}
			$this->assertNotFlooding('report');
			$creator->save();
			$this->finalizeReportCreate($creator);

			return $this->redirect($returnUrl, \XF::phrase('thank_you_for_reporting_this_content'));
		}
		else
		{
			$viewParams = [
				'confirmUrl' => $confirmUrl,
				'content' => $content,
			];
			return $this->view($options['view'], $options['template'], $viewParams + $options['extraViewParams']);
		}
	}
}
