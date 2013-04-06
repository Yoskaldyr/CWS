<?php

/**
 * Data writer for add-ons.
 *
 * @package XenForo_AddOns
 */
class CWS_DataWriter_AddOn extends XFCP_CWS_DataWriter_AddOn
{

	/**
	 * Post-save handling.
	 */
	protected function _postSave()
	{
		parent::_postSave();

		if ($this->isUpdate() && $this->isChanged('addon_id'))
		{
			$db = $this->_db;
			$updateClause = 'addon_id = ' . $db->quote($this->getExisting('addon_id'));
			$updateValue = array('addon_id' => $this->get('addon_id'));

			$db->update('cws_widget', $updateValue, $updateClause);
		}
	}


	/**
	 * Gets the widget model.
	 *
	 * @return CWS_Model_Widget
	 */
	protected function _getWidgetModel()
	{
		return $this->getModelFromCache('CWS_Model_Widget');
	}
}