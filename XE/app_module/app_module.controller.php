<?php
class app_moduleController extends app_module {

	/**
	 * @brief Get ObjectID, Save Session
	 */
	function triggerModuleHandlerProc() {
		if(Context::get('obid')){
			$_SESSION["obid"] = Context::get('obid');
		}
	}

	/**
	 * @brief A trigger to add Object ID for Member
	 */
	function triggerAfterLogin(&$obj) {
	    $member_srl = $obj->member_srl;
	    if(!$member_srl) return new Object();
 
	    if(!$_SESSION["obid"]) return new Object();
 
	    $query = "update xe_member set `obid` = '".$_SESSION["obid"]."' where `member_srl` = ".$member_srl;
	    $sql = mysql_query($query);
 
	    if($sql) {
			unset($_SESSION["obid"]);
			return new Object();
	    }
		else {
	        return new Object(-1,"단말기 정보 등록이 실패했습니다.");
	    }
	}
}