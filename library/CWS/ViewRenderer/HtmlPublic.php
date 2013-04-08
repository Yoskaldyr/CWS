<?php

/**
 * Concrete renderer for HTML output.
 *
 * @package XenForo_Mvc
 */
class CWS_ViewRenderer_HtmlPublic extends XenForo_ViewRenderer_HtmlPublic
{
	/**
	 * @var array
	 */
	public static $widgets = array();

	/**
	 * @var XenForo_Controller
	 */
	public static $controller;

	/**
	 * Constructor
	 * @see XenForo_ViewRenderer_Abstract::__construct()
	 */
	public function __construct(XenForo_ViewRenderer_HtmlPublic $viewRenderer)
	{
		$this->_contentTemplate = $viewRenderer->_contentTemplate;
		$this->_dependencies = $viewRenderer->_dependencies;
		$this->_needsContainer = $viewRenderer->_needsContainer;
		$this->_request = $viewRenderer->_request;
		$this->_response = $viewRenderer->_response;
	}

	protected function _getNoticesContainerParams(XenForo_Template_Abstract $template, array $containerData)
	{
		/* @var $widgetModel CWS_Model_Widget */
		$widgetModel = XenForo_Model::create('CWS_Model_Widget');
		$allWidgets = $widgetModel->rebuildWidgetCache();
		$widgets = array();

		$user = XenForo_Visitor::getInstance()->toArray();

		if (XenForo_Application::isRegistered('session'))
		{
			$dismissedWidgets = XenForo_Application::getSession()->get('dismissedWidgets');
		}

		if (!isset($dismissedWidgets) || !is_array($dismissedWidgets))
		{
			$dismissedWidgets = array();
		}

		CWS_ControllerHelper_Widget::$containerParams = XenForo_Application::mapMerge($template->getParams(), $containerData);
		CWS_ControllerHelper_Widget::$params = XenForo_Application::mapMerge(CWS_ControllerHelper_Widget::$innerParams, CWS_ControllerHelper_Widget::$containerParams);

		foreach ($allWidgets AS $widgetId => $widget)
		{
			$widgetPosition = $widget['position'];
			$widgetCallback = array($widget['callback_class'], $widget['callback_method']);

			if (!in_array($widgetId, $dismissedWidgets) && is_callable($widgetCallback) &&
				XenForo_Helper_Criteria::userMatchesCriteria($widget['user_criteria'], true, $user) &&
				XenForo_Helper_Criteria::pageMatchesCriteria($widget['page_criteria'], true, $template->getParams(), $containerData)
			)
			{
				$widgetHelper = new $widget['callback_class'](self::$controller, $widget['options']);

				$widgetResponse = call_user_func_array(array($widgetHelper, $widget['callback_method']), array($widget['options']));

				if ($widgetResponse instanceof XenForo_ControllerResponse_View)
				{
					$widgets[$widgetPosition][$widgetId] = $this->renderView(
						$widgetResponse->viewName,
						$widgetResponse->params,
						$widgetResponse->templateName,
						$widgetResponse->subView
					);
				}
				elseif ($widgetResponse)
				{
					$widgets[$widgetPosition][$widgetId] = $widgetResponse;
				}
			}
		}

		self::$widgets = $widgets;

		return parent::_getNoticesContainerParams($template, $containerData);
	}
}