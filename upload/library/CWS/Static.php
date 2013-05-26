<?php

class CWS_Static
{
	/**
	 * @var array
	 */
	public static $widgets = array();

	/**
	 * @var array
	 */
	public static $hmvc = array();

	/**
	 * @var Zend_Controller_Request_Http
	 */
	public static $request;

	/**
	 * @var Zend_Controller_Response_Http
	 */
	public static $response;

	/**
	 * @var XenForo_RouteMatch
	 */
	public static $routeMatch;

	/**
	 * @var array
	 */
	public static $params;

	/**
	 * @var array
	 */
	public static $innerParams;

	/**
	 * @var array
	 */
	public static $containerParams;

	public static function initDependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		/* @var $widgetModel CWS_Model_Widget */
		$widgetModel = XenForo_Model::create('CWS_Model_Widget');
		$allWidgets = $widgetModel->rebuildWidgetCache();

		XenForo_Application::set('widgets', $allWidgets);
	}

	public static function loadClassModel($class, array &$extend)
	{
		if ($class == 'XenForo_Model_AddOn')
		{
			$extend[] = 'CWS_Model_AddOn';
		}
	}

	public static function loadClassDataWriter($class, array &$extend)
	{
		if ($class == 'XenForo_DataWriter_AddOn')
		{
			$extend[] = 'CWS_DataWriter_AddOn';
		}
	}

	public static function controllerPostDispatch(XenForo_Controller $controller, $controllerResponse, $controllerName, $action)
	{
		if ($controller instanceof XenForo_ControllerPublic_Abstract && $controllerResponse instanceof XenForo_ControllerResponse_View)
		{
			//self::$controller = $controller;
		}
	}

	public static function frontControllerPreDispatch(XenForo_FrontController $fc, XenForo_RouteMatch &$routeMatch)
	{
		if ($fc->getDependencies() instanceof XenForo_Dependencies_Public)
		{
			self::$request = $fc->getRequest();
			self::$response = $fc->getResponse();
			self::$routeMatch = $routeMatch;
		}
	}

	public static function frontControllerPreView(XenForo_FrontController $fc, XenForo_ControllerResponse_Abstract &$controllerResponse, XenForo_ViewRenderer_Abstract &$viewRenderer, array &$containerParams)
	{
		if ($fc->getDependencies() instanceof XenForo_Dependencies_Public &&
			$controllerResponse instanceof XenForo_ControllerResponse_View &&
			$viewRenderer instanceof XenForo_ViewRenderer_HtmlPublic
		)
		{
			$specificContainerParams = XenForo_Application::mapMerge(
				$containerParams,
				$controllerResponse->containerParams
			);

			CWS_Static::$containerParams = $viewRenderer->getDependencyHandler()->getEffectiveContainerParams($specificContainerParams, $fc->getRequest());

			CWS_Static::$innerParams = $controllerResponse->params;

			$wrapper = new XenForo_ControllerResponse_View();
			$wrapper->containerParams = $controllerResponse->containerParams;
			$wrapper->controllerAction = $controllerResponse->controllerAction;
			$wrapper->controllerName = $controllerResponse->controllerName;
			$wrapper->params = $controllerResponse->params;
			$wrapper->responseCode = $controllerResponse->responseCode;
			$wrapper->subView = $controllerResponse;
			$wrapper->templateName = $controllerResponse->templateName;
			$wrapper->viewName = 'CWS_ViewPublic_Wrapper';

			$controllerResponse = $wrapper;
		}
	}

	public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		if ($templateName == 'PAGE_CONTAINER' && $template instanceof XenForo_Template_Public)
		{
			//CWS_Static::$containerParams = XenForo_Application::mapMerge($template->getParams(), $params);
			$params['widgets'] = & self::$widgets;
		}

		if ($template instanceof XenForo_Template_Public)
		{
			$params['hmvc'] = & self::$hmvc;
		}
	}
}