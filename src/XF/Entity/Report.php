<?php

namespace XF\Entity;

use XF\Api\Result\EntityResult;
use XF\Draft;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use XF\Phrase;
use XF\Report\AbstractHandler;
use XF\Repository\ReportRepository;

/**
 * COLUMNS
 * @property int|null $report_id
 * @property string $content_type
 * @property int $content_id
 * @property int $content_user_id
 * @property array $content_info
 * @property int $first_report_date
 * @property string $report_state
 * @property int $assigned_user_id
 * @property int $comment_count
 * @property int $report_count
 * @property int $last_modified_date
 * @property int $last_modified_user_id
 * @property string $last_modified_username
 *
 * GETTERS
 * @property-read mixed $Content
 * @property-read Draft $draft_comment
 * @property-read AbstractHandler|null $Handler
 * @property-read array $last_modified_cache
 * @property-read Phrase|string $title
 * @property-read string $link
 * @property-read string $content_message
 *
 * RELATIONS
 * @property-read User|null $User
 * @property-read User|null $AssignedUser
 * @property-read User|null $LastModifiedUser
 * @property-read \XF\Mvc\Entity\AbstractCollection<\XF\Entity\ReportComment> $Comments
 * @property-read \XF\Mvc\Entity\AbstractCollection<\XF\Entity\Draft> $DraftComments
 */
class Report extends Entity implements LinkableInterface, ViewableInterface
{
	public function canView()
	{
		$handler = $this->Handler;

		return ($handler && $handler->canView($this));
	}

	public function getReportState($state = null)
	{
		if ($state === null)
		{
			$state = $this->report_state;
		}

		return \XF::phrase('report_state.' . $state);
	}

	public function isAssignedTo($userId = null)
	{
		if ($userId === null)
		{
			$userId = \XF::visitor()->user_id;
		}
		if ($userId instanceof User)
		{
			$userId = $userId->user_id;
		}

		return $this->assigned_user_id && $this->assigned_user_id == $userId;
	}

	/**
	 * @return Draft
	 */
	public function getDraftComment()
	{
		return Draft::createFromEntity($this, 'DraftComments');
	}

	/**
	 * @return Phrase|string
	 */
	public function getTitle()
	{
		$handler = $this->Handler;
		return $handler ? $handler->getContentTitle($this) : '';
	}

	/**
	 * @return string
	 */
	public function getContentMessage(): string
	{
		$handler = $this->Handler;
		return $handler ? $handler->getContentMessage($this) : '';
	}

	/**
	 * @return string
	 */
	public function getLink()
	{
		$handler = $this->Handler;
		return $handler ? $handler->getContentLink($this) : '';
	}

	public function getNewComment()
	{
		$comment = $this->_em->create(ReportComment::class);

		$comment->report_id = $this->_getDeferredValue(function ()
		{
			return $this->report_id;
		}, 'save');

		return $comment;
	}

	public function getContent()
	{
		$handler = $this->Handler;
		return $handler ? $handler->getContent($this->content_id) : null;
	}

	public function setContent(?Entity $content = null)
	{
		$this->_getterCache['Content'] = $content;
	}

	/**
	 * @return AbstractHandler|null
	 */
	public function getHandler()
	{
		return $this->getReportRepo()->getReportHandler($this->content_type);
	}

	public function isClosed()
	{
		return ($this->report_state == 'rejected' || $this->report_state == 'resolved');
	}

	/**
	 * @return array
	 */
	public function getLastModifiedCache()
	{
		return [
			'user_id' => $this->last_modified_user_id,
			'username' => $this->last_modified_username,
			'modified_date' => $this->last_modified_date,
		];
	}

	protected function _postSave()
	{
		$this->rebuildReportCounts();
	}

	protected function _postDelete()
	{
		$this->rebuildReportCounts();
	}

	protected function rebuildReportCounts()
	{
		\XF::runOnce('reportCountsRebuild', function ()
		{
			$this->getReportRepo()->rebuildReportCounts();
		});
	}

	protected function setupApiResultData(
		EntityResult $result,
		$verbosity = self::VERBOSITY_NORMAL,
		array $options = []
	)
	{
		$result->view_url = $this->getContentUrl(true);
	}

	public function getContentUrl(bool $canonical = false, array $extraParams = [], $hash = null)
	{
		$route = $canonical ? 'canonical:reports' : 'reports';
		return $this->app()->router('public')->buildLink($route, $this, $extraParams, $hash);
	}

	public function getContentPublicRoute()
	{
		return 'reports';
	}

	public function getContentTitle(string $context = '')
	{
		return \XF::phrase('report_for_x', [
			'title' => $this->title,
		]);
	}

	public static function getStructure(Structure $structure)
	{
		$structure->table = 'xf_report';
		$structure->shortName = 'XF:Report';
		$structure->contentType = 'report';
		$structure->primaryKey = 'report_id';
		$structure->columns = [
			'report_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
			'content_type' => ['type' => self::STR, 'maxLength' => 25, 'required' => true, 'api' => true],
			'content_id' => ['type' => self::UINT, 'required' => true, 'api' => true],
			'content_user_id' => ['type' => self::UINT, 'required' => true, 'api' => true],
			'content_info' => ['type' => self::JSON_ARRAY, 'required' => true],
			'first_report_date' => ['type' => self::UINT, 'default' => \XF::$time],
			'report_state' => ['type' => self::STR, 'default' => 'open',
				'allowedValues' => ['open', 'assigned', 'resolved', 'rejected'],
				'api' => true,
			],
			'assigned_user_id' => ['type' => self::UINT, 'default' => 0, 'api' => true],
			'comment_count' => ['type' => self::UINT, 'forced' => true, 'default' => 0, 'api' => true],
			'report_count' => ['type' => self::UINT, 'forced' => true, 'default' => 0, 'api' => true],
			'last_modified_date' => ['type' => self::UINT, 'default' => \XF::$time, 'api' => true],
			'last_modified_user_id' => ['type' => self::UINT, 'default' => 0],
			'last_modified_username' => ['type' => self::STR, 'maxLength' => 50, 'default' => ''],
		];
		$structure->behaviors = [
			'XF:Webhook' => [],
		];
		$structure->getters = [
			'Content' => true,
			'draft_comment' => true,
			'Handler' => true,
			'last_modified_cache' => true,
			'title' => true,
			'link' => true,
			'content_message' => true,
		];
		$structure->relations = [
			'User' => [
				'entity' => 'XF:User',
				'type' => self::TO_ONE,
				'conditions' => [
					['user_id', '=', '$content_user_id'],
				],
				'primary' => true,
			],
			'AssignedUser' => [
				'entity' => 'XF:User',
				'type' => self::TO_ONE,
				'conditions' => [
					['user_id', '=', '$assigned_user_id'],
				],
				'primary' => true,
			],
			'LastModifiedUser' => [
				'entity' => 'XF:User',
				'type' => self::TO_ONE,
				'conditions' => [
					['user_id', '=', '$last_modified_user_id'],
				],
				'primary' => true,
			],
			'Comments' => [
				'entity' => 'XF:ReportComment',
				'type' => self::TO_MANY,
				'conditions' => 'report_id',
			],
			'DraftComments' => [
				'entity' => 'XF:Draft',
				'type' => self::TO_MANY,
				'conditions' => [
					['draft_key', '=', 'report-comment-', '$report_id'],
				],
				'key' => 'user_id',
			],
		];

		return $structure;
	}

	/**
	 * @return ReportRepository
	 */
	protected function getReportRepo()
	{
		return $this->repository(ReportRepository::class);
	}
}
