<?php

namespace XF\Admin\Controller;

use XF\ControllerPlugin\DeletePlugin;
use XF\ControllerPlugin\TogglePlugin;
use XF\Entity\AddOn;
use XF\Entity\Style;
use XF\Entity\StyleProperty;
use XF\Entity\StylePropertyGroup;
use XF\Entity\StylePropertyMap;
use XF\Entity\Template;
use XF\Finder\StyleFinder;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Redirect;
use XF\Repository\AddOnRepository;
use XF\Repository\OptionRepository;
use XF\Repository\StylePropertyRepository;
use XF\Repository\StyleRepository;
use XF\Repository\TemplateRepository;
use XF\Service\Style\ArchiveExportService;
use XF\Service\Style\ArchiveImportService;
use XF\Service\Style\ArchiveValidatorService;
use XF\Service\Style\ExportService;
use XF\Service\Style\ImportService;
use XF\Util\Xml;

use function count, intval, strlen;

class StyleController extends AbstractController
{
	protected function preDispatchController($action, ParameterBag $params)
	{
		$this->assertAdminPermission('style');
	}

	public function actionIndex()
	{
		$styleRepo = $this->getStyleRepo();

		$viewParams = [
			'styleTree' => $styleRepo->getStyleTree(),
			'canSupportStyleArchives' => $styleRepo->canSupportStyleArchives(),
		];
		return $this->view('XF:Style\Listing', 'style_list', $viewParams);
	}

	protected function styleAddEdit(Style $style)
	{
		$viewParams = [
			'style' => $style,
			'nextCounter' => count($style->effective_assets),
			'styleTree' => $this->getStyleRepo()->getStyleTree(false),
		];
		return $this->view('XF:Style\Edit', 'style_edit', $viewParams);
	}

	public function actionEdit(ParameterBag $params)
	{
		$style = $this->assertStyleExists($params->style_id);
		return $this->styleAddEdit($style);
	}

	public function actionAdd()
	{
		$style = $this->em()->create(Style::class);
		return $this->styleAddEdit($style);
	}

	protected function styleSaveProcess(Style $style)
	{
		$form = $this->formAction();

		$input = $this->filter([
			'parent_id' => 'uint',
			'title' => 'str',
			'description' => 'str',
			'assets' => 'array',
			'enable_variations' => 'bool',
			'user_selectable' => 'bool',
		]);

		$form->basicEntitySave($style, $input);

		return $form;
	}

	public function actionSave(ParameterBag $params)
	{
		$this->assertPostOnly();

		if ($params->style_id)
		{
			$style = $this->assertStyleExists($params->style_id);
		}
		else
		{
			$style = $this->em()->create(Style::class);
		}

		$this->styleSaveProcess($style)->run();

		return $this->redirect($this->buildLink('styles'));
	}

	public function actionToggle()
	{
		// update defaultStyleId option if necessary
		$input = $this->filter([
			'default_style_id' => 'int',
		]);
		$style = $this->assertStyleExists($input['default_style_id']);
		if (!$style->user_selectable)
		{
			return $this->error(\XF::phrase('it_is_not_possible_to_prevent_users_selecting_the_default_style'));
		}
		$this->repository(OptionRepository::class)->updateOptions(['defaultStyleId' => $input['default_style_id']]);

		/** @var TogglePlugin $plugin */
		$plugin = $this->plugin(TogglePlugin::class);
		return $plugin->actionToggle(StyleFinder::class, 'user_selectable');
	}

	public function actionDelete(ParameterBag $params)
	{
		$style = $this->assertStyleExists($params->style_id);

		/** @var DeletePlugin $plugin */
		$plugin = $this->plugin(DeletePlugin::class);
		return $plugin->actionDelete(
			$style,
			$this->buildLink('styles/delete', $style),
			$this->buildLink('styles/edit', $style),
			$this->buildLink('styles'),
			$style->title,
			'style_delete'
		);
	}

