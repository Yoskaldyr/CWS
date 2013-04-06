<?php

/**
 *
 */
class CWS_Install
{
	private static $_instance;

	protected $_db;

	public static final function getInstance()
	{
		if (!self::$_instance)
        {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	/**
	 * @return Zend_Db_Adapter_Abstract
	 */
	protected function _getDb()
	{
		if ($this->_db === null)
        {
			$this->_db = XenForo_Application::getDb();
		}

		return $this->_db;
	}

	public static function build($existingAddOn, $addOnData)
	{
        if (XenForo_Application::$versionId < 1010370)
        {
            // note: this can't be phrased
            throw new XenForo_Exception('This add-on requires XenForo 1.1.3 or higher.', true);
        }

        /* @var $addOnModel XenForo_Model_AddOn*/
        $addOnModel = XenForo_Model::create('XenForo_Model_AddOn');

        if (!$addOnModel->getAddOnById('TMS'))
        {
            throw new XenForo_Exception('This add-on requires TMS.', true);
        }

		$startVersion = 1;
		$endVersion = $addOnData['version_id'];

		if ($existingAddOn)
        {
			$startVersion = $existingAddOn['version_id'] + 1;
		}

		$install = self::getInstance();

		$db = XenForo_Application::getDb();
		XenForo_Db::beginTransaction($db);

		for ($i = $startVersion; $i <= $endVersion; $i++)
		{
			$method = '_installVersion' . $i;

			if (method_exists($install, $method) === false) {
				continue;
			}

			$install->$method();
		}

		XenForo_Db::commit($db);
	}

	protected function _installVersion1()
	{
		$db = $this->_getDb();

		$db->query("
            CREATE TABLE cws_widget (
            widget_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
            title VARCHAR(25) NOT NULL ,
            description text NOT NULL ,
            callback_class VARCHAR(75) NOT NULL DEFAULT '',
            callback_method VARCHAR(50) NOT NULL DEFAULT '',
            active TINYINT UNSIGNED NOT NULL DEFAULT '1',
            display_order INT(10) UNSIGNED NOT NULL DEFAULT '1',
            dismissible TINYINT UNSIGNED NOT NULL default '0',
            position VARCHAR(50) NOT NULL DEFAULT '',
            user_criteria MEDIUMBLOB NOT NULL,
            page_criteria MEDIUMBLOB NOT NULL,
            addon_id VARCHAR(25) NOT NULL DEFAULT '',
            PRIMARY KEY (widget_id) ,
            UNIQUE KEY title (title)
            ) ENGINE = InnoDB DEFAULT CHARSET = utf8;
        ");



	}

	public static function destroy()
	{
		$lastUninstallStep = 3;

		$uninstall = self::getInstance();

		$db = XenForo_Application::getDb();
		XenForo_Db::beginTransaction($db);

		for ($i = 1; $i <= $lastUninstallStep; $i++)
		{
			$method = '_uninstallStep' . $i;

			if (method_exists($uninstall, $method) === false) {
				continue;
			}

			$uninstall->$method();
		}

		XenForo_Db::commit($db);
	}

	protected function _uninstallStep1()
	{
		$db = $this->_getDb();

		$db->query("DROP TABLE cws_widget");
	}
}