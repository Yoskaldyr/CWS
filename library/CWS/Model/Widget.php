<?php

class CWS_Model_Widget extends XenForo_Model
{
	/**
	 * Fetch a single widget by its widget_id
	 *
	 * @param integer $widgetId
	 *
	 * @return array
	 */
	public function getWidgetById($widgetId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM cws_widget
			WHERE widget_id = ?
		', $widgetId);
	}

	public function getDefaultWidget()
	{
		return array(
			'widget_id' => 0,

			'title' => '',
            'description' => '',

			'user_criteria' => '',
			'userCriteriaList' => array(),

			'page_criteria' => '',
			'pageCriteriaList' => array(),

			'active' => 1,
			'dismissible' => 0,
			'display_order' => 1,
			'position' => 'right_sidebar',
            'addon_id' => '',
		);
	}

	/**
	 * Fetch all widgets from the database
	 *
	 * @return array
	 */
	public function getAllWidgets()
	{
		return $this->fetchAllKeyed('
			SELECT widget.*,
			addon.addon_id, addon.title AS addonTitle, addon.active AS addonActive
			FROM cws_widget AS widget
			LEFT JOIN xf_addon AS addon ON (addon.addon_id = widget.addon_id)
			ORDER BY display_order
		', 'widget_id');
	}

	public function prepareWidget(array $widget)
	{
		return $widget;
	}

	public function rebuildWidgetCache()
	{
		$cache = array();

		foreach ($this->getAllWidgets() AS $widgetId => $widget)
		{
			if ($widget['active'] && $widget['addonActive'])
			{
				$cache[$widgetId] = array(
					'title' => $widget['title'],
                    'description' => $widget['description'],
					'callback_class' => $widget['callback_class'],
                    'callback_method' => $widget['callback_method'],
					'dismissible' => $widget['dismissible'],
					'position' => $widget['position'],
					'user_criteria' => XenForo_Helper_Criteria::unserializeCriteria($widget['user_criteria']),
					'page_criteria' => XenForo_Helper_Criteria::unserializeCriteria($widget['page_criteria']),
                    'addon_id' => $widget['addon_id'],
				);
			}
		}

		$this->_getDataRegistryModel()->set('widgets', $cache);
		return $cache;
	}

	public function canDismissWidget(array $widget, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (empty($viewingUser['user_id']) || empty($widget['dismissible']))
		{
			$errorPhraseKey = 'cws_you_may_not_dismiss_this_widget';
			return false;
		}

		return true;
	}

	public function dismissWidget($widgetId, $userId = null)
	{
		if (empty($userId))
		{
			$userId = XenForo_Visitor::getUserId();
		}

		if (!$userId)
		{
			return;
		}

        return;

        // leave it for future

		$this->_getDb()->query('
			INSERT IGNORE INTO cws_widget_dismissed
				(widget_id, user_id, dismiss_date)
			VALUES
				(?, ?, ?)
		', array($widgetId, $userId, XenForo_Application::$time));
	}

	public function restoreWidgets(array $user = null)
	{
		$this->standardizeViewingUserReference($user);

		if (!$user['user_id'])
		{
			return;
		}

		$db = $this->_getDb();

		$db->delete('widget_dismissed', 'user_id = ' . $db->quote($user['user_id']));
	}

	public function getDismissedWidgetIdsForUser($userId)
	{
        return array();

        // leave it for future if needed

		if (!$userId)
		{
			return array();
		}

		return $this->_getDb()->fetchCol('
			SELECT widget_id
			FROM cws_widget_dismissed
			WHERE user_id = ?
		', $userId);
	}

	public function getWidgetsForAdminQuickSearch($searchText)
	{
		$quotedString = XenForo_Db::quoteLike($searchText, 'lr', $this->_getDb());

		return $this->fetchAllKeyed('
			SELECT * FROM cws_widget
			WHERE title LIKE ' . $quotedString . '
			ORDER BY title, display_order
		', 'widget_id');
	}

    /*******************************************************************************/


    /**
     * Fetches a widget from a particular style based on its title.
     * Note that if a version of the requested widget does not exist
     * in the specified style, nothing will be returned.
     *
     * @param string Title
     * @param integer Style ID (defaults to master style)
     *
     * @return array
     */
    public function getWidgetByTitle($title)
    {
        return $this->_getDb()->fetchRow('
			SELECT widget.*,
			addon.title AS addonTitle
			FROM cws_widget AS widget
   			LEFT JOIN xf_addon AS addon ON
   				(addon.addon_id = widget.addon_id)
            WHERE widget.title = ?
      		', array($title));
    }

    /**
     * Fetches widgets from a particular style based on their titles.
     * Note that if a version of the requested widget does not exist
     * in the specified style, nothing will be returned for it.
     *
     * @param array $titles List of titles
     * @param integer $styleId Style ID (defaults to master style)
     *
     * @return array Format: [title] => info
     */
    public function getWidgetsByTitles(array $titles)
    {
        if (!$titles) {
            return array();
        }

        return $this->fetchAllKeyed('
			SELECT widget.*,
			addon.title AS addonTitle
			FROM cws_widget AS widget
   			LEFT JOIN xf_addon AS addon ON
   				(addon.addon_id = widget.addon_id)
  			WHERE widget.title IN (' . $this->_getDb()->quote($titles) . ')
  		', 'widget_id');
    }


    /**
     * Gets all widgets for the specified add-on in ID and execute order.
     *
     * @param string $addOnId
     *
     * @return array Format: [event listener id] => info
     */
    public function getWidgetsByAddOn($addOnId)
    {
        return $this->fetchAllKeyed('
			SELECT widget.*
			FROM cws_widget AS widget
			WHERE addon_id = ?
			ORDER BY title, display_order
		', 'widget_id', $addOnId);
    }


    /**
     * Deletes the widgets that belong to the specified add-on.
     *
     * @param string $addOnId
     */
    public function deleteWidgetsForAddOn($addOnId)
    {
        $db = $this->_getDb();

        $db->delete('cws_widget', 'addon_id = ' . $db->quote($addOnId));
    }

    /**
     * Imports the add-on widgets XML.
     *
     * @param SimpleXMLElement $xml XML element pointing to the root of the data
     * @param string $addOnId Add-on to import for
     * @param integer $maxExecution Maximum run time in seconds
     * @param integer $offset Number of elements to skip
     *
     * @return boolean|integer True on completion; false if the XML isn't correct; integer otherwise with new offset value
     */
    public function importWidgetsAddOnXml(SimpleXMLElement $xml, $addOnId)
    {
        $db = $this->_getDb();

        XenForo_Db::beginTransaction($db);

        $this->deleteWidgetsForAddOn($addOnId);

        $widgets = XenForo_Helper_DevelopmentXml::fixPhpBug50670($xml->widget);

        $titles = array();
        foreach ($widgets AS $widget)
        {
            $titles[] = (string)$widget['title'];
        }

        $existingWidgets = $this->getWidgetsByTitles($titles);

        foreach ($widgets AS $widget)
        {
            $widgetName = (string)$widget['title'];

            $dw = XenForo_DataWriter::create('CWS_DataWriter_Widget');
            if (isset($existingWidgets[$widgetName]))
            {
                $dw->setExistingData($existingWidgets[$widgetName], true);
            }
            $dw->setOption(CWS_DataWriter_Widget::OPTION_CHECK_DUPLICATE, false);
            $dw->bulkSet(array(
                'title' => $widgetName,
                'description' => (string)$widget['description'],
                'callback_class' => (string)$widget['callback_class'],
                'callback_method' => (string)$widget['callback_method'],
                'dismissible' => (int)$widget['dismissible'],
                'active' => (int)$widget['active'],
                'position' => (string)$widget['position'],
                'display_order' => (int)$widget['display_order'],
                'user_criteria' => XenForo_Helper_DevelopmentXml::processSimpleXmlCdata($widget->user_criteria),
                'page_criteria' => XenForo_Helper_DevelopmentXml::processSimpleXmlCdata($widget->page_criteria),
                'addon_id' => $addOnId,
            ));
            $dw->save();

        }

        XenForo_Db::commit($db);

        return true;
    }

    /**
     * Appends the add-on widget XML to a given DOM element.
     *
     * @param DOMElement $rootNode Node to append all elements to
     * @param string $addOnId Add-on ID to be exported
     */
    public function appendWidgetsAddOnXml(DOMElement $rootNode, $addOnId)
    {
        $document = $rootNode->ownerDocument;

        $widgets = $this->getWidgetsByAddOn($addOnId);
        foreach ($widgets AS $widget)
        {
            $widgetNode = $document->createElement('widget');
            $widgetNode->setAttribute('title', $widget['title']);
            $widgetNode->setAttribute('description', $widget['description']);
            $widgetNode->setAttribute('callback_class', $widget['callback_class']);
            $widgetNode->setAttribute('callback_method', $widget['callback_method']);
            $widgetNode->setAttribute('dismissible', $widget['dismissible']);
            $widgetNode->setAttribute('active', $widget['active']);
            $widgetNode->setAttribute('position', $widget['position']);
            $widgetNode->setAttribute('display_order', $widget['display_order']);
            //$widgetNode->setAttribute('user_criteria', $widget['user_criteria']);
            //$widgetNode->setAttribute('page_criteria', $widget['page_criteria']);

            $findNode = $document->createElement('user_criteria');
            $findNode->appendChild(XenForo_Helper_DevelopmentXml::createDomCdataSection($document, $widget['user_criteria']));
            $widgetNode->appendChild($findNode);

            $replaceNode = $document->createElement('page_criteria');
            $replaceNode->appendChild(XenForo_Helper_DevelopmentXml::createDomCdataSection($document, $widget['page_criteria']));
            $widgetNode->appendChild($replaceNode);

            $rootNode->appendChild($widgetNode);
        }
    }
}