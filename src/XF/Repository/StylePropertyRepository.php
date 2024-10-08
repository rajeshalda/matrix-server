<?php

namespace XF\Repository;

use XF\Entity\StyleProperty;
use XF\Entity\StylePropertyGroup;
use XF\Entity\StylePropertyMap;
use XF\Finder\StylePropertyFinder;
use XF\Finder\StylePropertyGroupFinder;
use XF\Finder\StylePropertyMapFinder;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;
use XF\Style;

use function in_array, is_array, is_scalar;

class StylePropertyRepository extends Repository
{
	public function findPropertyMapForEditing(\XF\Entity\Style $style, $inGroup = null)
	{
		$finder = $this->finder(StylePropertyMapFinder::class);
		$finder->where('style_id', $style->style_id)
			->with('Property', true)
			->with('Property.Group')
			->whereAddOnActive([
				'relation' => 'Property.AddOn',
				'column' => 'Property.addon_id',
			])
			->keyedBy('property_name')
			->order('Property.display_order');

		if ($inGroup)
		{
			$finder->where('Property.group_name', $inGroup);
		}

		return $finder;
	}

	/**
	 * @param \XF\Entity\Style $style
	 *
	 * @return Finder
	 */
	public function findPropertyMapInStyle(\XF\Entity\Style $style)
	{
		$finder = $this->finder(StylePropertyMapFinder::class);
		$finder->where('style_id', $style->style_id)
			->setDefaultOrder('property_name')
			->keyedBy('property_name')
			->with('Property', true);

		return $finder;
	}

	/**
	 * @param \XF\Entity\Style $style
	 * @param array $groups List of available groups in style, keyed by group_name
	 *
	 * @return StylePropertyMap[]
	 */
	public function getUngroupedPropertyMapsInStyle(\XF\Entity\Style $style, array $groups)
	{
		$maps = $this->findPropertyMapForEditing($style)->fetch();
		$ungrouped = [];
		foreach ($maps AS $k => $map)
		{
			if (!isset($groups[$map->Property->group_name]))
			{
				$ungrouped[$k] = $map;
			}
		}

		return $ungrouped;
	}

	/**
	 * @param \XF\Entity\Style $style
	 *
	 * @return Finder
	 */
	public function findPropertiesInStyle(\XF\Entity\Style $style)
	{
		$finder = $this->finder(StylePropertyFinder::class);
		$finder->where('style_id', $style->style_id)
			->order('property_name');

		return $finder;
	}

	public function getStyleColorData(?\XF\Entity\Style $style = null)
	{
		if ($style === null)
		{
			$style = $this->repository(StyleRepository::class)->getMasterStyle();
		}

		$data = [];

		$styleType = $this->getEffectivePropertyInStyle($style, 'styleType');
		$groups = [
			'color' => \XF::phrase('basic'),
			'palette' => \XF::phrase('palette'),
		];

		foreach ($style->getVariations() AS $variation)
		{
			$data[$variation] = [
				'styleType' => $styleType->getVariationValue($variation),
				'groups' => [],
			];

			foreach ($groups AS $group => $title)
			{
				$data[$variation]['groups'][$group] = [
					'title' => $title,
					'colors' => $this->getColorMapVariationFromPropertyGroup(
						$style,
						$group,
						$variation
					),
				];
			}
		}

		return $data;
	}

	protected function getColorMapFromPropertyGroup(\XF\Entity\Style $style, $group)
	{
		return $this->getColorMapVariationFromPropertyGroup(
			$style,
			$group,
			Style::VARIATION_DEFAULT
		);
	}

	protected function getColorMapVariationFromPropertyGroup(
		\XF\Entity\Style $style,
		string $group,
		string $variation
	): array
	{
		$colors = [];

		/** @var AbstractCollection|StylePropertyMap[] */
		$propertyMaps = $this->findPropertyMapForEditing($style, $group)->fetch();
		foreach ($propertyMaps AS $propertyMap)
		{
			$property = $propertyMap->Property;
			if ($property->value_type !== 'color')
			{
				continue;
			}

			$colors['@xf-' . $property->property_name] = [
				'title' => $property->title,
				'value' => $property->getVariationValue($variation),
			];
		}

		return $colors;
	}

