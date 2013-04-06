<?php

/**
 * Model for add-ons.
 *
 * @package XenForo_AddOns
 */
class CWS_Model_AddOn extends XFCP_CWS_Model_AddOn
{

	/**
	 * Imports all the add-on associated XML into the DB and rebuilds the
	 * caches.
	 *
	 * @param SimpleXMLElement $xml Root node that contains all of the "data" nodes below
	 * @param string $addOnId Add-on to import for
	 */
	public function importAddOnExtraDataFromXml(SimpleXMLElement $xml, $addOnId)
	{
		parent::importAddOnExtraDataFromXml($xml, $addOnId);

		$this->_getWidgetModel()->importWidgetsAddOnXml($xml->widgets, $addOnId);
	}

	/**
	 * Gets the XML data for the specified add-on.
	 *
	 * @param array $addOn Add-on info
	 *
	 * @return DOMDocument
	 */
	public function getAddOnXml(array $addOn)
	{
		$document = parent::getAddOnXml($addOn);
		$rootNode = $document->documentElement;
		$addOnId = $addOn['addon_id'];

		$dataNode = $rootNode->appendChild($document->createElement('widgets'));
		$this->_getWidgetModel()->appendWidgetsAddOnXml($dataNode, $addOnId);

		return $document;
	}

	public function deleteAddOnMasterData($addOnId)
	{
		parent::deleteAddOnMasterData($addOnId);

		if($addOnId != 'CWS')
		{
			$this->_getWidgetModel()->deleteWidgetsForAddOn($addOnId);
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