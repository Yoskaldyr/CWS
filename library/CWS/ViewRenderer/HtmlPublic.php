<?php

/**
 * Concrete renderer for HTML output.
 *
 * @package XenForo_Mvc
 */
class CWS_ViewRenderer_HtmlPublic extends XenForo_ViewRenderer_HtmlPublic
{
	protected $_controllerWidgetCache = array();
	protected $_params = array();

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

		if(XenForo_Application::isRegistered('session'))
		{
			$dismissedWidgets = XenForo_Application::getSession()->get('dismissedWidgets');
		}

		if(!isset($dismissedWidgets) || !is_array($dismissedWidgets))
		{
			$dismissedWidgets = array();
		}

		$this->_params = XenForo_Application::mapMerge($template->getParams(), $containerData, CWS_Static::$controllerResponse->params);

		foreach($allWidgets AS $widgetId => $widget)
		{
			$widgetPosition = $widget['position'];
			$widgetCallback = array($widget['callback_class'], $widget['callback_method']);

			if(!in_array($widgetId, $dismissedWidgets) && XenForo_Helper_Criteria::userMatchesCriteria($widget['user_criteria'], true, $user) && XenForo_Helper_Criteria::pageMatchesCriteria($widget['page_criteria'], true, $template->getParams(), $containerData) && is_callable($widgetCallback)
			)
			{
				$widgetController = $this->getControllerWidgetFromCache($widget['callback_class']);

				$widgetControllerResponse = call_user_func_array(array($widgetController, $widget['callback_method']), array());

				if($widgetControllerResponse instanceof XenForo_ControllerResponse_View)
				{
					$widgets[$widgetPosition][$widgetId] = $this->renderView($widgetControllerResponse->viewName, $widgetControllerResponse->params, $widgetControllerResponse->templateName, $widgetControllerResponse->subView);
				}
				elseif($widgetControllerResponse)
				{
					$widgets[$widgetPosition][$widgetId] = $widgetControllerResponse;
				}

			}
		}

		CWS_Static::$widgets = $widgets;

		return parent::_getNoticesContainerParams($template, $containerData);
	}

	/**
	 * Gets the specified model object from the cache. If it does not exist,
	 * it will be instantiated.
	 *
	 * @param string $class Name of the class to load
	 *
	 * @return CWS_ControllerWidget_Abstract
	 */
	public function getControllerWidgetFromCache($class)
	{
		if(!isset($this->_controllerWidgetCache[$class]))
		{
			$this->_controllerWidgetCache[$class] = new $class(CWS_Static::$controller, $this->_params);
		}

		return $this->_controllerWidgetCache[$class];
	}
}