	/**
	 * @param \XF\Entity\Style $style
	 *
	 * @return StyleProperty[]
	 */
	public function getEffectivePropertiesInStyle(\XF\Entity\Style $style)
	{
		$finder = $this->findPropertyMapInStyle($style);
		$finder->pluckFrom('Property', 'property_name');

		return $finder->fetch()->toArray();
	}

	/**
	 * @param \XF\Entity\Style $style
	 * @param string $propertyName
	 *
	 * @return StyleProperty|null
	 */
	public function getEffectivePropertyInStyle(\XF\Entity\Style $style, $propertyName)
	{
		$finder = $this->findPropertyMapInStyle($style)->where('property_name', $propertyName);
		$map = $finder->fetchOne();

		return ($map ? $map->Property : null);
	}

	/**
	 * @param \XF\Entity\Style $style
	 *
	 * @return StylePropertyGroup[]
	 */
	public function getEffectivePropertyGroupsInStyle(\XF\Entity\Style $style)
	{
		$stylePath = $style->parent_list;
		if (!$stylePath)
		{
			$stylePath = [0];
		}

		$entities = $this->finder(StylePropertyGroupFinder::class)
			->where('style_id', $stylePath)
			->whereAddOnActive()
			->fetch();
		$styleGrouped = [];
		foreach ($entities AS $entity)
		{
			$styleGrouped[$entity->style_id][] = $entity;
		}

		// traverse from most generic (last) to most specific (first) in case of name conflict
		$keyedEntities = [];
		foreach (array_reverse($stylePath) AS $styleId)
		{
			if (empty($styleGrouped[$styleId]))
			{
				continue;
			}

			foreach ($styleGrouped[$styleId] AS $entity)
			{
				$keyedEntities[$entity->group_name] = $entity;
			}
		}

		uasort($keyedEntities, function ($a, $b)
		{
			$aOrder = $a->display_order;
			$bOrder = $b->display_order;
			return $aOrder <=> $bOrder;
		});

		return $keyedEntities;
	}

	/**
	 * @param int $propertyId
	 *
	 * @return StyleProperty[]
	 */
	public function getPropertiesDerivedFrom($propertyId)
	{
		$finder = $this->finder(StylePropertyMapFinder::class);
		$finder->where('parent_property_id', $propertyId)
			->with('Property', true)
			->pluckFrom('Property', 'property_id');

		return $finder->fetch()->toArray();
	}

	public function updatePropertyValues(\XF\Entity\Style $style, array $values, array $revertProperties = [])
	{
		$map = $this->findPropertyMapInStyle($style)->fetch();

		$this->db()->beginTransaction();

		foreach ($revertProperties AS $propertyName)
		{
			unset($values[$propertyName]);

			if (isset($map[$propertyName]))
			{
				/** @var StylePropertyMap $propMap */
				$propMap = $map[$propertyName];
				if ($propMap->isRevertable())
				{
					$propMap->Property->delete();
				}
			}
		}

		foreach ($values AS $propertyName => $value)
		{
			if (isset($map[$propertyName]))
			{
				/** @var StyleProperty $property */
				$property = $map[$propertyName]->Property;

				if ($property->style_id != $style->style_id)
				{
					$property = $property->getPropertyCopyInStyle($style);
				}
				if ($property->updatePropertyValue($value))
				{
					$property->save();
				}
			}
		}

		$this->db()->commit();
	}

	public function castAndValidatePropertyValue($valueType, array $options, &$value, &$error = null)
	{
		if ($valueType == 'boolean')
		{
			$value = $value ? 1 : 0;
			return true;
		}

		if ($valueType == 'template')
		{
			if (isset($options['type']))
			{
				switch ($options['type'])
				{
					case 'array':
						if (!is_array($value))
						{
							$value = [];
						}
						break;

					case 'scalar':
						if (!is_scalar($value))
						{
							$value = '';
						}
						break;

					default:
						throw new \LogicException("Unknown template value type '$options[type]'");
				}
			}

			return true;
		}

		if (!is_scalar($value))
		{
			$value = '';
		}

		if ($valueType == 'radio' || $valueType == 'select')
		{
			if (!isset($options[$value]))
			{
				return false;
			}
		}

		if ($valueType == 'number' && preg_match('/^(\d+)[a-z]+$/iU', $value, $match))
		{
			// if we're expecting a number and what was entered was a clear unit, just pull the number part of the unit
			$value = $match[1];
		}
		else if ($valueType == 'unit' && preg_match('/^(\d+)$/iU', $value, $match))
		{
			// if we're expecting a unit and someone has entered just a number, assume they meant pixels
			$value = $match[1] . 'px';
		}

		return true;
	}

