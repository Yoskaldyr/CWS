<?php

class CWS_WidgetHandler_Default extends CWS_WidgetHandler_Abstract
{
	public function actionDefaultSidebar(XenForo_ControllerPublic_Abstract $controller, array $options = array())
	{
		if(!empty(self::$params['sidebar']))
		{
			return $controller->responseView('CWS_ViewWidget_Default', 'cws_widget_default_sidebar', self::$params);
		}
		else
		{
			return null;
		}
	}

	public function actionOnlineUsers(XenForo_ControllerPublic_Abstract $controller, array $options = array())
	{
		$visitor = XenForo_Visitor::getInstance();

		/* @var $sessionModel XenForo_Model_Session */
		$sessionModel = $controller->getModelFromCache('XenForo_Model_Session');

		self::$params['onlineUsers'] = isset(self::$params['onlineUsers']) ? self::$params['onlineUsers'] :
			$sessionModel->getSessionActivityQuickList(
				$visitor->toArray(),
				array('cutOff' => array('>', $sessionModel->getOnlineStatusTimeout())),
				($visitor['user_id'] ? $visitor->toArray() : null)
			);

		return $controller->responseView('CWS_ViewWidget_Default', 'cws_widget_online_users', self::$params);
	}

	public function actionOnlineStaff(XenForo_ControllerPublic_Abstract $controller, array $options = array())
	{
		$visitor = XenForo_Visitor::getInstance();

		/* @var $sessionModel XenForo_Model_Session */
		$sessionModel = $controller->getModelFromCache('XenForo_Model_Session');

		self::$params['onlineUsers'] = isset(self::$params['onlineUsers']) ? self::$params['onlineUsers'] :
			$sessionModel->getSessionActivityQuickList(
				$visitor->toArray(),
				array('cutOff' => array('>', $sessionModel->getOnlineStatusTimeout())),
				($visitor['user_id'] ? $visitor->toArray() : null)
			);

		return $controller->responseView('CWS_ViewWidget_Default', 'cws_widget_online_staff', self::$params);
	}

	public function actionBoardTotals(XenForo_ControllerPublic_Abstract $controller, array $options = array())
	{
		self::$params['boardTotals'] = isset(self::$params['boardTotals']) ? self::$params['boardTotals'] : null;

		if (self::$params['boardTotals'] === null)
		{
			self::$params['boardTotals'] = $controller->getModelFromCache('XenForo_Model_DataRegistry')->get('boardTotals');

			if (!self::$params['boardTotals'])
			{
				self::$params['boardTotals'] = $controller->getModelFromCache('XenForo_Model_Counters')->rebuildBoardTotalsCounter();
			}
		}

		return $controller->responseView('CWS_ViewWidget_Default', 'cws_widget_forum_stats', self::$params);
	}

	public function actionSharePage(XenForo_ControllerPublic_Abstract $controller, array $options = array())
	{
		$requestPaths = XenForo_Application::get('requestPaths');

		$viewParams['url'] = $requestPaths['fullUri'];

		return $controller->responseView('CWS_ViewWidget_Default', 'sidebar_share_page', $viewParams);
	}

}