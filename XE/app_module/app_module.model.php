<?php
class app_moduleModel extends app_module {
	function getPushMessage($v,$obid) {
		switch($v->type)
		{
			case 'D':
			    $type = "글";
			break;
			case 'C':
			    $type = "댓글";
			break;
			case 'E':
			    $type = "쪽지";
			break;
		}
 
		switch($v->target_type)
		{
			case 'C':
			    $str = sprintf('%s님이 회원님의 %s에 "%s" 댓글을 남겼습니다.', $v->target_nick_name, $type, $v->target_summary);
			break;
			case 'M':
				$str = sprintf('%s님이 "%s" %s에서 회원님을 언급하였습니다.', $v->target_nick_name,  $v->target_summary, $type);
		    break;
		    case 'E':
			    $str = sprintf('%s개의 읽지 않은 쪽지가 있습니다.', $v->target_summary);
		    break;
		}
  
		$url = 'https://api.parse.com/1/push';
	    $appId = '(Application ID)';
		$restKey = '(REST API Key)';
 
	    $target_device = $obid;
 
		$push_payload = json_encode(array(
			    "where" => array(
				        "objectId" => $target_device,
				),
				"data" => array(
					    "alert" => $str,
						"url" => getUrl('','act','procNcenterliteRedirect', 'notify', $v->notify, 'url', $v->target_url)
	            )
		));
 
	    $rest = curl_init();
		curl_setopt($rest,CURLOPT_URL,$url);
	    curl_setopt($rest, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($rest, CURLOPT_SSL_VERIFYPEER, 0);
	    curl_setopt($rest,CURLOPT_PORT,443);
		curl_setopt($rest,CURLOPT_POST,1);
	    curl_setopt($rest,CURLOPT_POSTFIELDS,$push_payload);
		curl_setopt($rest,CURLOPT_HTTPHEADER,
			    array("X-Parse-Application-Id: " . $appId,
				        "X-Parse-REST-API-Key: " . $restKey,
					    "Content-Type: application/json"));
	    curl_exec($rest);
	}
}