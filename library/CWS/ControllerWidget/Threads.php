<?php

class CWS_ControllerWidget_Threads extends CWS_ControllerWidget_Abstract
{
	public static function renderOptions(XenForo_ControllerAdmin_Abstract $controller, $widget)
	{
		$widget = self::prepareWidget($widget);

		$viewParams = array(
			'widget' => $widget,
			'options' => $widget['options'],
 			'selNodeIds' => !empty($widget['options']['node_ids']) ? $widget['options']['node_ids'] : array(),
			'nodes' => $controller->getModelFromCache('XenForo_Model_Node')->getAllNodes(),
		);

		return $controller->responseView('CWS_ViewAdmin_Widget_Options', 'cws_options_threads', $viewParams);
	}

	public function actionThreads()
	{
		$visitor = XenForo_Visitor::getInstance();

		$fetchOptions = array(
			'limit' => (int)$this->_options['limit'],
			'order' => 'post_date',
			'orderDirection' => 'desc',
			'join' => XenForo_Model_Thread::FETCH_FORUM_OPTIONS | XenForo_Model_Thread::FETCH_FORUM | XenForo_Model_Thread::FETCH_USER | XenForo_Model_Thread::FETCH_AVATAR,
			'permissionCombinationId' => $visitor['permission_combination_id'],
		);

		$conditions['node_id'] = $this->_options['node_ids'];

		/* @var $threadModel XenForo_Model_Thread*/
		$threadModel = $this->getModelFromCache('XenForo_Model_Thread');

		$threads = $threadModel->getThreads($conditions, $fetchOptions);

		foreach ($threads AS $key => &$thread)
		{
			$thread['permissions'] = XenForo_Permission::unserializePermissions($thread['node_permission_cache']);

			if (!$threadModel->canViewThreadAndContainer($thread, $thread, $null, $thread['permissions'])
				|| $visitor->isIgnoring($thread['user_id'])
			)
			{
				unset($threads[$key]);
			}
		}

		$viewParams['threads'] = $threads;
		$viewParams['widgetTitle'] = $this->_options['title'];

		return $this->responseView('CWS_ViewWidget_Default', 'cws_widget_threads', $viewParams);
	}
}