<?php

class CWS_ControllerWidget_Template extends CWS_ControllerWidget_Abstract
{
	public static function renderOptions(XenForo_ControllerAdmin_Abstract $controller, $widget)
	{
		$viewParams['widget'] = self::prepareWidget($widget);

		return $controller->responseView('CWS_ViewAdmin_Widget_Options', 'cws_options_template', $viewParams);
	}

	public function actionTemplate()
	{
		$templateName = isset($this->_options['template_title']) ? $this->_options['template_title'] : '';
		return $this->responseView('CWS_ViewWidget_Default', $templateName, CWS_Static::$params);
	}
}