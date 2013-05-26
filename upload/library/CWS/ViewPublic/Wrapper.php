<?php

class CWS_ViewPublic_Wrapper extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		/* @var $subView XenForo_Template_Abstract */
		$subView = &$this->_params['_subView'];

		if ($subView instanceof XenForo_Template_Abstract)
		{
			$subView = $subView->render();
		}

		$containerData = $this->_renderer->getDependencyHandler()->getExtraContainerData();

		$this->_getWidgetsContainerParams($containerData);

		return $subView;
	}

	protected function _getWidgetsContainerParams(array $containerData)
	{
		$allWidgets = XenForo_Application::isRegistered('widgets') ? XenForo_Application::get('widgets') : array();

		$hmvc = & CWS_Static::$hmvc;
		$widgets = & CWS_Static::$widgets;

		$user = XenForo_Visitor::getInstance()->toArray();

		if (XenForo_Application::isRegistered('session'))
		{
			$dismissedWidgets = XenForo_Application::getSession()->get('dismissedWidgets');
		}

		if (!isset($dismissedWidgets) || !is_array($dismissedWidgets))
		{
			$dismissedWidgets = array();
		}

		CWS_Static::$containerParams = XenForo_Application::mapMerge(CWS_Static::$containerParams, $containerData);
		CWS_Static::$params = XenForo_Application::mapMerge(CWS_Static::$innerParams, CWS_Static::$containerParams);

		foreach ($allWidgets AS $widgetId => $widget)
		{
			$widgetPosition = $widget['position'];
			$widgetCallback = array($widget['callback_class'], $widget['callback_method']);

			if (!in_array($widgetId, $dismissedWidgets) && is_callable($widgetCallback) &&
				XenForo_Helper_Criteria::userMatchesCriteria($widget['user_criteria'], true, $user) &&
				XenForo_Helper_Criteria::pageMatchesCriteria($widget['page_criteria'], true, array(), $containerData)
			)
			{
				$widgetReroute = new XenForo_ControllerResponse_Reroute();
				$widgetReroute->controllerName = $widget['callback_class'];
				$widgetReroute->action = $widget['callback_method'];
				$widgetReroute->containerParams = $widget['options'];

				$hmvc[$widgetId] = $widgetReroute;
				$widgets[$widgetPosition][$widgetId] = &$hmvc[$widgetId];
			}
		}

		foreach ($hmvc as $key => &$hmvcItem)
		{
			if ($hmvcItem instanceof XenForo_ControllerResponse_Reroute)
			{
				$hmvcItem = $this->dispatch($hmvcItem);
			}

			if($hmvcItem instanceof XenForo_ControllerResponse_View)
			{
				$hmvcItem = $this->_renderer->renderView(
					$hmvcItem->viewName,
					$hmvcItem->params,
					$hmvcItem->templateName,
					$hmvcItem->subView
				);
			}
		}
	}

	/**
	 * Executes the controller dispatch loop.
	 *
	 * @param XenForo_ControllerResponse_Reroute $routeMatch
	 *
	 * @return XenForo_ControllerResponse_Abstract|null Null will only occur if error handling is broken
	 */
	public function dispatch(XenForo_ControllerResponse_Reroute $reroute)
	{
		do
		{
			$controllerResponse = null;
			$controllerName = $reroute->controllerName;

			$action = preg_replace('#^action#', '', $reroute->action);
			$action = str_replace(array('-', '/'), ' ', strtolower($action));
			$action = str_replace(' ', '', ucwords($action));
			if ($action === '')
			{
				$action = 'Index';
			}

			$controller = $this->_getValidatedController($controllerName, $action, $reroute->containerParams);
			$reroute = false;

			if ($controller)
			{
				try
				{
					try
					{
						//$controller->preDispatch($action);
						$controllerResponse = $controller->{'action' . $action}();
					}
					catch (XenForo_ControllerResponse_Exception $e)
					{
						break;
					}

					//$controller->postDispatch($controllerResponse, $controllerName, $action);
					$reroute = $this->_handleControllerResponse($controllerResponse, $controllerName, $action);
				}
				catch (Exception $e)
				{
					break;
				}
			}
		}
		while ($reroute);

		if ($controllerResponse instanceof XenForo_ControllerResponse_Abstract)
		{
			$controllerResponse->controllerName = $controllerName;
			$controllerResponse->controllerAction = $action;
		}

		return $controllerResponse;
	}

	/**
	 * Loads the controller only if it and the specified action have been validated as callable.
	 *
	 * @param string Name of the controller to load
	 * @param string Name of the action to run
	 * @param XenForo_RouteMatch Route match for this request (may not match controller)
	 *
	 * @return XenForo_Controller|null
	 */
	protected function _getValidatedController($controllerName, $action, $options)
	{
		$controllerName = XenForo_Application::resolveDynamicClass($controllerName, 'controller');
		if ($controllerName)
		{
			$controller = new $controllerName(CWS_Static::$request, CWS_Static::$response, CWS_Static::$routeMatch, $options);
			if (method_exists($controller, 'action' . $action) && ($controller instanceof CWS_ControllerWidget_Abstract))
			{
				return $controller;
			}
		}

		return null;
	}

	/**
	 * Handles a controller response to determine if something failed or a reroute is needed.
	 *
	 * @param mixed  Exceptions will be thrown if is not {@link XenForo_ControllerResponse_Abstract}
	 * @param string Name of the controller that generated this response
	 * @param string Name of the action that generated this response
	 *
	 * @return false|array False if no reroute is needed. Array with keys controllerName and action if needed.
	 */
	protected function _handleControllerResponse($controllerResponse, $controllerName, $action)
	{
		if ($controllerResponse instanceof XenForo_ControllerResponse_Reroute)
		{
			if ($controllerResponse->controllerName == $controllerName && strtolower($controllerResponse->action) == strtolower($action))
			{
				return false;
			}

			return $controllerResponse;
		}

		return false;
	}

}