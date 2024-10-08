<?php

namespace XF\Import\Data;

/**
 * @mixin \XF\Entity\UserField
 */
class UserField extends AbstractField
{
	public function getImportType()
	{
		return 'user_field';
	}

	public function getEntityShortName()
	{
		return 'XF:UserField';
	}
}
