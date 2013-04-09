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
	protected $_widgetHandlerCache = array();

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
		$allWidgets = XenForo_Application::isRegistered('widgets') ? XenForo_Application::get('widgets') : array();
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

		CWS_WidgetHandler_Abstract::$containerParams = XenForo_Application::mapMerge($template->getParams(), $containerData);
		CWS_WidgetHandler_Abstract::$params = XenForo_Application::mapMerge(CWS_WidgetHandler_Abstract::$innerParams, CWS_WidgetHandler_Abstract::$containerParams);

		foreach ($allWidgets AS $widgetId => $widget)
		{
			$widgetPosition = $widget['position'];
			$widgetCallback = array($widget['callback_class'], $widget['callback_method']);

			if (!in_array($widgetId, $dismissedWidgets) && is_callable($widgetCallback) &&
				XenForo_Helper_Criteria::userMatchesCriteria($widget['user_criteria'], true, $user) &&
				XenForo_Helper_Criteria::pageMatchesCriteria($widget['page_criteria'], true, $template->getParams(), $containerData)
			)
			{
				$widgetHandler = $this->_getWidgetHandlerFromCache($widget['callback_class']);

				if(!($widgetHandler instanceof CWS_WidgetHandler_Abstract))
				{
					throw new XenForo_Exception(
						new XenForo_Phrase('cws_callback_class_of_widget_x_must_extend_class_y',
							array(
								'widget' => $widget['widget_id'],
								'class' => 'CWS_WidgetHandler_Abstract'
							)
						));
				}

				$widgetResponse = call_user_func_array(
					array($widgetHandler, $widget['callback_method']),
					array(
						self::$controller,
						$widgetHandler->prepareOptions($widget['options'])
					)
				);

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

	/**
	 * @param string $class Full class name, or partial suffix (if no underscore)
	 *
	 * @return CWS_WidgetHandler_Abstract
	 */
	protected function _getWidgetHandlerFromCache($class)
	{
		if(!isset($this->_widgetHandlerCache[$class]))
		{
			$this->_widgetHandlerCache[$class] = new $class();
		}

		return $this->_widgetHandlerCache[$class];
	}
}