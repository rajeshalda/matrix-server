<?php

namespace XF\Repository;

use XF\Entity\Style;
use XF\Entity\Template;
use XF\Finder\TemplateFinder;
use XF\Finder\TemplateMapFinder;
use XF\Mvc\Entity\Repository;

use function count;

class TemplateRepository extends Repository
{
	/**
	 * @param Style $style
	 * @param string|null $type
	 *
	 * @return TemplateMapFinder
	 */
	public function findEffectiveTemplatesInStyle(Style $style, $type = null)
	{
		/** @var TemplateMapFinder $finder */
		$finder = $this->finder(TemplateMapFinder::class);
		$finder
			->where('style_id', $style->style_id)
			->with('Template', true)
			->orderTitle()
			->pluckFrom('Template', 'template_id');

		if ($type !== null)
		{
			$finder->where('type', $type);
		}

		return $finder;
	}

	/**
	 * @param Style $style
	 * @param string           $title
	 * @param string           $type
	 *
	 * @return TemplateMapFinder
	 */
	public function findEffectiveTemplateInStyle(Style $style, $title, $type)
	{
		/** @var TemplateMapFinder $finder */
		$finder = $this->finder(TemplateMapFinder::class);
		$finder
			->where('style_id', $style->style_id)
			->where('type', $type)
			->where('title', $title)
			->pluckFrom('Template', 'template_id')
		;

		return $finder;
	}

	/**
	 * @param Style $style
	 * @param string|null $type
	 *
	 * @return TemplateFinder
	 */
	public function findTemplatesInStyle(Style $style, $type = null)
	{
		/** @var TemplateFinder $templateFinder */
		$templateFinder = $this->finder(TemplateFinder::class);
		$templateFinder
			->where('style_id', $style->style_id)
			->order('type')
			->orderTitle();

		if ($type !== null)
		{
			$templateFinder->where('type', $type);
		}

		return $templateFinder;
	}

	public function countOutdatedTemplates()
	{
		return count($this->getBaseOutdatedTemplateData());
	}

	public function getOutdatedTemplates()
	{
		$data = $this->getBaseOutdatedTemplateData();
		$templateIds = array_keys($data);

		if (!$templateIds)
		{
			return [];
		}

		$templates = $this->em->findByIds(Template::class, $templateIds);

		$output = [];
		foreach ($data AS $templateId => $outdated)
		{
			if (!isset($templates[$templateId]))
			{
				continue;
			}

			$outdated['template'] = $templates[$templateId];
			$output[$templateId] = $outdated;
		}

		return $output;
	}

	protected function getBaseOutdatedTemplateData()
	{
		$db = $this->db();

		return $db->fetchAllKeyed('
			SELECT template.template_id,
				parent.version_string AS parent_version_string,
				parent.last_edit_date AS parent_last_edit_date,
				IF(parent.version_id > template.version_id, 1, 0) AS outdated_by_version,
				IF(parent.last_edit_date > 0 AND parent.last_edit_date >= template.last_edit_date, 1, 0) AS outdated_by_date
			FROM xf_template AS template
			INNER JOIN xf_style AS style ON (style.style_id = template.style_id)
			INNER JOIN xf_template_map AS map ON (
				map.style_id = style.parent_id
				AND map.type = template.type
				AND map.title = template.title
			)
			INNER JOIN xf_template AS parent ON (map.template_id = parent.template_id
				AND (
					(parent.last_edit_date > 0 AND parent.last_edit_date >= template.last_edit_date)
					OR parent.version_id > template.version_id
				)
			)
			WHERE template.style_id > 0
			ORDER BY template.title
		', 'template_id');
	}

	public function getTemplateTypes(?Style $style = null)
	{
		$types = [
			'public' => \XF::phrase('public'),
			'email' => \XF::phrase('email'),
		];
		if (($style && !$style->style_id) || (!$style && \XF::$developmentMode))
		{
			$types['admin'] = \XF::phrase('admin');
		}

		return $types;
	}

	public function pruneEditHistory($cutOff = null)
	{
		if ($cutOff === null)
		{
			$logLength = $this->options()->templateHistoryLength;
			if (!$logLength)
			{
				return 0;
			}

			$cutOff = \XF::$time - 86400 * $logLength;
		}

		return $this->db()->delete('xf_template_history', 'log_date < ?', $cutOff);
	}
}
