<?php

class CWS_ControllerHelper_Widget extends XenForo_ControllerHelper_Abstract
{
	/**
	 * @var array
	 */
	public $options;

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


	public function __construct(XenForo_Controller $controller, array $options = array())
	{
		$this->options = $options;
		parent::__construct($controller);
	}

	public function getOptionsForEdit($widget)
	{
		$viewParams['widget'] = $widget;

		return $this->_controller->responseView('CWS_ViewAdmin_Widget_Options', '', $viewParams);
	}

	public function filterOptionsForSave()
	{
		$options = $this->_controller->getInput()->filterSingle('options', XenForo_Input::ARRAY_SIMPLE);

		return $options;
	}
}