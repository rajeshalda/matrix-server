<?php

namespace XF\Option;

use XF\Entity\Option;

class MaxFilesize extends AbstractOption
{
	public static function renderOption(Option $option, array $htmlParams)
	{
		$serverMaxFileSize = \XF::app()->uploadMaxFilesize / 1024;

		$htmlParams['min'] = 0;
		$htmlParams['max'] = $serverMaxFileSize;
		$htmlParams['units'] = \XF::phrase('units_kb');
		$htmlParams['explainHtml'] = \XF::phrase(
			'option_explain.' . $option->option_id,
			['serverMaxFileSize' => \XF::language()->numberFormat($serverMaxFileSize)]
		);

		return static::getNumberBoxRow($option, $htmlParams);
	}

	public static function verifyOption(&$value, Option $option)
	{
		if ($value * 1024 > \XF::app()->uploadMaxFilesize)
		{
			$option->error(\XF::phrase('value_greater_than_server_config_allows'), $option->option_id);
			return false;
		}

		return true;
	}
}
