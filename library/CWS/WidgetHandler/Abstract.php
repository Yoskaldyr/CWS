<?php

class CWS_WidgetHandler_Abstract
{
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


	public function __construct()
	{
	}

	public function prepareOptions($options)
	{
		return XenForo_Helper_Criteria::unserializeCriteria($options);
	}

	public function prepareWidget($widget)
	{
		$widget['options'] = $this->prepareOptions($widget['options']);

		return $widget;
	}

	public function renderOptions(XenForo_ControllerAdmin_Abstract $controller, $widget)
	{
		$viewParams['widget'] = $this->prepareWidget($widget);

		return $controller->responseView('CWS_ViewAdmin_Widget_Options', '', $viewParams);
	}

}