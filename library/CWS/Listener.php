<?php

class CWS_Listener
{
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
			CWS_ControllerWidget_Abstract::$routeMatch = $controller->getRouteMatch();
		}
	}

	public static function frontControllerPreView(XenForo_FrontController $fc, XenForo_ControllerResponse_Abstract &$controllerResponse, XenForo_ViewRenderer_Abstract &$viewRenderer, array &$containerParams)
	{
		if ($controllerResponse instanceof XenForo_ControllerResponse_View &&
			$viewRenderer instanceof XenForo_ViewRenderer_HtmlPublic
		)
		{
			CWS_ControllerWidget_Abstract::$innerParams = $controllerResponse->params;
			$containerParams['widgets'] = & CWS_ViewRenderer_HtmlPublic::$widgets;
			$viewRenderer = new CWS_ViewRenderer_HtmlPublic($viewRenderer);
		}
	}

	public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		if ($templateName == 'PAGE_CONTAINER' && $template instanceof XenForo_Template_Public)
		{
			$params['widgets'] = & CWS_ViewRenderer_HtmlPublic::$widgets;
		}
	}
}