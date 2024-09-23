<?php

namespace XF\Cli\Command\AddOn;

use Symfony\Component\Console\Input\InputArgument;

class UninstallStep extends AbstractSetupStep
{
	protected function getStepType()
	{
		return 'uninstall';
	}

	protected function getCommandArguments()
	{
		$this->addArgument(
			'step',
			InputArgument::REQUIRED,
			'The step number to run.'
		);
	}
}
