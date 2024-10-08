<?php

namespace XF\BbCode\ProcessorAction;

use XF\App;
use XF\Str\Formatter;

use function is_array;

class Censor implements FiltererInterface
{
	/**
	 * @var Formatter
	 */
	protected $formatter;

	public function __construct(Formatter $formatter)
	{
		$this->formatter = $formatter;
	}

	public function addFiltererHooks(FiltererHooks $hooks)
	{
		$hooks->addStringHook('censorText')
			->addTagOptionHook('censorTagOption');
	}

	public function censorText($string, array $options)
	{
		return $this->formatter->censorText($string);
	}

	public function censorTagOption($optionValue, array $tag, array $options)
	{
		if (is_array($optionValue))
		{
			foreach ($optionValue AS &$value)
			{
				$value = $this->formatter->censorText($value);
			}

			return $optionValue;
		}
		else
		{
			return $this->formatter->censorText($optionValue);
		}
	}

	public static function factory(App $app)
	{
		return new static($app->stringFormatter());
	}
}
