<?php

namespace XF\SubContainer;

use GuzzleHttp\Client;
use XF\Http\MetadataFetcher;
use XF\Http\Reader;

class Http extends AbstractSubContainer
{
	public function initialize()
	{
		$container = $this->container;

		$container['client'] = function ($c)
		{
			return $this->createClient();
		};
		$container['clientUntrusted'] = function ($c)
		{
			$options = ['allow_redirects' => false];

			$config = $this->app->config();
			if ($config['http']['proxy'])
			{
				$options['proxy'] = $config['http']['proxy'];
			}

			$this->app->fire('http_client_options_untrusted', [&$options]);

			$client = $this->createClient($options);

			$this->app->fire('http_client_config_untrusted', [&$client]);

			return $client;
		};

		$container['reader'] = function ($c)
		{
			return new Reader($c['client'], $c['clientUntrusted']);
		};

		$container['metadataFetcher'] = function ($c)
		{
			$class = MetadataFetcher::class;
			$class = $this->extendClass($class);

			return new $class($this->app, $c['reader']);
		};
	}

	protected function applyDefaultClientOptions(array $options)
	{
		$config = $this->app->config();
		if ($config['http']['sslVerify'] === null)
		{
			$bundleFileName = 'ca-bundle.crt';

			if (extension_loaded('curl')) // this should always be true...
			{
				$version = curl_version();
				if (preg_match('#openssl/(0|1\.0\.[01])#i', $version['ssl_version']))
				{
					// For OpenSSL < 1.0.2, we need to use a bundle that includes the Equifax cert as it will
					// always check if it knows the last certificate in the path. Google cross signs their certificates
					// with a known cert and the now-untrusted Equifax cert. See this for more details:
					// https://serverfault.com/questions/841036/openssl-unable-to-get-local-issuer-certificate-for-accounts-google-com
					$bundleFileName = 'ca-bundle-legacy-openssl.crt';
				}
			}

			$verify = \XF::getSourceDirectory() . "/XF/Http/" . $bundleFileName;
		}
		else
		{
			$verify = $config['http']['sslVerify'];
		}

		$xfOptions = $this->parent['options'];
		$options = array_replace_recursive([
			'verify' => $verify,
			'headers' => [
				'User-Agent' => 'XenForo/2.x (' . $xfOptions->boardUrl . ')',
			],
		], $options);

		$this->app->fire('http_client_options', [&$options]);

		return $options;
	}

	/**
	 * @return array
	 */
	public function getDefaultClientOptions()
	{
		return $this->applyDefaultClientOptions([]);
	}

	/**
	 * @param array $options
	 *
	 * @return Client
	 */
	public function createClient(array $options = [])
	{
		$options = $this->applyDefaultClientOptions($options);
		$client = new Client($options);

		$this->app->fire('http_client_config', [&$client]);

		return $client;
	}

	/**
	 * @return Client
	 */
	public function client()
	{
		return $this->container['client'];
	}

	/**
	 * @return Reader
	 */
	public function reader()
	{
		return $this->container['reader'];
	}

	/**
	 * @return MetadataFetcher
	 */
	public function metadataFetcher()
	{
		return $this->container['metadataFetcher'];
	}
}
