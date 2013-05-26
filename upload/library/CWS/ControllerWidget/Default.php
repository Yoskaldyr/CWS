<?php

class CWS_ControllerWidget_Default extends CWS_ControllerWidget_Abstract
{
	public function actionDefaultSidebar()
	{
		if(!empty(CWS_Static::$params['sidebar']))
		{
			return $this->responseView('CWS_ViewWidget_Default', 'cws_widget_default_sidebar', CWS_Static::$params);
		}
		else
		{
			return null;
		}
	}

	public function actionOnlineUsers()
	{
		$visitor = XenForo_Visitor::getInstance();

		/* @var $sessionModel XenForo_Model_Session */
		$sessionModel = $this->getModelFromCache('XenForo_Model_Session');

		CWS_Static::$params['onlineUsers'] = isset(CWS_Static::$params['onlineUsers']) ? CWS_Static::$params['onlineUsers'] :
			$sessionModel->getSessionActivityQuickList(
				$visitor->toArray(),
				array('cutOff' => array('>', $sessionModel->getOnlineStatusTimeout())),
				($visitor['user_id'] ? $visitor->toArray() : null)
			);

		return $this->responseView('CWS_ViewWidget_Default', 'cws_widget_online_users', CWS_Static::$params);
	}

	public function actionOnlineStaff()
	{
		$visitor = XenForo_Visitor::getInstance();

		/* @var $sessionModel XenForo_Model_Session */
		$sessionModel = $this->getModelFromCache('XenForo_Model_Session');

		CWS_Static::$params['onlineUsers'] = isset(CWS_Static::$params['onlineUsers']) ? CWS_Static::$params['onlineUsers'] :
			$sessionModel->getSessionActivityQuickList(
				$visitor->toArray(),
				array('cutOff' => array('>', $sessionModel->getOnlineStatusTimeout())),
				($visitor['user_id'] ? $visitor->toArray() : null)
			);

		return $this->responseView('CWS_ViewWidget_Default', 'cws_widget_online_staff', CWS_Static::$params);
	}

	public function actionBoardTotals()
	{
		CWS_Static::$params['boardTotals'] = isset(CWS_Static::$params['boardTotals']) ? CWS_Static::$params['boardTotals'] : null;

		if (CWS_Static::$params['boardTotals'] === null)
		{
			CWS_Static::$params['boardTotals'] = $this->getModelFromCache('XenForo_Model_DataRegistry')->get('boardTotals');

			if (!CWS_Static::$params['boardTotals'])
			{
				CWS_Static::$params['boardTotals'] = $this->getModelFromCache('XenForo_Model_Counters')->rebuildBoardTotalsCounter();
			}
		}

		return $this->responseView('CWS_ViewWidget_Default', 'cws_widget_forum_stats', CWS_Static::$params);
	}

	public function actionSharePage()
	{
		$requestPaths = XenForo_Application::get('requestPaths');

		$viewParams['url'] = $requestPaths['fullUri'];

		return $this->responseView('CWS_ViewWidget_Default', 'sidebar_share_page', $viewParams);
	}

}