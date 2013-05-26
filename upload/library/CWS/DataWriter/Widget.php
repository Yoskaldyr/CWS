<?php

/**
 * Data writer for widgets
 *
 * @package XenForo_Widgets
 */
class CWS_DataWriter_Widget extends XenForo_DataWriter
{

	/**
	 * @var string
	 */
	protected $_existingDataErrorPhrase = 'cws_requested_widget_not_found';

	/**
	 * Gets the fields that are defined for the table. See parent for explanation.
	 *
	 * @return array
	 */
	protected function _getFields()
	{
		return array(
			'cws_widget' => array(
				'widget_id' => array('type' => self::TYPE_STRING, 'maxLength' => 50, 'required' => true,
					'verification' => array('$this', '_verifyWidgetId'), 'requiredError' => 'cws_please_enter_valid_widget_id'
				),
				'description' => array('type' => self::TYPE_STRING, 'default' => ''),
				'callback_class' => array('type' => self::TYPE_STRING, 'maxLength' => 75, 'required' => true,
					'requiredError' => 'please_enter_valid_callback_class'),
				'callback_method' => array('type' => self::TYPE_STRING, 'maxLength' => 50, 'required' => true,
					'requiredError' => 'please_enter_valid_callback_method'),
				'options' => array('type' => self::TYPE_SERIALIZED, 'default' => 'a:0:{}'),
				'dismissible' => array('type' => self::TYPE_BOOLEAN, 'default' => 1),
				'active' => array('type' => self::TYPE_BOOLEAN, 'default' => 1),
				'position' => array('type' => self::TYPE_STRING, 'default' => 'right_sidebar'),
				'display_order' => array('type' => self::TYPE_UINT, 'default' => 1),
				'user_criteria' => array('type' => self::TYPE_UNKNOWN, 'required' => true,
					'verification' => array('$this', '_verifyCriteria')),
				'page_criteria' => array('type' => self::TYPE_UNKNOWN, 'required' => true,
					'verification' => array('$this', '_verifyCriteria')),
				'addon_id' => array('type' => self::TYPE_STRING, 'maxLength' => 25, 'default' => ''),
			)
		);
	}

	/**
	 * Gets the actual existing data out of data that was passed in. See parent for explanation.
	 *
	 * @param mixed
	 *
	 * @return array|false
	 */
	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'widget_id'))
		{
			return false;
		}

		return array('cws_widget' => $this->_getWidgetModel()->getWidgetById($id));
	}

	/**
	 * Gets SQL condition to update the existing record.
	 *
	 * @return string
	 */
	protected function _getUpdateCondition($tableName)
	{
		return 'widget_id = ' . $this->_db->quote($this->getExisting('widget_id'));
	}

	protected function _verifyWidgetId(&$widgetId)
	{
		if (!preg_match('/^[a-z0-9_\-]+$/i', $widgetId))
		{
			$this->error(new XenForo_Phrase('cws_please_enter_widget_id_using_alphanumeric'), 'widget_id');
			return false;
		}

		if ($this->isInsert() || $widgetId != $this->getExisting('widget_id'))
		{
			$existing = $this->_getWidgetModel()->getWidgetById($widgetId);
			if ($existing)
			{
				$this->error(new XenForo_Phrase('cws_widget_ids_must_be_unique'), 'widget_id');
				return false;
			}
		}

		return true;
	}

	/**
	 * Verifies that the criteria is valid and formats is correctly.
	 * Expected input format: [] with children: [rule] => name, [data] => info
	 *
	 * @param array|string $criteria Criteria array or serialize string; see above for format. Modified by ref.
	 *
	 * @return boolean
	 */
	protected function _verifyCriteria(&$criteria)
	{
		$criteriaFiltered = XenForo_Helper_Criteria::prepareCriteriaForSave($criteria);
		$criteria = serialize($criteriaFiltered);
		return true;
	}

	protected function _preSave()
	{
		$class = $this->get('callback_class');
		$method = $this->get('callback_method');

		if (!XenForo_Application::autoload($class) || !method_exists($class, $method))
		{
			$this->error(new XenForo_Phrase('please_enter_valid_callback_method'), 'callback_method');
		}
		else
		{
			$widgetHandler = new $class();

			if(!($widgetHandler instanceof CWS_ControllerWidget_Abstract))
			{
				$this->error(new XenForo_Phrase('cws_callback_class_must_extend_class_y', array('class' => 'CWS_ControllerWidget_Abstract')), 'callback_class');
			}
			else
			{
				$this->set('options', $widgetHandler->prepareOptions($this->get('options')));
			}
		}
	}

	/**
	 * Post-save handling.
	 */
	protected function _postSave()
	{
		$this->_rebuildWidgetCache();
	}

	/**
	 * Post-delete handling.
	 */
	protected function _postDelete()
	{
		//$this->_db->delete('cws_widget_dismissed', 'widget_id = ' . $this->_db->quote($this->get('widget_id')));
		$this->_rebuildWidgetCache();
	}

	/**
	 * Rebuilds the widget cache.
	 */
	protected function _rebuildWidgetCache()
	{
		$this->_getWidgetModel()->rebuildWidgetCache();
	}

	/**
	 * @return CWS_Model_Widget
	 */
	protected function _getWidgetModel()
	{
		return $this->getModelFromCache('CWS_Model_Widget');
	}
}