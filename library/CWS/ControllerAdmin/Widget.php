<?php

class CWS_ControllerAdmin_Widget extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('option');
	}

	public function actionIndex()
	{
		$viewParams = array('widgets' => $this->_getWidgetModel()->getAllWidgets(),);

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

		if (empty($positionOptions[$widget['position']]))
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

		$response = $this->responseView('XenForo_ViewAdmin_Widget_Edit', 'cws_widget_edit', $viewParams);

		$callbackClass = isset($widget['callback_class']) ? $widget['callback_class'] : 'CWS_WidgetHandler_Abstract';

		$widgetHandler = $this->_getWidgetHandler($callbackClass);

		$response->subView = $widgetHandler->renderOptions($this, $widget);

		return $response;
	}

	public function actionAdd()
	{
		return $this->_getWidgetAddEditResponse($this->_getWidgetModel()->getDefaultWidget());
	}

	public function actionEdit()
	{
		$widgetId = $this->_input->filterSingle('widget_id', XenForo_Input::STRING);
		$widget = $this->_getWidgetOrError($widgetId);

		return $this->_getWidgetAddEditResponse($widget);
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		$originalWidgetId = $this->_input->filterSingle('original_widget_id', XenForo_Input::STRING);

		$data = $this->_input->filter(array(
			'widget_id' => XenForo_Input::STRING,
			'description' => XenForo_Input::STRING,
			'callback_class' => XenForo_Input::STRING,
			'callback_method' => XenForo_Input::STRING,
			'options' => XenForo_Input::ARRAY_SIMPLE,
			'dismissible' => XenForo_Input::UINT,
			'active' => XenForo_Input::UINT,
			'position' => XenForo_Input::STRING,
			'display_order' => XenForo_Input::UINT,
			'user_criteria' => XenForo_Input::ARRAY_SIMPLE,
			'page_criteria' => XenForo_Input::ARRAY_SIMPLE,
			'addon_id' => XenForo_Input::STRING,)
		);

		$dw = XenForo_DataWriter::create('CWS_DataWriter_Widget');
		if ($originalWidgetId)
		{
			$dw->setExistingData($originalWidgetId);
		}
		$dw->bulkSet($data);
		$dw->save();

		$widgetId = $dw->get('widget_id');

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildAdminLink('widgets') . $this->getLastHash($widgetId));
	}

	public function actionDelete()
	{
		$widgetId = $this->_input->filterSingle('widget_id', XenForo_Input::STRING);

		if ($this->isConfirmedPost())
		{
			return $this->_deleteData('CWS_DataWriter_Widget', 'widget_id', XenForo_Link::buildAdminLink('widgets'));
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
		return $this->_getToggleResponse($this->_getWidgetModel()->getAllWidgets(), 'CWS_DataWriter_Widget', 'widgets');
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
		$widget = $this->_getWidgetModel()->getWidgetById($widgetId);

		if (!$widget)
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('cws_requested_widget_not_found'), 404));
		}

		return $this->_getWidgetHandler($widget['callback_class'])->prepareWidget($widget);
	}


	public function actionOptions()
	{
		$class = $this->_input->filterSingle('callback_class', XenForo_Input::STRING);
		$widgetId = $this->_input->filterSingle('widget_id', XenForo_Input::STRING);

		$widget = $this->_getWidgetModel()->getWidgetById($widgetId);

		$widgetHandler = $this->_getWidgetHandler($class);

		if($widget)
		{
			$widget = $widgetHandler->prepareWidget($widget);
		}

		return $widgetHandler->renderOptions($this, $widget);
	}

	public function actionSearchMethod()
	{
		$q = $this->_input->filterSingle('q', XenForo_Input::STRING);

		$class = $this->_input->filterSingle('class', XenForo_Input::STRING);

		$methods = array();

		if(strpos($class, 'WidgetHandler') && XenForo_Application::autoload($class))
		{
			foreach(get_class_methods($class) as $method)
			{
				if(strpos($method, $q) === 0 && !in_array($method, array('__construct', 'renderOptions', 'prepareWidget')))
				{
					$methods[] = $method;
				}
			}

			sort($methods);
		}

		$viewParams = array(
			'values' => $methods
		);

		return $this->responseView('CWS_ViewAdmin_Widget_SearchParam', '', $viewParams);
	}

	public function actionSearchClass()
	{
		$q = $this->_input->filterSingle('q', XenForo_Input::STRING);

		$classes = array();

		if ($q !== '')
		{
			$libraryDir = XenForo_Application::getInstance()->getRootDir() . '/library';

			$filePaths = array();
			$this->_scanDir($libraryDir, $filePaths);

			foreach($filePaths as $filePath)
			{
				$class = str_replace($libraryDir.'/', '', $filePath);
				$class = str_replace('/', '_', $class);
				$class = str_replace('.php', '', $class);

				if(strpos($class, $q) === 0 && strpos($class, 'WidgetHandler') && XenForo_Application::autoload($class))
				{
					$classes[] = $class;
				}
			}
		}

		$viewParams = array(
			'values' => $classes
		);

		return $this->responseView('CWS_ViewAdmin_Widget_SearchParam', '', $viewParams);
	}

	protected function _scanDir($dir, array &$filePaths)
	{
		foreach (scandir($dir) as $filename) {
			if (is_dir($dir . '/' . $filename) && $filename != '.' && $filename != '..') {
				$this->_scanDir($dir . '/' . $filename, $filePaths);
			}
			elseif (is_file($dir . '/' . $filename)) {
				$filePaths[] = $dir . '/' . $filename;
			}
		}
	}

	/**
	 * @param string $class Full class name, or partial suffix (if no underscore)
	 *
	 * @return CWS_WidgetHandler_Abstract
	 */
	public function _getWidgetHandler($class)
	{
		$class = is_callable(array($class, 'renderOptions')) &&
			is_callable(array($class, 'prepareWidget')) &&
			is_callable(array($class, 'prepareOptions')) ?
			$class : 'CWS_WidgetHandler_Abstract';

		return new $class();
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