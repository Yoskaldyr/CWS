<?php

class CWS_ViewAdmin_Widget_SearchParam extends XenForo_ViewAdmin_Base
{
	public function renderJson()
	{
		$results = array();
		foreach ($this->_params['values'] AS $value)
		{
			$results[$value]['username'] = $value;
		}

		return array(
			'results' => $results
		);
	}
}