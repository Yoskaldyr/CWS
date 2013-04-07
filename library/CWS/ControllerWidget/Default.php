<?php

class CWS_ControllerWidget_Default extends CWS_ControllerWidget_Abstract
{
	public function actionHtml($argument = '')
	{
		return $argument;
	}

	public function actionTemplate($argument = '')
	{
		return $this->responseView('CWS_ViewWidget_Default', $argument, self::$params);
	}

	public function actionDefaultSidebar()
	{
		return !empty(self::$params['sidebar']) ? self::$params['sidebar'] : '';
	}

	public function actionOnlineUsers()
	{
		$visitor = XenForo_Visitor::getInstance();

		/* @var $sessionModel XenForo_Model_Session */
		$sessionModel = $this->getModelFromCache('XenForo_Model_Session');

		self::$params['onlineUsers'] = isset(self::$params['onlineUsers']) ? self::$params['onlineUsers'] :
			$sessionModel->getSessionActivityQuickList(
				$visitor->toArray(),
				array('cutOff' => array('>', $sessionModel->getOnlineStatusTimeout())),
				($visitor['user_id'] ? $visitor->toArray() : null)
			);

		return $this->responseView('CWS_ViewWidget_Default', 'cws_widget_online_users', self::$params);
	}

	public function actionOnlineStaff()
	{
		$visitor = XenForo_Visitor::getInstance();

		/* @var $sessionModel XenForo_Model_Session */
		$sessionModel = $this->getModelFromCache('XenForo_Model_Session');

		self::$params['onlineUsers'] = isset(self::$params['onlineUsers']) ? self::$params['onlineUsers'] :
			$sessionModel->getSessionActivityQuickList(
				$visitor->toArray(),
				array('cutOff' => array('>', $sessionModel->getOnlineStatusTimeout())),
				($visitor['user_id'] ? $visitor->toArray() : null)
			);

		return $this->responseView('CWS_ViewWidget_Default', 'cws_widget_online_staff', self::$params);
	}

	public function actionBoardTotals()
	{
		self::$params['boardTotals'] = isset(self::$params['boardTotals']) ? self::$params['boardTotals'] : null;

		if (self::$params['boardTotals'] === null)
		{
			self::$params['boardTotals'] = $this->getModelFromCache('XenForo_Model_DataRegistry')->get('boardTotals');

			if (!self::$params['boardTotals'])
			{
				self::$params['boardTotals'] = $this->getModelFromCache('XenForo_Model_Counters')->rebuildBoardTotalsCounter();
			}
		}

		return $this->responseView('CWS_ViewWidget_Default', 'cws_widget_forum_stats', self::$params);
	}

	public function actionSharePage()
	{
		$requestPaths = XenForo_Application::get('requestPaths');

		$viewParams['url'] = $requestPaths['fullUri'];

		return $this->responseView('CWS_ViewWidget_Default', 'sidebar_share_page', $viewParams);
	}

}