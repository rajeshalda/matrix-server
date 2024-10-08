<?php

namespace XF\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use XF\Util\File;
use XF\Util\Str;

use function strlen;

/**
 * COLUMNS
 * @property int|null $data_id
 * @property int $user_id
 * @property int $upload_date
 * @property bool $optimized
 * @property string $filename
 * @property string $filename_
 * @property int $file_size
 * @property string $file_hash
 * @property string $file_key
 * @property string $file_path
 * @property int $width
 * @property int $height
 * @property int $thumbnail_width
 * @property int $thumbnail_height
 * @property int $attach_count
 *
 * GETTERS
 * @property-read string $extension
 * @property-read bool $has_thumbnail
 * @property-read string|null $thumbnail_url
 * @property-read bool $is_video
 * @property-read bool $is_audio
 * @property-read string $type_grouping
 *
 * RELATIONS
 * @property-read User|null $User
 */
class AttachmentData extends Entity
{
	/**
	 * @return string
	 */
	public function getExtension()
	{
		return strtolower(pathinfo($this->filename, PATHINFO_EXTENSION));
	}

	public function getAbstractedDataPath()
	{
		return $this->_getAbstractedDataPath(
			$this->data_id,
			$this->file_path,
			$this->file_key
		);
	}

	public function getExistingAbstractedDataPath()
	{
		return $this->_getAbstractedDataPath(
			$this->getExistingValue('data_id'),
			$this->getExistingValue('file_path'),
			$this->getExistingValue('file_key')
		);
	}

	protected function _getAbstractedDataPath($dataId, $filePath, $fileHash)
	{
		$group = floor($dataId / 1000);

		if ($filePath)
		{
			$placeholders = [
				'%INTERNAL%' => 'internal-data://', // for legacy
				'%DATA%' => 'data://', // for legacy
				'%DATA_ID%' => $dataId,
				'%FLOOR%' => $group,
				'%HASH%' => $fileHash,
			];
			$path = strtr($filePath, $placeholders);
			$path = str_replace(':///', '://', $path); // writing %INTERNAL%/path would cause this

			return $path;
		}
		else
		{
			return sprintf(
				'internal-data://attachments/%d/%d-%s.data',
				$group,
				$dataId,
				$fileHash
			);
		}
	}

	public function getAbstractedThumbnailPath()
	{
		return $this->_getAbstractedThumbnailPath(
			$this->data_id,
			$this->file_key
		);
	}

	public function getExistingAbstractedThumbnailPath()
	{
		return $this->_getAbstractedThumbnailPath(
			$this->getExistingValue('data_id'),
			$this->getExistingValue('file_key')
		);
	}

	protected function _getAbstractedThumbnailPath($dataId, $fileKey)
	{
		return sprintf(
			'data://attachments/%d/%d-%s.jpg',
			floor($dataId / 1000),
			$dataId,
			$fileKey
		);
	}

	/**
	 * @return string|null
	 */
	public function getThumbnailUrl($canonical = false)
	{
		if (!$this->thumbnail_width)
		{
			return null;
		}

		$hash = base64_encode(hex2bin($this->file_hash));
		$hash = strtr($hash, '+/', '-_');
		$hash = substr($hash, 0, 10);

		$dataId = $this->data_id;

		$path = sprintf(
			'attachments/%d/%d-%s.jpg?hash=%s',
			floor($dataId / 1000),
			$dataId,
			$this->file_key,
			$hash
		);
		return $this->app()->applyExternalDataUrl($path, $canonical);
	}

	/**
	 * @return bool
	 */
	public function hasThumbnail()
	{
		return $this->thumbnail_width ? true : false;
	}

	public function isVideo(): bool
	{
		if (!$this->file_path)
		{
			return false;
		}

		$extension = strtolower($this->extension);
		return File::isVideoInlineDisplaySafe($extension);
	}

	public function isAudio(): bool
	{
		if (!$this->file_path)
		{
			return false;
		}

		$extension = strtolower($this->extension);
		return File::isAudioInlineDisplaySafe($extension);
	}

	/**
	 * @return string
	 */
	public function getTypeGrouping(): string
	{
		if ($this->isVideo())
		{
			return 'video';
		}

		if ($this->isAudio())
		{
			return 'audio';
		}

		$extension = strtolower($this->extension);
		if (File::isImageInlineDisplaySafe($extension))
		{
			return 'image';
		}

		return 'file';
	}

