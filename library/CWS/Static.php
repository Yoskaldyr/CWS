<?php

class CWS_Static
{
    /**
     * @var array
     */
    public static $widgets = array();

    /**
     * @var XenForo_ControllerPublic_Abstract
     */
    public static $controller = null;

    /**
     * @var XenForo_ControllerResponse_View
     */
    public static $controllerResponse = null;



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
        if($controller instanceof XenForo_ControllerPublic_Abstract && $controllerResponse instanceof XenForo_ControllerResponse_View)
        {
            self::$controller = $controller;
        }
    }

    public static function frontControllerPreView(XenForo_FrontController $fc, XenForo_ControllerResponse_Abstract &$controllerResponse, XenForo_ViewRenderer_Abstract &$viewRenderer, array &$containerParams)
    {
        if(self::$controller instanceof XenForo_ControllerPublic_Abstract &&
            $controllerResponse instanceof XenForo_ControllerResponse_View &&
            $viewRenderer instanceof XenForo_ViewRenderer_HtmlPublic
        )
        {
            self::$controllerResponse = $controllerResponse;
            $containerParams['widgets'] = &self::$widgets;
            $viewRenderer = new CWS_ViewRenderer_HtmlPublic($viewRenderer);
        }
    }

    public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
    {
        if($templateName=='PAGE_CONTAINER' && $template instanceof XenForo_Template_Public)
        {
            $params['widgets'] = &self::$widgets;
        }
    }
}