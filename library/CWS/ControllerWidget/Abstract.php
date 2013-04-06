<?php

class CWS_ControllerWidget_Abstract extends XenForo_ControllerPublic_Abstract
{
	/**
	 * Title of the phrase that will be created when a call to set the
	 * existing data fails (when the data doesn't exist).
	 *
	 * @var array
	 */
	protected $_params;

	public function __construct(XenForo_ControllerPublic_Abstract $controller, array &$params)
	{
		$this->_request = $controller->_request;
		$this->_response = $controller->_response;
		$this->_routeMatch = $controller->_routeMatch;
		$this->_input = $controller->_input;
		$this->_viewStateChanges = $controller->_viewStateChanges;
		$this->_modelCache = $controller->_modelCache;

		$this->_params = & $params;
	}
}