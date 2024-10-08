<?php

namespace XF\Admin\Controller;

class CategoryController extends AbstractNode
{
	protected function getNodeTypeId()
	{
		return 'Category';
	}

	protected function getDataParamName()
	{
		return 'category';
	}

	protected function getTemplatePrefix()
	{
		return 'category';
	}

	protected function getViewClassPrefix()
	{
		return 'XF:Category';
	}
}
