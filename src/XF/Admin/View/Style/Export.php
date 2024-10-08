<?php

namespace XF\Admin\View\Style;

use XF\Mvc\View;

class Export extends View
{
	public function renderXml()
	{
		$this->response->setDownloadFileName($this->params['filename']);

		/** @var \DOMDocument $document */
		$document = $this->params['xml'];
		return $document->saveXml();
	}

	public function renderRaw()
	{
		$this->response
			->setDownloadFileName($this->params['filename'])
			->contentType('application/octet-stream', '');

		return $this->response->responseFile($this->params['tempFile']);
	}
}
