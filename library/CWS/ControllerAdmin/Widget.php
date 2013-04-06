<?php

class CWS_ControllerAdmin_Widget extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('option');
	}

	public function actionIndex()
	{
		$viewParams = array(
			'widgets' => $this->_getWidgetModel()->getAllWidgets(),
		);

		return $this->responseView('XenForo_ViewAdmin_Widget_List', 'cws_widget_list', $viewParams);
	}

	protected function _getWidgetAddEditResponse(array $widget)
	{
		$widgetModel = $this->_getWidgetModel();
        $addOnModel = $this->_getAddOnModel();

        $positionOptions = array(
            'left_sidebar' => new XenForo_Phrase('cws_left_sidebar'),
            'right_sidebar' => new XenForo_Phrase('cws_right_sidebar')
        );

        if(empty($positionOptions[$widget['position']]))
        {
            $positionOptions[$widget['position']] = $widget['position'];
        }

		$viewParams = array(
			'widget' => $widget,

			'userCriteria' => XenForo_Helper_Criteria::prepareCriteriaForSelection($widget['user_criteria']),
			'userCriteriaData' => XenForo_Helper_Criteria::getDataForUserCriteriaSelection(),

			'pageCriteria' => XenForo_Helper_Criteria::prepareCriteriaForSelection($widget['page_criteria']),
			'pageCriteriaData' => XenForo_Helper_Criteria::getDataForPageCriteriaSelection(),

			'showInactiveCriteria' => true,

            'positionOptions' => $positionOptions,

            'addOnOptions' => $addOnModel->getAddOnOptionsListIfAvailable(true, false),
            'addOnSelected' => (isset($widget['addon_id']) ? $widget['addon_id'] : $addOnModel->getDefaultAddOnId())
		);

		return $this->responseView('XenForo_ViewAdmin_Widget_Edit', 'cws_widget_edit', $viewParams);
	}

	public function actionAdd()
	{
		return $this->_getWidgetAddEditResponse($this->_getWidgetModel()->getDefaultWidget());
	}

	public function actionEdit()
	{
		$widgetId = $this->_input->filterSingle('widget_id', XenForo_Input::UINT);
		$widget = $this->_getWidgetOrError($widgetId);

		return $this->_getWidgetAddEditResponse($widget);
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		$widgetId = $this->_input->filterSingle('widget_id', XenForo_Input::UINT);

		$data = $this->_input->filter(array(
			'title' => XenForo_Input::STRING,
            'description' => XenForo_Input::STRING,
            'callback_class' => XenForo_Input::STRING,
            'callback_method' => XenForo_Input::STRING,
			'dismissible' => XenForo_Input::UINT,
			'active' => XenForo_Input::UINT,
			'position' => XenForo_Input::STRING,
			'display_order' => XenForo_Input::UINT,
			'user_criteria' => XenForo_Input::ARRAY_SIMPLE,
			'page_criteria' => XenForo_Input::ARRAY_SIMPLE,
            'addon_id' => XenForo_Input::STRING,
		));

		$dw = XenForo_DataWriter::create('CWS_DataWriter_Widget');
		if ($widgetId)
		{
			$dw->setExistingData($widgetId);
		}
		$dw->bulkSet($data);
		$dw->save();

		$widgetId = $dw->get('widget_id');

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('widgets') . $this->getLastHash($widgetId)
		);
	}

	public function actionDelete()
	{
		$widgetId = $this->_input->filterSingle('widget_id', XenForo_Input::UINT);

		if ($this->isConfirmedPost())
		{
			return $this->_deleteData(
				'CWS_DataWriter_Widget', 'widget_id',
				XenForo_Link::buildAdminLink('widgets')
			);
		}
		else
		{
			$viewParams = array('widget' => $this->_getWidgetOrError($widgetId));

			return $this->responseView('XenForo_ViewAdmin_Widget_Delete', 'cws_widget_delete', $viewParams);
		}
	}

	/**
	 * Selectively enables or disables specified widgets
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionToggle()
	{
		return $this->_getToggleResponse(
			$this->_getWidgetModel()->getAllWidgets(),
			'CWS_DataWriter_Widget',
			'widgets');
	}

	/**
	 * Gets a valid widget or throws an exception.
	 *
	 * @param integer $widgetId
	 *
	 * @return array
	 */
	protected function _getWidgetOrError($widgetId)
	{
		$widgetModel = $this->_getWidgetModel();

		$widget = $widgetModel->getWidgetById($widgetId);
		if (!$widget)
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('cws_requested_widget_not_found'), 404));
		}

		return $widgetModel->prepareWidget($widget);
	}

	/**
	 * @return CWS_Model_Widget
	 */
	protected function _getWidgetModel()
	{
		return $this->getModelFromCache('CWS_Model_Widget');
	}

    /**
     * Get the add-on model.
     *
     * @return XenForo_Model_AddOn
     */
    protected function _getAddOnModel()
    {
        return $this->getModelFromCache('XenForo_Model_AddOn');
    }
}