	/**
	 * If applicable, exposes a public URL to view this attachment data. Otherwise, it will need to be
	 * viewed via a /attachments/ URL.
	 *
	 * @param bool $canonical
	 *
	 * @return string|null
	 */
	public function getPublicUrl(bool $canonical = false)
	{
		$path = $this->file_path;
		if (!strlen($path))
		{
			return null;
		}

		$placeholders = [
			'%INTERNAL%' => 'internal-data://', // for legacy
			'%DATA%' => 'data://', // for legacy
		];
		$path = strtr($path, $placeholders);
		if (substr($path, 0, 7) !== 'data://')
		{
			return null;
		}

		$path = $this->_getAbstractedDataPath(
			$this->data_id,
			substr($path, 7),
			$this->file_key
		);
		return $this->app()->applyExternalDataUrl($path, $canonical);
	}

	public function isDataAvailable()
	{
		$file = $this->getAbstractedDataPath();
		return $file && \XF::app()->fs()->has($file);
	}

	protected function verifyFilePath(&$path)
	{
		if (!strlen($path))
		{
			return true;
		}

		$placeholders = [
			'%INTERNAL%' => 'internal-data://', // for legacy
			'%DATA%' => 'data://', // for legacy
		];
		$path = strtr($path, $placeholders);

		if (!preg_match('#^[a-z0-9-]+://#i', $path))
		{
			throw new \LogicException("Invalid file path. Must be an abstracted path.");
		}

		return true;
	}

	protected function verifyFileName(&$fileName)
	{
		$maxLength = 100; // must match value in structure

		if (Str::strlen($fileName) > $maxLength && $info = @pathinfo($fileName))
		{
			if (!empty($info['extension']))
			{
				$extension = '...' . $info['extension'];
			}
			else
			{
				$extension = '';
			}

			$fileName = Str::substr($info['filename'], 0, $maxLength - Str::strlen($extension)) . $extension;
		}

		return true;
	}

	protected function _preSave()
	{
		if ($this->isInsert() && !$this->file_key)
		{
			$this->file_key = md5(microtime(true) . \XF::generateRandomString(8, true));
		}
	}

	protected function _postDelete()
	{
		$filePath = $this->getAbstractedDataPath();
		File::deleteFromAbstractedPath($filePath);

		$thumbPath = $this->getAbstractedThumbnailPath();
		File::deleteFromAbstractedPath($thumbPath);
	}

	public static function getStructure(Structure $structure)
	{
		$structure->table = 'xf_attachment_data';
		$structure->shortName = 'XF:AttachmentData';
		$structure->primaryKey = 'data_id';
		$structure->columns = [
			'data_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
			'user_id' => ['type' => self::UINT, 'required' => true],
			'upload_date' => ['type' => self::UINT, 'default' => \XF::$time],
			'optimized' => ['type' => self::BOOL, 'default' => false],
			'filename' => ['type' => self::STR, 'maxLength' => 100, // if this is adjusted, see verifyFileName()
				'required' => true, 'censor' => true,
			],
			'file_size' => ['type' => self::UINT, 'required' => true, 'max' => PHP_INT_MAX],
			'file_hash' => ['type' => self::STR, 'maxLength' => 32, 'required' => true],
			'file_key' => ['type' => self::STR, 'maxLength' => 32, 'required' => true],
			'file_path' => ['type' => self::STR, 'maxLength' => 250, 'default' => ''],
			'width' => ['type' => self::UINT, 'default' => 0],
			'height' => ['type' => self::UINT, 'default' => 0],
			'thumbnail_width' => ['type' => self::UINT, 'default' => 0],
			'thumbnail_height' => ['type' => self::UINT, 'default' => 0],
			'attach_count' => ['type' => self::UINT, 'default' => 0, 'forced' => true],
		];
		$structure->getters = [
			'extension' => ['getter' => 'getExtension', 'cache' => false],
			'has_thumbnail' => ['getter' => 'hasThumbnail', 'cache' => false],
			'thumbnail_url' => true,
			'is_video' => ['getter' => 'isVideo', 'cache' => true],
			'is_audio' => ['getter' => 'isAudio', 'cache' => true],
			'type_grouping' => true,
		];
		$structure->relations = [
			'User' => [
				'entity' => 'XF:User',
				'type' => self::TO_ONE,
				'conditions' => 'user_id',
				'primary' => true,
			],
		];

		return $structure;
	}
}
