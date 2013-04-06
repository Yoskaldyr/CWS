<?php

class CWS_ControllerWidget_Default extends CWS_ControllerWidget_Abstract
{
    public function actionDefaultSidebar()
    {
        return !empty($this->_params['sidebar']) ? $this->_params['sidebar'] : '';
    }


	public function actionOnlineUsers()
	{
        $visitor = XenForo_Visitor::getInstance();

        /* @var $sessionModel XenForo_Model_Session */
        $sessionModel = $this->getModelFromCache('XenForo_Model_Session');

        $this->_params['onlineUsers'] = isset($this->_params['onlineUsers']) ? $this->_params['onlineUsers'] :
            $sessionModel->getSessionActivityQuickList(
                $visitor->toArray(),
                array('cutOff' => array('>', $sessionModel->getOnlineStatusTimeout())),
                ($visitor['user_id'] ? $visitor->toArray() : null)
            );

        return $this->responseView('CWS_ViewWidget_Default', 'cws_widget_online_users', $this->_params);
	}

    public function actionOnlineStaff()
    {
        $visitor = XenForo_Visitor::getInstance();

        /* @var $sessionModel XenForo_Model_Session */
        $sessionModel = $this->getModelFromCache('XenForo_Model_Session');

        $this->_params['onlineUsers'] = isset($this->_params['onlineUsers']) ? $this->_params['onlineUsers'] :
            $sessionModel->getSessionActivityQuickList(
                $visitor->toArray(),
                array('cutOff' => array('>', $sessionModel->getOnlineStatusTimeout())),
                ($visitor['user_id'] ? $visitor->toArray() : null)
            );

        return $this->responseView('CWS_ViewWidget_Default', 'cws_widget_online_staff', $this->_params);
    }

    public function actionBoardTotals()
    {
        $this->_params['boardTotals'] = isset($this->_params['boardTotals']) ? $this->_params['boardTotals'] : null;

        if($this->_params['boardTotals'] === null)
        {
            $this->_params['boardTotals'] = $this->getModelFromCache('XenForo_Model_DataRegistry')->get('boardTotals');

            if (!$this->_params['boardTotals'])
            {
                $this->_params['boardTotals'] = $this->getModelFromCache('XenForo_Model_Counters')->rebuildBoardTotalsCounter();
            }
        }

        return $this->responseView('CWS_ViewWidget_Default', 'cws_widget_forum_stats', $this->_params);
    }

    public function actionSharePage()
    {
        $requestPaths = XenForo_Application::get('requestPaths');

        $viewParams['url'] = $requestPaths['fullUri'];

        return $this->responseView('CWS_ViewWidget_Default', 'sidebar_share_page', $viewParams);
    }

}