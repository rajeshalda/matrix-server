<?php

namespace XF\Cli\Command\Development;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XF\Cli\Command\AbstractCommand;
use XF\Install\Helper;

class UpdateVersion extends AbstractCommand
{
	use RequiresDevModeTrait;

	protected function configure()
	{
		$this
			->setName('xf-dev:update-version')
			->setDescription('Updates the XF version to the same version as in the XF files.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$devOutput = \XF::app()->developmentOutput();
		if (!$devOutput->isEnabled() || $devOutput->isAddOnSkipped('XF'))
		{
			$output->writeln('<error>It is only possible to run this command with dev output enabled and XF not skipped.</error>');
			return 1;
		}

		$output->write('Updating database to ' . \XF::$version . ' (' . \XF::$versionId . ')...');

		$helper = new Helper(\XF::app());
		$helper->updateVersion();

		$output->write(' Done!', true);

		return 0;
	}
}