	public function actionTemplates(ParameterBag $params)
	{
		$type = $this->filter('type', 'str');
		if (!$type)
		{
			$type = 'public';
		}

		$currentAddOn = null;
		$addOnId = $this->filter('addon_id', 'str');
		if ($addOnId)
		{
			$currentAddOn = $this->em()->find(AddOn::class, $addOnId);
		}

		$style = $this->assertStyleExists($params->style_id);
		if (!$style->canEdit())
		{
			return $this->error(\XF::phrase('templates_in_this_style_can_not_be_modified'));
		}

		if ($type == 'admin' && $style->style_id)
		{
			$style = $this->getStyleRepo()->getMasterStyle();
			return $this->redirect($this->buildLink('styles/templates', $style, ['type' => 'admin']));
		}

		$this->app->response()->setCookie('edit_style_id', $style->style_id);

		$page = $this->filterPage();
		$perPage = 300;

		$templateRepo = $this->getTemplateRepo();
		$types = $templateRepo->getTemplateTypes($style);
		if (!isset($types[$type]))
		{
			return $this->error(\XF::phrase('templates_in_this_style_can_not_be_modified'));
		}

		$templateFinder = $templateRepo->findEffectiveTemplatesInStyle($style, $type);
		$templateFinder->limitByPage($page, $perPage);

		if ($currentAddOn)
		{
			$templateFinder->where('Template.addon_id', $currentAddOn->addon_id);
		}
		$templateFinder->with('Template.AddOn');

		$filter = $this->filter('_xfFilter', [
			'text' => 'str',
			'prefix' => 'bool',
		]);
		if (strlen($filter['text']))
		{
			$templateFinder->Template->searchTitle($filter['text'], $filter['prefix']);
		}

		$templates = $templateFinder->fetch();
		$total = $templateFinder->total();

		$linkParams = [
			'type' => $type,
			'addon_id' => $currentAddOn ? $currentAddOn->addon_id : null,
		];

		$viewParams = [
			'style' => $style,
			'types' => $types,
			'type' => $type,
			'templates' => $templates,
			'styleTree' => $this->getStyleRepo()->getStyleTree(),

			'currentAddOn' => $currentAddOn,
			'addOns' => $this->getAddOnRepo()->findAddOnsForList()->fetch(),

			'linkParams' => $linkParams,
			'filter' => $filter['text'],

			'page' => $page,
			'perPage' => $perPage,
			'total' => $total,
		];
		return $this->view('XF:Template\Listing', 'template_list', $viewParams);
	}

	/**
	 * Enables switching of style from templates/edit,
	 * to enable editing of template {title} in a different style
	 *
	 * @param ParameterBag $params
	 *
	 * @return Redirect
	 */
	public function actionEditTemplate(ParameterBag $params)
	{
		$style = $this->assertStyleExists($params->style_id);

		$template = $this->assertTemplateExists($this->filter('template_id', 'uint'));

		$templateRepo = $this->getTemplateRepo();

		$templateInfo = $templateRepo->findEffectiveTemplateInStyle($style, $template->title, $template->type)->fetchOne();

		return $this->redirect($this->buildLink('templates/edit', $templateInfo, [
			'style_id' => $style->style_id,
		]));
	}

	/**
	 * Enables switching of style from templates/add,
	 * to allow a template to be added to a different style
	 *
	 * @param ParameterBag $params
	 *
	 * @return Redirect
	 */
	public function actionAddTemplate(ParameterBag $params)
	{
		return $this->redirect($this->buildLink('templates/add', null, [
			'style_id' => $params->style_id,
			'type' => $this->filter('type', 'str', 'public'),
		]));
	}

	public function actionStyleProperties(ParameterBag $params)
	{
		$style = $this->assertStyleExists($params->style_id);
		if (!$style->canEdit())
		{
			return $this->error(\XF::phrase('style_properties_in_style_can_not_be_modified'));
		}

		$this->app->response()->setCookie('edit_style_id', $style->style_id);

		$propertyRepo = $this->getPropertyRepo();

		$groups = $propertyRepo->getEffectivePropertyGroupsInStyle($style);
		$hasUngrouped = count($propertyRepo->getUngroupedPropertyMapsInStyle($style, $groups)) > 0;

		$viewParams = [
			'style' => $style,
			'styleTree' => $this->getStyleRepo()->getStyleTree(),
			'groups' => $groups,
			'hasUngrouped' => $hasUngrouped,
		];
		return $this->view('XF:StyleProperty\GroupList', 'style_property_group_list', $viewParams);
	}