	public function getDefaultPropertyValue($valueType, array $options)
	{
		switch ($valueType)
		{
			case 'boolean': return 0;
			case 'number': return 0;
			case 'unit': return '0px';

			case 'radio':
			case 'select':
				reset($options);
				$key = key($options);
				return $key ?? '';

			case 'template':
				if (isset($options['type']))
				{
					switch ($options['type'])
					{
						case 'array':
							return [];

						case 'scalar':
							return '';

						default:
							throw new \LogicException("Unknown template value type '$options[type]'");
					}
				}

				return null;

			default: return '';
		}
	}

	public function buildPropertyLessCacheValue(array $css, array $allowedComponents)
	{
		foreach ($css AS $k => &$value)
		{
			$value = trim($value);
			if ($value === '')
			{
				unset($css[$k]);
			}
		}

		$output = [];

		$outputSimple = function ($name) use (&$output, $css)
		{
			if (isset($css[$name]))
			{
				$output[$name] = "$name: " . $css[$name] . ';';
			}
		};

		// text
		if (in_array('text', $allowedComponents))
		{
			$outputSimple('font-size');
			$outputSimple('color');
			$outputSimple('font-weight');
			$outputSimple('font-style');
			$outputSimple('text-decoration');
		}

		// background
		if (in_array('background', $allowedComponents))
		{
			if (isset($css['background-color']) || isset($css['background-image']))
			{
				if (isset($css['background-image']))
				{
					$css['background-image'] = preg_replace('/^("|\')(.*)\\1$/', '\\2', $css['background-image']);
					if (!preg_match('#^[a-z0-9-]+\(#i', $css['background-image']))
					{
						$css['background-image'] = 'url("' . $css['background-image'] . '")';
					}
				}

				$output['background'] = 'background: '
					. ($css['background-color'] ?? '')
					. ' '
					. ($css['background-image'] ?? '')
					. ';';
			}
		}

		// border
		if (in_array('border', $allowedComponents))
		{
			$hasGeneralBorderStyle = false;

			if (isset($css['border-width'], $css['border-color']))
			{
				$output['border'] = 'border: ' . $css['border-width'] . ' solid ' . $css['border-color'] . ';';
				$hasGeneralBorderStyle = true;
			}
			else
			{
				$outputSimple('border-width');
				if (isset($css['border-width']))
				{
					$output['border-style'] = 'border-style: solid;';
					$hasGeneralBorderStyle = true;
				}
				$outputSimple('border-color');
			}

			$outputBorderSide = function ($side) use (&$output, $css, $outputSimple, $hasGeneralBorderStyle)
			{
				$width = "border-{$side}-width";
				$color = "border-{$side}-color";
				$sideProperty = "border-{$side}";

				if (isset($css[$width], $css[$color]))
				{
					$output[$sideProperty] = $sideProperty . ': ' . $css[$width] . ' solid ' . $css[$color] . ';';
				}
				else
				{
					$outputSimple($width);
					if (isset($css[$width]) && !$hasGeneralBorderStyle)
					{
						$style = "border-{$side}-style";
						$output[$style] = $style . ': solid;';
					}
					$outputSimple($color);
				}
			};
			$outputBorderSide('top');
			$outputBorderSide('right');
			$outputBorderSide('bottom');
			$outputBorderSide('left');
		}

		// border radius
		if (in_array('border_radius', $allowedComponents))
		{
			$outputSimple('border-radius');
			$outputSimple('border-top-left-radius');
			$outputSimple('border-top-right-radius');
			$outputSimple('border-bottom-right-radius');
			$outputSimple('border-bottom-left-radius');
		}

		// padding
		if (in_array('padding', $allowedComponents))
		{
			$outputSimple('padding');
			$outputSimple('padding-top');
			$outputSimple('padding-right');
			$outputSimple('padding-bottom');
			$outputSimple('padding-left');
		}

		// extra
		if (in_array('extra', $allowedComponents) && isset($css['extra']))
		{
			$output['extra'] = $css['extra'];
		}

		$return = trim(implode("\n", $output));
		$return = "\t" . str_replace("\n", "\n\t", $return);

		return $return;
	}
}
