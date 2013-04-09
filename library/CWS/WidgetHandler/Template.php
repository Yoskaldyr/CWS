<?php

class CWS_WidgetHandler_Template extends CWS_WidgetHandler_Abstract
{
	public function renderOptions(XenForo_ControllerAdmin_Abstract $controller, $widget)
	{
		$viewParams['widget'] = $this->prepareWidget($widget);

		return $controller->responseView('CWS_ViewAdmin_Widget_Options', 'cws_options_template', $viewParams);
	}

	public function actionTemplate(XenForo_ControllerPublic_Abstract $controller, array $options = array())
	{
		$templateName = isset($options['template_title']) ? $options['template_title'] : '';
		return $controller->responseView('CWS_ViewWidget_Default', $templateName, self::$params);
	}
}