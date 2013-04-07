<?php

/**
 * Data writer for widgets
 *
 * @package XenForo_Widgets
 */
class CWS_DataWriter_Widget extends XenForo_DataWriter
{
	const OPTION_CHECK_DUPLICATE = 'checkDuplicate';

	/**
	 * @var string
	 */
	protected $_existingDataErrorPhrase = 'requested_widget_not_found';

	/**
	 * Gets the fields that are defined for the table. See parent for explanation.
	 *
	 * @return array
	 */
	protected function _getFields()
	{
		return array(
			'cws_widget' => array(
				'widget_id' => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'title' => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 150,
					'requiredError' => 'please_enter_valid_title'),
				'description' => array('type' => self::TYPE_STRING, 'default' => ''),
				'callback_class' => array('type' => self::TYPE_STRING, 'maxLength' => 75, 'required' => true,
					'requiredError' => 'please_enter_valid_callback_class'),
				'callback_method' => array('type' => self::TYPE_STRING, 'maxLength' => 50, 'required' => true,
					'requiredError' => 'please_enter_valid_callback_method'),
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
		if (!$id = $this->_getExistingPrimaryKey($data))
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

	protected function _getDefaultOptions()
	{
		$options = array(self::OPTION_CHECK_DUPLICATE => true);

		return $options;
	}

	protected function _verifyPrepareTitle(&$title)
	{
		$title = trim($title);
		if (preg_match('/[^a-zA-Z0-9_ \.]/', $title))
		{
			$this->error(new XenForo_Phrase('cws_please_enter_title_using_only_alphanumeric_dot_space'), 'title');
			return false;
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
		if ($this->isChanged('callback_class') || $this->isChanged('callback_method'))
		{
			$class = $this->get('callback_class');
			$method = $this->get('callback_method');

			if (!XenForo_Application::autoload($class) || !method_exists($class, $method))
			{
				$this->error(new XenForo_Phrase('please_enter_valid_callback_method'), 'callback_method');
			}
		}

		if ($this->getOption(self::OPTION_CHECK_DUPLICATE) && $this->isChanged('title'))
		{
			$titleConflict = $this->_getWidgetModel()->getWidgetByTitle($this->getNew('title'));

			if ($titleConflict)
			{
				$this->error(new XenForo_Phrase('cws_widget_titles_must_be_unique'), 'title');
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