	public function actionStylePropertiesGroup(ParameterBag $params)
	{
		$style = $this->assertStyleExists($params->style_id);
		if (!$style->canEdit())
		{
			return $this->error(\XF::phrase('style_properties_in_style_can_not_be_modified'));
		}

		$this->app->response()->setCookie('edit_style_id', $style->style_id);

		$propertyRepo = $this->getPropertyRepo();
		$groups = $propertyRepo->getEffectivePropertyGroupsInStyle($style);

		if ($this->filter('ungrouped', 'bool'))
		{
			$group = null;
			$urlParams = ['ungrouped' => 1];
		}
		else
		{
			$groupName = $this->filter('group', 'str');
			if (!isset($groups[$groupName]))
			{
				return $this->error(\XF::phrase('requested_style_property_group_not_found'), 404);
			}

			$group = $groups[$groupName];
			$urlParams = ['group' => $groupName];
		}

		if ($this->isPost())
		{
			$input = $this->filterFormJson([
				'properties' => 'array',
				'properties_listed' => 'array-str',
			]);

			$propertyValues = $input['properties'];
			foreach ($input['properties_listed'] AS $propertyName)
			{
				if (!isset($propertyValues[$propertyName]))
				{
					$propertyValues[$propertyName] = null;
				}
			}

			$propertyRevert = $this->filter('properties_revert', 'array-str');

			$this->getPropertyRepo()->updatePropertyValues($style, $propertyValues, $propertyRevert);

			$properties = $this->getPropertiesForGroupRefresh($style, $group);

			$reply = $this->redirect(
				$this->buildLink(
					'styles/style-properties/group',
					$style,
					$urlParams
				)
			);
			$reply->setJsonParam('properties', $properties);
			return $reply;
		}
		else
		{
			if ($group)
			{
				$propertyMap = $propertyRepo->findPropertyMapForEditing($style, $group->group_name)->fetch()->toArray();
				$hasUngrouped = count($propertyRepo->getUngroupedPropertyMapsInStyle($style, $groups)) > 0;
			}
			else
			{
				$propertyMap = $propertyRepo->getUngroupedPropertyMapsInStyle($style, $groups);
				if (!$propertyMap)
				{
					return $this->redirect($this->buildLink('styles/style-properties', $style));
				}

				$hasUngrouped = true;
			}

			$viewParams = [
				'style' => $style,
				'styleTree' => $this->getStyleRepo()->getStyleTree(),
				'propertyMap' => $propertyMap,
				'groups' => $groups,
				'hasUngrouped' => $hasUngrouped,
				'group' => $group,
				'urlParams' => $urlParams,
				'colorData' => $propertyRepo->getStyleColorData($style),
			];
			return $this->view('XF:StyleProperty\GroupView', 'style_property_group_view', $viewParams);
		}
	}

	protected function getPropertiesForGroupRefresh(
		Style $style,
		?StylePropertyGroup $group
	): array
	{
		$properties = [];

		$propertyMapFinder = $this->getPropertyRepo()->findPropertyMapForEditing(
			$style,
			$group->group_name
		);
		/** @var AbstractCollection|StylePropertyMap[] $propertyMaps */
		$propertyMaps = $propertyMapFinder->fetch();

		foreach ($propertyMaps AS $propertyMap)
		{
			$property = $propertyMap->Property;

			if ($property->has_variations)
			{
				$value = [];

				foreach ($property->getVariations() AS $variation)
				{
					$value[$variation] = $property->getVariationValue($variation);
				}
			}
			else
			{
				$value = $property->property_value;
			}

			$properties[$propertyMap->property_name] = [
				'property_type' => $property->property_type,
				'value' => $value,
				'customizationState' => $propertyMap->getCustomizationState(),
			];

		}

		return $properties;
	}

