<?php

namespace XF\Import\Data;

use XF\Entity\AttachmentData;
use XF\FileWrapper;
use XF\Mvc\Entity\Entity;
use XF\Service\Attachment\PreparerService;
use XF\Util\File;

/**
 * @mixin \XF\Entity\Attachment
 */
class Attachment extends AbstractEmulatedData
{
	/**
	 * @var AttachmentData|null
	 */
	protected $data;

	/**
	 * @var FileWrapper|null
	 */
	protected $sourceFile;

	protected $dataExtras = [];

	protected $dataUserId = null;

	/**
	 * @var callable|null
	 */
	protected $containerCallback;

	public function getImportType()
	{
		return 'attachment';
	}

	public function getEntityShortName()
	{
		return 'XF:Attachment';
	}

	public function setDataUserId($userId)
	{
		$this->dataUserId = $userId;
	}

	public function setDataExtra($key, $value)
	{
		$this->dataExtras[$key] = $value;
	}

	public function setSourceFile($sourceFile, $fileName = '')
	{
		$fileName = $this->convertToUtf8($fileName);

		$this->sourceFile = new FileWrapper($sourceFile, $fileName);
	}

	public function setContainerCallback(callable $callback)
	{
		$this->containerCallback = $callback;
	}

	protected function write($oldId)
	{
		if (!$this->data)
		{
			if (!isset($this->dataExtras['file_path']))
			{
				$extension = strtolower($this->sourceFile->getExtension());

				if (File::isVideoInlineDisplaySafe($extension))
				{
					$this->dataExtras['file_path'] = strtr(
						PreparerService::INLINE_VIDEO_PATH,
						['%EXTENSION%' => $extension]
					);
				}
				else if (File::isAudioInlineDisplaySafe($extension))
				{
					$this->dataExtras['file_path'] = strtr(
						PreparerService::INLINE_AUDIO_PATH,
						['%EXTENSION%' => $extension]
					);
				}
			}

			/** @var PreparerService $attachPreparer */
			$attachPreparer = $this->app()->service(PreparerService::class);
			$this->data = $attachPreparer->insertDataFromFile($this->sourceFile, $this->dataUserId, $this->dataExtras);
		}

		$this->ee->set('data_id', $this->data->data_id);

		return $this->ee->insert($oldId, $this->db());
	}

	protected function preSave($oldId)
	{
		if (!$this->sourceFile)
		{
			throw new \LogicException("Must set a source file");
		}
		if ($this->dataUserId === null)
		{
			throw new \LogicException("Must set a data user ID (can be 0)");
		}
	}

	protected function postSave($oldId, $newId)
	{
		$this->data->fastUpdate('attach_count', 1);

		$attachment = $this->em()->find(\XF\Entity\Attachment::class, $newId);
		if ($attachment && $attachment->Container)
		{
			/** @var Entity $container */
			$container = $attachment->Container;
			if (isset($container->attach_count))
			{
				$container->attach_count++;
			}

			if ($this->containerCallback)
			{
				$callback = $this->containerCallback;
				$callback($container, $attachment, $oldId, $this->dataExtras);
			}

			$container->saveIfChanged($saved, false, false);
			$this->em()->detachEntity($attachment);
			$this->em()->detachEntity($container);
		}
	}
}
