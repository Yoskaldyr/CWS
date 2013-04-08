<?php

class CWS_ControllerHelper_Template extends CWS_ControllerHelper_Widget
{
	public function getOptionsForEdit($widget)
	{
		$viewParams['widget'] = $widget;

		return $this->_controller->responseView('CWS_ViewAdmin_Widget_Options', 'cws_options_template', $viewParams);
	}

	public function actionTemplate()
	{
		$templateName = isset($this->options['template_title']) ? $this->options['template_title'] : '';
		return $this->_controller->responseView('CWS_ViewWidget_Default', $templateName, self::$params);
	}
}