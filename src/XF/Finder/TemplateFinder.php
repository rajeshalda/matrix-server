<?php

namespace XF\Finder;

use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Entity\Finder;

use function strlen;

/**
 * @method AbstractCollection<\XF\Entity\Template> fetch(?int $limit = null, ?int $offset = null)
 * @method AbstractCollection<\XF\Entity\Template> fetchDeferred(?int $limit = null, ?int $offset = null)
 * @method \XF\Entity\Template|null fetchOne(?int $offset = null)
 * @extends Finder<\XF\Entity\Template>
 */
class TemplateFinder extends Finder
{
	public function fromAddOn($addOnId)
	{
		if ($addOnId == '_any')
		{
			return $this;
		}
		$this->where('addon_id', $addOnId);
		return $this;
	}

	public function searchTitle($match)
	{
		if (strlen($match))
		{
			$this->where(
				$this->columnUtf8('title'),
				'LIKE',
				$this->escapeLike($match, '%?%')
			);
		}

		return $this;
	}

	public function searchTemplate($match, $caseSensitive = false)
	{
		if (strlen($match))
		{
			$expression = 'template';
			if ($caseSensitive)
			{
				$expression = $this->expression('BINARY %s', $expression);
			}

			$this->where($expression, 'LIKE', $this->escapeLike($match, '%?%'));
		}

		return $this;
	}

	public function orderTitle($direction = 'ASC')
	{
		$expression = $this->columnUtf8('title');
		$this->order($expression, $direction);

		return $this;
	}
}
