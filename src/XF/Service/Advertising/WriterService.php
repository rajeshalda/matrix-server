<?php

namespace XF\Service\Advertising;

use XF\App;
use XF\Entity\Advertising;
use XF\Entity\AdvertisingPosition;
use XF\Entity\Template;
use XF\Service\AbstractService;
use XF\Util\Arr;

use function is_array;

class WriterService extends AbstractService
{
	/**
	 * @var AdvertisingPosition[]
	 */
	protected $positions = [];

	/**
	 * @var Advertising[]
	 */
	protected $ads = [];

	/**
	 * @var Template
	 */
	protected $template;

	protected $disallowedTemplates = [];

	public function __construct(App $app, array $positions, array $ads)
	{
		parent::__construct($app);

		$this->positions = $positions;
		$this->ads = $ads;

		$template = $this->findOne(Template::class, [
			'style_id' => 0,
			'title' => '_ads',
			'type' => 'public',
		]);
		if (!$template)
		{
			$template = $this->em()->create(Template::class);
			$template->style_id = 0;
			$template->title = '_ads';
			$template->type = 'public';
			$template->addon_id = '';
			$template->template = '';
			$template->save(); // needs to exist regardless
		}
		$this->template = $template;
	}

	public function setDisallowedTemplates($disallowedTemplates)
	{
		if (is_array($disallowedTemplates))
		{
			$this->disallowedTemplates = $disallowedTemplates;
		}
		else if ($disallowedTemplates)
		{
			$this->disallowedTemplates = Arr::stringToArray($disallowedTemplates);
		}
		else
		{
			$this->disallowedTemplates = [];
		}
	}

	public function write()
	{
		$positions = $this->positions;
		$ads = $this->ads;

		$template = '';

		foreach ($positions AS $positionId => $position)
		{
			if (empty($ads[$positionId]))
			{
				continue;
			}

			$adContents = $this->prepareAdContents($ads[$positionId]);
			$this->preparePositionContents($position, $adContents, $template);
		}

		$this->template->template = $template;
		$this->template->save();
	}

	/**
	 * @param Advertising[] $ads
	 *
	 * @return string
	 */
	protected function prepareAdContents(array $ads)
	{
		$output = '';

		foreach ($ads AS $ad)
		{
			if (!$ad->ad_html)
			{
				continue;
			}

			$condition = null;
			$adHtml = '';

			$tabs = "\t";
			if ($ad->display_criteria)
			{
				$tabs = "\t\t";

				$userGroups = !empty($ad->display_criteria['user_groups'])
					? $ad->display_criteria['user_groups']
					: [];

				$notUserGroups = !empty($ad->display_criteria['not_user_groups'])
					? $ad->display_criteria['not_user_groups']
					: [];

				if ($userGroups && $notUserGroups)
				{
					$condition = '$xf.visitor.isMemberOf([' . implode(', ', $userGroups) . ']) '
						. 'AND !$xf.visitor.isMemberOf([' . implode(', ', $notUserGroups) . '])';
				}
				else if ($userGroups)
				{
					$condition = '$xf.visitor.isMemberOf([' . implode(', ', $userGroups) . '])';
				}
				else if ($notUserGroups)
				{
					$condition = '!$xf.visitor.isMemberOf([' . implode(', ', $notUserGroups) . '])';
				}
			}

			$adHtml .= "\n$tabs<xf:comment>{$ad->title}</xf:comment>";

			$line = strtok($ad->ad_html, "\r\n");
			while ($line !== false)
			{
				$adHtml .= "\n$tabs$line";
				$line = strtok("\r\n");
			}

			$adHtml .= "\n";

			if ($condition)
			{
				$output .= "\n\t<xf:if is=\"$condition\">";
				$output .= "\n" . $adHtml;
				$output .= "\n\t</xf:if>\n";
			}
			else
			{
				$output .= $adHtml;
			}
		}

		return $output;
	}

	protected function preparePositionContents(AdvertisingPosition $position, $adContents, &$template)
	{
		if ($template)
		{
			$template .= "\n\n";
		}

		if ($this->disallowedTemplates)
		{
			$disallowedOptions = [];
			foreach ($this->disallowedTemplates AS $t)
			{
				$disallowedOptions[] = "'" . addslashes($t) . "'";
			}
			$disallowed = implode(', ', $disallowedOptions);

			$adContents = '<xf:if is="!in_array($xf.reply.template, [' . $disallowed . '])">' . $adContents . '</xf:if>';
		}

		$arguments = $this->prepareMacroArguments($position->arguments);

		$template .= '<xf:macro id="' . $position->position_id . '"' . ($arguments ? ' ' . $arguments : '') . '>';
		$template .= "\n";
		$template .= $adContents;
		$template .= "\n";
		$template .= '</xf:macro>';
	}

	protected function prepareMacroArguments(array $arguments)
	{
		if (!$arguments)
		{
			return '';
		}

		$parts = [];

		foreach ($arguments AS $argument)
		{
			$parts[] = 'arg-' . $argument['argument'] . '="' . ($argument['required'] ? '!' : '') . '"';
		}

		return implode(' ', $parts);
	}
}
