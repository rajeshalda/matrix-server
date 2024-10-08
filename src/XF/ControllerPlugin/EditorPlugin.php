<?php

namespace XF\ControllerPlugin;

use XF\Html\Renderer\BbCode;
use XF\Util\Str;

class EditorPlugin extends AbstractPlugin
{
	public function fromInput($key, $htmlMaxLength = -1)
	{
		$htmlKey = "{$key}_html";
		$request = $this->request;
		if ($request->exists($key))
		{
			return $request->filter($key, 'str');
		}
		else if ($request->exists($htmlKey))
		{
			$html = $request->filter($htmlKey, 'str,no-clean');
			return $this->convertToBbCode($html, $htmlMaxLength);
		}
		else
		{
			return '';
		}
	}

	public function convertToBbCode($html, $htmlMaxLength = -1)
	{
		if ($htmlMaxLength < 0)
		{
			$htmlMaxLength = 6 * $this->options()->messageMaxLength;
			// increase the limit as HTML can be a lot more verbose and some scenarios might relax the limit
		}

		if ($htmlMaxLength && Str::strlen($html) > $htmlMaxLength)
		{
			throw \XF::phrasedException('submitted_message_is_too_long_to_be_processed');
		}

		$bbCode = BbCode::renderFromHtml($html);
		return \XF::cleanString($bbCode);
	}
}
