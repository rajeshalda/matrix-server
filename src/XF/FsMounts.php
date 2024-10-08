<?php

namespace XF;

use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\EventableFilesystem\Event\After;
use League\Flysystem\EventableFilesystem\EventableFilesystem;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use XF\Util\File;
use XF\Util\Php;

use function call_user_func;

class FsMounts
{
	public static function loadDefaultMounts(array $config)
	{
		$adapterOverrides = $config['fsAdapters'];

		if (isset($adapterOverrides['data']))
		{
			$dataAdapter = call_user_func($adapterOverrides['data']);
		}
		else
		{
			$dataAdapter = static::getLocalAdapter($config['externalDataPath']);
		}

		$data = new EventableFilesystem($dataAdapter, [
			'visibility' => AdapterInterface::VISIBILITY_PUBLIC,
		]);
		static::addDefaultWriteListeners('data', $data);

		if (isset($adapterOverrides['internal-data']))
		{
			$internalDataAdapter = call_user_func($adapterOverrides['internal-data']);
		}
		else
		{
			$internalDataAdapter = static::getLocalAdapter($config['internalDataPath']);
		}

		$internalData = new EventableFilesystem($internalDataAdapter, [
			'visibility' => AdapterInterface::VISIBILITY_PUBLIC,
		]);
		static::addDefaultWriteListeners('internal-data', $internalData);


		if (isset($adapterOverrides['local-data']))
		{
			$localDataAdapter = call_user_func($adapterOverrides['local-data']);
		}
		else
		{
			$localDataAdapter = static::getLocalAdapter($config['localDataPath']);
		}

		$localData = new EventableFilesystem($localDataAdapter, [
			'visibility' => AdapterInterface::VISIBILITY_PUBLIC,
		]);
		static::addDefaultWriteListeners('local-data', $data);

		// if you override this, codeCachePath must still contain the code as we will use it to include files
		if (isset($adapterOverrides['code-cache']))
		{
			$codeCacheAdapter = call_user_func($adapterOverrides['code-cache']);
		}
		else
		{
			$codeCacheAdapter = static::getLocalAdapter(
				sprintf($config['codeCachePath'], $config['internalDataPath'])
			);
		}

		$codeCache = new EventableFilesystem($codeCacheAdapter, [
			'visibility' => AdapterInterface::VISIBILITY_PUBLIC,
		]);
		static::addDefaultWriteListeners('code-cache', $codeCache);

		return new MountManager([
			'data' => $data,
			'internal-data' => $internalData,
			'local-data' => $localData,
			'code-cache' => $codeCache,
		]);
	}

	public static function addDefaultWriteListeners($prefix, EventableFilesystem $fs)
	{
		static::addWriteListeners($prefix, $fs, [self::class, 'handleWriteAction']);
	}

	public static function addWriteListeners($type, EventableFilesystem $fs, callable $callback)
	{
		$f = function ($event) use ($type, $callback)
		{
			$callback($type, $event);
		};

		$fs->addListener('after.put', $f);
		$fs->addListener('after.putstream', $f);
		$fs->addListener('after.readanddelete', $f);
		$fs->addListener('after.write', $f);
		$fs->addListener('after.update', $f);
		$fs->addListener('after.writestream', $f);
		$fs->addListener('after.updatestream', $f);
		$fs->addListener('after.rename', $f);
		$fs->addListener('after.copy', $f);
		$fs->addListener('after.delete', $f);
		$fs->addListener('after.deletedir', $f);
	}

	public static function handleWriteAction($type, After $event)
	{
		$name = $event->getName();
		$arguments = $event->getArguments();
		$first = reset($arguments);
		$second = next($arguments);

		if ($name == 'after.rename')
		{
			$files = [$first, $second];
		}
		else if ($name == 'after.copy')
		{
			// first argument is the copy from, it's not changed
			$files = [$second];
		}
		else
		{
			$files = [$first];
		}

		// handle invalidating opcode caches if we are writing to the code cache
		if ($type == 'code-cache')
		{
			$fs = $event->getFilesystem();
			if ($fs instanceof Filesystem)
			{
				$adapter = $fs->getAdapter();
				if ($adapter instanceof LocalFsAdapter)
				{
					foreach ($files AS $file)
					{
						$filePath = $adapter->applyPathPrefix($file);
						Php::invalidateOpcodeCache($filePath);
					}
				}
			}
		}

		foreach ($files AS $file)
		{
			\XF::fire('mounted_file_write', [$type, $file, $event], $type);
		}
	}

	public static function getLocalAdapter($path)
	{
		$permissions = File::getDefaultFilePermissions();

		if (substr($path, 0, 7) == 'file://')
		{
			$path = substr($path, 7);
		}

		return new LocalFsAdapter(
			File::canonicalizePath($path),
			LOCK_EX,
			Local::DISALLOW_LINKS,
			[
				'file' => ['public' => $permissions['file']],
				'dir' => ['public' => $permissions['dir']],
			]
		);
	}
}
