<?php
require_once(_XE_PATH_.'modules/member/member.class.php');
class app_module extends member {

	/**
	 * @brief install the module
	 **/
	function moduleInstall() {
	    return new Object();
	}
 
	/**
	 * @brief chgeck module method
	 **/
	function checkUpdate() {
	    $oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		if(!$oDB->isColumnExists("member", "obid"))    return true;
		if(!$oModuleModel->getTrigger('member.doLogin', 'app_module', 'controller', 'triggerAfterLogin', 'after'))
			return true;
		if(!$oModuleModel->getTrigger('moduleHandler.proc', 'app_module', 'controller', 'triggerModuleHandlerProc', 'after'))
			return true;
	    return false;
	}
 
	/**
	 * @brief update module
	 **/
	function moduleUpdate() {
		$oDB = &DB::getInstance();
	    if(!$oDB->isColumnExists("member", "obid")) $oDB->addColumn("member", "obid", "varchar","128");
 
		$oModuleModel = &getModel('module');
	    $oModuleController = &getController('module');
		if(!$oModuleModel->getTrigger('member.doLogin', 'app_module', 'controller', 'triggerAfterLogin', 'after'))
			$oModuleController->insertTrigger('member.doLogin', 'app_module', 'controller', 'triggerAfterLogin', 'after');
		if(!$oModuleModel->getTrigger('moduleHandler.proc', 'app_module', 'controller', 'triggerModuleHandlerProc', 'after'))
			$oModuleController->insertTrigger('moduleHandler.proc', 'app_module', 'controller', 'triggerModuleHandlerProc', 'after');
	    return new Object(0, 'success_updated');
	}
 
	function moduleUninstall() {
		return new Object();
	}
 
	/**
	* @brief re-generate the cache files
	 **/
	function recompileCache() {

	}
}