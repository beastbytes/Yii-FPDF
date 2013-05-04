<?php
/**
* PDFHelper class file.
* @copyright  Copyright © 2011 PBM Web Development - All Rights Reserved
* @version		1.0.0
* @license		BSD 3-Clause
*/
/**
* PDFHelper class.
* Provides the hooks for helpers.
*/
class PDFHelper extends CBehavior
{
	private $_d;

	public function __call($name, $parameters)
  {
		try {
			return call_user_func_array(array($this->getOwner(), $name), $parameters);
		}
		catch (CException $e) {
			parent::__call($name, $parameters);
		}
	}

	public function attach($owner)
  {
		$this->_d = $owner->getData();
		parent::attach($owner);
	}

	public function getData()
  {
		return $this->_d;
	}
}
