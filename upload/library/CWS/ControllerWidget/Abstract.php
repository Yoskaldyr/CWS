<?php

abstract class CWS_ControllerWidget_Abstract extends XenForo_ControllerPublic_Abstract
{
	/**
	 * @var array
	 */
	protected  $_options = array();

	public function __construct(Zend_Controller_Request_Http $request, Zend_Controller_Response_Http $response, XenForo_RouteMatch $routeMatch, $options = array())
	{
		parent::__construct($request, $response, $routeMatch);
		$this->_options = $options;
	}

	public static function prepareOptions($options)
	{
		return XenForo_Helper_Criteria::unserializeCriteria($options);
	}

	public static function prepareWidget($widget)
	{
		$widget['options'] = self::prepareOptions($widget['options']);

		return $widget;
	}

	public static function renderOptions(XenForo_ControllerAdmin_Abstract $controller, $widget)
	{
		$viewParams['widget'] = self::prepareWidget($widget);

		return $controller->responseView('CWS_ViewAdmin_Widget_Options', '', $viewParams);
	}

}