<?php

namespace XF\Service\Banning\Emails;

use XF\Repository\BanningRepository;
use XF\Service\AbstractXmlImport;
use XF\Util\Xml;

use function in_array;

class ImportService extends AbstractXmlImport
{
	public function import(\SimpleXMLElement $xml)
	{
		$bannedEmailsCache = (array) $this->app->container('bannedEmails');
		$bannedEmailsCache = array_map('strtolower', $bannedEmailsCache);

		$entries = $xml->entry;
		foreach ($entries AS $entry)
		{
			if (in_array(strtolower((string) $entry['banned_email']), $bannedEmailsCache))
			{
				// already exists
				continue;
			}

			$this->repository(BanningRepository::class)->banEmail(
				(string) $entry['banned_email'],
				Xml::processSimpleXmlCdata($entry->reason)
			);
		}
	}
}