	public function actionImport()
	{
		if ($this->isPost())
		{
			$upload = $this->request->getFile('upload', false);
			if (!$upload)
			{
				return $this->error(\XF::phrase('please_upload_valid_style_xml_file'));
			}

			/** @var ImportService $styleImporter */
			$styleImporter = $this->service(ImportService::class);

			$xmlFile = null;

			switch ($upload->getExtension())
			{
				case 'zip':
					$this->assertcanSupportStyleArchives();

					$archiveFile = $upload->getTempFile();

					/** @var ArchiveImportService $styleArchiveImporter */
					$styleArchiveImporter = $this->service(ArchiveImportService::class, $archiveFile);

					if (!$styleArchiveImporter->validateArchive($errors))
					{
						return $this->error($errors);
					}

					$styleImporter->setArchiveImporter($styleArchiveImporter);

					$xmlFile = $styleArchiveImporter->getXmlFile();
					break;

				case 'xml':
					$xmlFile = $upload->getTempFile();
					break;

				default:
					return $this->error(\XF::phrase('please_upload_valid_style_xml_file'));
			}

			try
			{
				$document = Xml::openFile($xmlFile);
			}
			catch (\Exception $e)
			{
				$document = null;
			}

			if (!$styleImporter->isValidXml($document, $error))
			{
				return $this->error($error);
			}

			$input = $this->filter([
				'target' => 'str',
				'parent_style_id' => 'uint',
				'overwrite_style_id' => 'uint',
			]);

			if ($input['target'] == 'overwrite')
			{
				$overwriteStyle = $this->assertRecordExists(Style::class, $input['overwrite_style_id']);
				$styleImporter->setOverwriteStyle($overwriteStyle);
			}
			else
			{
				$parentStyle = $input['parent_style_id']
					? $this->assertRecordExists(Style::class, $input['parent_style_id'])
					: null;
				$styleImporter->setParentStyle($parentStyle);
			}

			if (!$this->filter('force', 'bool'))
			{
				if (!$styleImporter->isValidConfiguration($document, $errors))
				{
					return $this->error(\XF::phrase('import_verification_errors_x_select_skip_checks', [
						'errors' => implode(' ', $errors),
					]));
				}
			}

			$styleImporter->importFromXml($document);

			return $this->redirect($this->buildLink('styles'));
		}
		else
		{
			$styleRepo = $this->getStyleRepo();

			$viewParams = [
				'styleTree' => $styleRepo->getStyleTree(false),
				'canSupportStyleArchives' => $styleRepo->canSupportStyleArchives(),
			];
			return $this->view('XF:Style\Import', 'style_import', $viewParams);
		}
	}

	public function actionExport(ParameterBag $params)
	{
		$style = $this->assertStyleExists($params->style_id);

		if ($this->isPost())
		{
			$this->setResponseType('xml');

			/** @var ExportService $styleExporter */
			$styleExporter = $this->service(ExportService::class, $style);

			$addOnId = $this->filter('addon_id', 'str');
			$addOn = $addOnId ? $this->assertRecordExists(AddOn::class, $addOnId) : null;

			$styleExporter->setAddOn($addOn);

			if (\XF::$developmentMode or \XF::config('designer')['enabled'])
			{
				$styleExporter->setIndependent($this->filter('independent', 'bool'));
			}

			$viewParams = [
				'style' => $style,
				'xml' => $styleExporter->exportToXml(),
				'filename' => $styleExporter->getExportFileName(),
			];
			return $this->view('XF:Style\Export', '', $viewParams);
		}
		else
		{
			$viewParams = [
				'style' => $style,
			];
			return $this->view('XF:Style\Export', 'style_export', $viewParams);
		}
	}

	public function actionExportArchive(ParameterBag $params)
	{
		$style = $this->assertStyleExists($params->style_id);

		$this->assertcanSupportStyleArchives();

		if ($this->isPost())
		{
			/** @var ArchiveExportService $styleArchiveExporter */
			$styleArchiveExporter = $this->service(ArchiveExportService::class, $style);

			$addOnId = $this->filter('addon_id', 'str');
			$addOn = $addOnId ? $this->assertRecordExists(AddOn::class, $addOnId) : null;

			$styleArchiveExporter->setAddOn($addOn);
			$styleArchiveExporter->setIndependent($this->filter('independent', 'bool'));

			$tempFile = $styleArchiveExporter->build();

			$this->setResponseType('raw');

			$viewParams = [
				'style' => $style,
				'tempFile' => $tempFile,
				'filename' => $styleArchiveExporter->getArchiveFileName(),
			];
			return $this->view('XF:Style\Export', '', $viewParams);
		}
		else
		{
			$viewParams = [
				'style' => $style,
				'asArchive' => true,
				'allowedExtensions' => ArchiveValidatorService::EXTENSION_WHITELIST,
			];
			return $this->view('XF:Style\Export', 'style_export', $viewParams);
		}
	}

	public function actionCustomizedComponents(ParameterBag $params)
	{
		if ($params->style_id)
		{
			$styleId = $params->style_id;
		}
		else
		{
			$styleId = $this->request->getCookie('edit_style_id', $params->style_id);
			if (!$styleId)
			{
				$styleId = $this->options()->defaultStyleId;
			}
			$styleId = intval($styleId);
		}

		$style = $this->assertStyleExists($styleId);

		$templates = $this->getTemplateRepo()->findTemplatesInStyle($style)->fetch();
		$properties = $this->getPropertyRepo()->findPropertiesInStyle($style)->fetch();

		$viewParams = [
			'style' => $style,
			'templates' => $templates,
			'properties' => $properties,
			'itemCount' => $templates->count() + $properties->count(),
			'styleTree' => $this->getStyleRepo()->getStyleTree(),
		];
		return $this->view('XF:Style\CustomizedComponents', 'style_customized_components', $viewParams);
	}

	public function actionMassRevert(ParameterBag $params)
	{
		$style = $this->assertStyleExists($params->style_id);
		if (!$style->style_id)
		{
			return $this->noPermission();
		}

		$templateIds = $this->filter('template_ids', 'array-uint');
		$propertyIds = $this->filter('property_ids', 'array-uint');

		if (!$templateIds && !$propertyIds)
		{
			return $this->error(\XF::phrase('please_select_at_least_one_template_or_style_property_to_revert'));
		}

		$revert = $this->filter('perform_revert', 'bool');
		if ($this->isPost() && $revert)
		{
			$templates = $this->em()->findByIds(Template::class, $templateIds);
			foreach ($templates AS $template)
			{
				$template->delete();
			}

			$properties = $this->em()->findByIds(StyleProperty::class, $propertyIds);
			foreach ($properties AS $property)
			{
				$property->delete();
			}

			return $this->redirect($this->buildLink('styles', $style) . $this->buildLinkHash($style->style_id));
		}
		else
		{
			$viewParams = [
				'style' => $style,
				'templateIds' => $templateIds,
				'propertyIds' => $propertyIds,
			];
			return $this->view('XF:Style\MassRevert', 'style_mass_revert', $viewParams);
		}
	}

	/**
	 * @param string $id
	 * @param array|string|null $with
	 * @param null|string $phraseKey
	 *
	 * @return Style
	 */
	protected function assertStyleExists($id, $with = null, $phraseKey = null)
	{
		if ($id === 0 || $id === "0")
		{
			return $this->getStyleRepo()->getMasterStyle();
		}

		return $this->assertRecordExists(Style::class, $id, $with, $phraseKey);
	}

	protected function assertcanSupportStyleArchives()
	{
		if (!$this->getStyleRepo()->canSupportStyleArchives())
		{
			throw $this->exception($this->error(\XF::phrase('importing_exporting_style_archives_only_supported_if_meet_requirements')));
		}
	}

	/**
	 * @param string $id
	 * @param array|string|null $with
	 * @param null|string $phraseKey
	 *
	 * @return Template
	 */
	protected function assertTemplateExists($id, $with = null, $phraseKey = null)
	{
		return $this->assertRecordExists(Template::class, $id, $with, $phraseKey);
	}

	/**
	 * @return StyleRepository
	 */
	protected function getStyleRepo()
	{
		return $this->repository(StyleRepository::class);
	}

	/**
	 * @return TemplateRepository
	 */
	protected function getTemplateRepo()
	{
		return $this->repository(TemplateRepository::class);
	}

	/**
	 * @return StylePropertyRepository
	 */
	protected function getPropertyRepo()
	{
		return $this->repository(StylePropertyRepository::class);
	}

	/**
	 * @return AddOnRepository
	 */
	protected function getAddOnRepo()
	{
		return $this->repository(AddOnRepository::class);
	}
}
