<?php
/**
 * @author XE Magazine <info@xemagazine.com>
 * @link http://xemagazine.com/
 **/
class ncenterliteController extends ncenterlite
{
	function triggerAfterDeleteMember($obj)
	{
		$oNcenterliteModel = &getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();

		$member_srl = $obj->member_srl;
		if(!$member_srl) return new Object();

		$args->member_srl = $member_srl;
		executeQuery('ncenterlite.deleteNotifyByMemberSrl', $args);

		return new Object();
	}

	function triggerAfterInsertDocument(&$obj)
	{
		if($this->_isDisable()) return;

		$oNcenterliteModel = &getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if($config->use != 'Y') return new Object();

		$content = strip_tags($obj->title . ' ' . $obj->content);

		$mention_targets = $this->_getMentionTarget($content);
		if(!$mention_targets || !count($mention_targets)) return new Object();

		$is_anonymous = $this->_isAnonymous($this->_TYPE_DOCUMENT, $obj);

		// !TODO 공용 메소드로 분리
		foreach($mention_targets as $mention_member_srl)
		{
			$args = new stdClass();
			$args->member_srl = $mention_member_srl;
			$args->srl = $obj->document_srl;
			$args->target_srl = $mention_member_srl;
			$args->type = $this->_TYPE_DOCUMENT;
			$args->target_type = $this->_TYPE_MENTION;
			$args->target_url = getNotEncodedFullUrl('', 'document_srl', $obj->document_srl);
			$args->target_summary = cut_str(strip_tags($obj->title), 30);
			$args->target_nick_name = $obj->nick_name;
			$args->target_email_address = $obj->email_address;
			$args->regdate = date('YmdHis');
			$args->notify = $this->_getNotifyId($args);
			$output = $this->_insertNotify($args, $is_anonymous);
		}

		return new Object();
	}


	function triggerAfterInsertComment(&$obj)
	{
		if($this->_isDisable()) return;

		$oNcenterliteModel = &getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if($config->use != 'Y') return new Object();

		$logged_info = Context::get('logged_info');
		$notify_member_srl = array();

		$document_srl = $obj->document_srl;
		$comment_srl = $obj->comment_srl;
		$parent_srl = $obj->parent_srl;
		$content = $obj->content;
		$regdate = $obj->regdate;

		// 익명 노티 체크
		$is_anonymous = $this->_isAnonymous($this->_TYPE_COMMENT, $obj);

		// 멘션
		$mention_targets = $this->_getMentionTarget(strip_tags($obj->content));
		// !TODO 공용 메소드로 분리
		foreach($mention_targets as $mention_member_srl)
		{
			$args = new stdClass();
			$args->member_srl = $mention_member_srl;
			$args->srl = $obj->comment_srl;
			$args->target_srl = $mention_member_srl;
			$args->type = $this->_TYPE_COMMENT;
			$args->target_type = $this->_TYPE_MENTION;
			$args->target_url = getNotEncodedFullUrl('', 'document_srl', $document_srl, '_comment_srl', $comment_srl) . '#comment_'. $comment_srl;
			$args->target_summary = cut_str(strip_tags($content), 30);
			$args->target_nick_name = $obj->nick_name;
			$args->target_email_address = $obj->email_address;
			$args->regdate = date('YmdHis');
			$args->notify = $this->_getNotifyId($args);
			$output = $this->_insertNotify($args, $is_anonymous);
			$notify_member_srl[] = $mention_member_srl;
		}

		// 대댓글
		if($parent_srl)
		{
			$oCommentModel = &getModel('comment');
			$oComment = $oCommentModel->getComment($parent_srl);
			$member_srl = $oComment->member_srl;
			// !TODO 공용 메소드로 분리
			if(!in_array(abs($member_srl), $notify_member_srl) && (!$logged_info || ($member_srl != 0 && abs($member_srl) != $logged_info->member_srl)))
			{
				$args = new stdClass();
				$args->member_srl = abs($member_srl);
				$args->srl = $parent_srl;
				$args->target_srl = $comment_srl;
				$args->type = $this->_TYPE_COMMENT;
				$args->target_type = $this->_TYPE_COMMENT;
				$args->target_url = getNotEncodedFullUrl('', 'document_srl', $document_srl, '_comment_srl', $comment_srl) . '#comment_'. $comment_srl;
				$args->target_summary = cut_str(strip_tags($content), 30);
				$args->target_nick_name = $obj->nick_name;
				$args->target_email_address = $obj->email_address;
				$args->regdate = $regdate;
				$args->notify = $this->_getNotifyId($args);
				$output = $this->_insertNotify($args, $is_anonymous);
				$notify_member_srl[] = abs($member_srl);
			}
		}

		if(!$parent_srl || ($parent_srl && $config->document_notify == 'all-comment'))
		{
			$oDocumentModel = &getModel('document');
			$oDocument = $oDocumentModel->getDocument($document_srl);

			$member_srl = $oDocument->get('member_srl');
			// !TODO 공용 메소드로 분리
			if(!in_array(abs($member_srl), $notify_member_srl) && (!$logged_info || ($member_srl != 0 && abs($member_srl) != $logged_info->member_srl)))
			{
				$args = new stdClass();
				$args->member_srl = abs($member_srl);
				$args->srl = $document_srl;
				$args->target_srl = $comment_srl;
				$args->type = $this->_TYPE_DOCUMENT;
				$args->target_type = $this->_TYPE_COMMENT;
				$args->target_url = getNotEncodedFullUrl('', 'document_srl', $document_srl, '_comment_srl', $comment_srl) . '#comment_'. $comment_srl;
				$args->target_summary = cut_str(strip_tags($content), 30);
				$args->target_nick_name = $obj->nick_name;
				$args->target_email_address = $obj->email_address;
				$args->regdate = $regdate;
				$args->notify = $this->_getNotifyId($args);
				$output = $this->_insertNotify($args, $is_anonymous);
			}
		}

		return new Object();
	}

	function triggerBeforeModuleObjectProc(&$oModule)
	{
		$oNcenterliteModel = &getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		$vars = Context::getRequestVars();
		$logged_info = Context::get('logged_info');

		// 쪽지 체크
		if($config->message_notify != 'N')
		{
			$flag_path = './files/ncenterlite/new_message_flags/';

			$need_update = false;
			// 쪽지 알림 메시지 체크
			if(strpos(Context::getHtmlFooter(), 'xeNotifyMessage') !== FALSE)
			{
				$need_update = true;
			}
			// 메시지 플래그 파일 체크
			else if(file_exists($flag_path . $logged_info->member_srl))
			{
				$need_update = true;
			}

			if($oModule->act == 'procCommunicationSendMessage')
			{
				FileHandler::makeDir($flag_path);
				$flag_file = sprintf('%s%s', $flag_path, $vars->receiver_srl);
				FileHandler::writeFile($flag_file, $vars->receiver_srl);
			}
			else if($need_update)
			{
				$oMemberModel = &getModel('member');
				$_sender_member_srl = trim(FileHandler::readFile($flag_path . $logged_info->member_srl));
				$sender_member_info = $oMemberModel->getMemberInfoByMemberSrl($_sender_member_srl);
				FileHandler::removeFile($flag_path . $logged_info->member_srl);

				// 새 쪽지 수
				$args->receiver_srl = $logged_info->member_srl;
				$output = executeQuery('ncenterlite.getCountNewMessage', $args);
				$message_count = $output->data->count;

				// 기존 쪽지 알림을 읽은 것으로 변경
				$cond = null;
				$cond->type = $this->_TYPE_MESSAGE;
				$cond->member_srl = $logged_info->member_srl;
				$output = executeQuery('ncenterlite.updateNotifyReadedByType', $cond);

				if(!$message_count) return;

				// 알림 추가
				$args = new stdClass();
				$args->member_srl = $logged_info->member_srl;
				$args->srl = $sender_member_info->member_srl;
				if(!$args->srl) $args->srl = 0;
				$args->target_srl = $sender_member_info->member_srl;
				if(!$args->srl) $args->target_srl = 0;
				$args->type = $this->_TYPE_MESSAGE;
				$args->target_type = $this->_TYPE_MESSAGE;
				$args->target_url_params = $target_url_params;
				$args->target_summary = $message_count;
				$args->target_nick_name = $sender_member_info->nick_name;
				$args->target_member_srl = $sender_member_info->member_srl;
				$args->regdate = date('YmdHis');
				$args->notify = $this->_getNotifyId($args);

				if($this->message_mid)
				{
					$args->target_url = getNotEncodedFullUrl('', 'mid', $this->message_mid, 'act', 'dispCommunicationMessages');
				}
				else
				{
					$args->target_url = getNotEncodedFullUrl('', 'act', 'dispCommunicationMessages');
				}

				$output = $this->_insertNotify($args, $is_anonymous);
			}
		}
	}

	function triggerAfterDeleteComment(&$obj)
	{
		$oNcenterliteModel = &getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if($config->use != 'Y') return new Object();

		$args->srl = $obj->comment_srl;
		$output = executeQuery('ncenterlite.deleteNotifyBySrl', $args);
		return new Object();
	}

	function triggerAfterDeleteDocument(&$obj)
	{
		$oNcenterliteModel = &getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if($config->use != 'Y') return new Object();

		$args->srl = $obj->document_srl;
		$output = executeQuery('ncenterlite.deleteNotifyBySrl', $args);
		return new Object();
	}

	function triggerAfterModuleHandlerProc(&$oModule)
	{
		$vars = Context::getRequestVars();
		$logged_info = Context::get('logged_info');

		if($oModule->getLayoutFile() == 'popup_layout.html') Context::set('ncenterlite_is_popup', TRUE);

		$oNcenterliteModel = &getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if($config->use != 'Y') return new Object();

		$this->_hide_ncenterlite = false;
		if($oModule->module == 'beluxe' && Context::get('is_modal'))
		{
			$this->_hide_ncenterlite = true;
		}
		if($oModule->module == 'bodex' && Context::get('is_iframe'))
		{
			$this->_hide_ncenterlite = true;
		}
		if($oModule->getLayoutFile() == 'popup_layout.html')
		{
			$this->_hide_ncenterlite = true;
		}

		if($oModule->act == 'dispBoardReplyComment')
		{
			$comment_srl = Context::get('comment_srl');
			$logged_info = Context::get('logged_info');
			if($comment_srl && $logged_info)
			{
				$args->target_srl = $comment_srl;
				$args->member_srl = $logged_info->member_srl;
				executeQuery('ncenterlite.updateNotifyReadedByTargetSrl', $args);
			}
		}
		else if($oModule->act == 'dispBoardContent')
		{
			$comment_srl = Context::get('_comment_srl');
			$document_srl = Context::get('document_srl');
			$oDocument = Context::get('oDocument');
			$logged_info = Context::get('logged_info');

			if($document_srl && $logged_info)
			{
				$args->target_srl = $document_srl;
				$args->member_srl = $logged_info->member_srl;
				executeQuery('ncenterlite.updateNotifyReadedByTargetSrl', $args);
			}

			if($comment_srl && $document_srl && $oDocument)
			{
				$_comment_list = $oDocument->getComments();
				if($_comment_list)
				{
					if(array_key_exists($comment_srl, $_comment_list))
					{
						$url = getNotEncodedUrl('_comment_srl','') . '#comment_' . $comment_srl;
						$need_check_socialxe = true;
					}
					else
					{
						$cpage = $oDocument->comment_page_navigation->cur_page;
						if($cpage > 1)
						{
							$url = getNotEncodedUrl('cpage', $cpage-1) . '#comment_' . $comment_srl;
							$need_check_socialxe = true;
						}
						else
						{
							$url = getNotEncodedUrl('_comment_srl', '', 'cpage', '') . '#comment_' . $comment_srl;
						}
					}

					if($need_check_socialxe)
					{
						$oDB = &DB::getInstance();
						if($oDB->isTableExists('socialxe'))
						{
							unset($args);
							$oModuleModel = &getModel('module');
							$module_info = $oModuleModel->getModuleInfoByDocumentSrl($document_srl);
							$args->module_srl = $module_info->module_srl;
							$output = executeQuery('ncenterlite.getSocialxeCount', $args);
							if($output->data->cnt)
							{
								$socialxe_comment_srl = $comment_srl;

								unset($args);
								$args->comment_srl = $comment_srl;
								$oCommentModel = &getModel('comment');
								$oComment = $oCommentModel->getComment($comment_srl);
								$parent_srl = $oComment->get('parent_srl');
								if($parent_srl)
								{
									$socialxe_comment_srl = $parent_srl;
								}

								$url = getNotEncodedUrl('_comment_srl', '', 'cpage', '', 'comment_srl', $socialxe_comment_srl) . '#comment_' . $comment_srl;
							}
						}
					}

					$url = str_replace('&amp;','&',$url);
					header('location: ' . $url);
					Context::close();
					exit;
				}
			}
		}

		// 지식인 모듈의 의견
		// TODO: 코드 분리
		if($oModule->act == 'procKinInsertComment')
		{
			// 글, 댓글 구분
			$parent_type = ($vars->document_srl == $vars->parent_srl) ? 'DOCUMENT' : 'COMMENT';
			if($parent_type == 'DOCUMENT') {
				$oDocumentModel = &getModel('document');
				$oDocument = $oDocumentModel->getDocument($vars->document_srl);
				$member_srl = $oDocument->get('member_srl');
				$type = $this->_TYPE_DOCUMENT;
			} else {
				$oCommentModel = &getModel('comment');
				$oComment = $oCommentModel->getComment($vars->parent_srl);
				$member_srl = $oComment->get('member_srl');
				$type = $this->_TYPE_COMMENT;
			}

			if($logged_info->member_srl != $member_srl)
			{
				$args = new stdClass();
				$args->member_srl = abs($member_srl);
				$args->srl = ($parent_type == 'DOCUMENT') ? $vars->document_srl : $vars->parent_srl;
				$args->type = $type;
				$args->target_type = $this->_TYPE_COMMENT;
				$args->target_srl = $vars->parent_srl;
				$args->target_url = getNotEncodedFullUrl('', 'document_srl', $vars->document_srl, '_comment_srl', $vars->parent_srl) . '#comment_'. $vars->parent_srl;
				$args->target_summary = cut_str(strip_tags($vars->content), 30);
				$args->target_nick_name = $logged_info->nick_name;
				$args->target_email_address = $logged_info->email_address;
				$args->regdate = date('YmdHis');
				$args->notify = $this->_getNotifyId($args);
				$output = $this->_insertNotify($args);
			}
		}
		else if($oModule->act == 'dispKinView' || $oModule->act == 'dispKinIndex')
		{
			// 글을 볼 때 알림 제거
			$oDocumentModel = &getModel('document');
			$oDocument = $oDocumentModel->getDocument($vars->document_srl);
			$member_srl = $oDocument->get('member_srl');

			if($logged_info->member_srl == $member_srl)
			{
				$args = new stdClass;
				$args->member_srl = $logged_info->member_srl;
				$args->srl = $vars->document_srl;
				$args->type = $this->_TYPE_DOCUMENT;
				$output = executeQuery('ncenterlite.updateNotifyReadedBySrl', $args);
			}
		}
		else if($oModule->act == 'getKinComments')
		{
			// 의견을 펼칠 때 알림 제거
			$args = new stdClass;
			$args->member_srl = $logged_info->member_srl;
			$args->target_srl = $vars->parent_srl;
			$output = executeQuery('ncenterlite.updateNotifyReadedByTargetSrl', $args);
		}

		return new Object();
	}

	function triggerBeforeDisplay(&$output_display)
	{
		// 팝업창이면 중지
		if(Context::get('ncenterlite_is_popup')) return;

		if(count($this->disable_notify_bar_act))
		{
			if(in_array(Context::get('act'), $this->disable_notify_bar_act)) return;
		}

		// HTML 모드가 아니면 중지 + act에 admin이 포함되어 있으면 중지
		if(Context::getResponseMethod() != 'HTML' || strpos(strtolower(Context::get('act')), 'admin') !== false) return;

		$logged_info = Context::get('logged_info');

		// 로그인 상태가 아니면 중지
		if(!$logged_info) return;

		$module_info = Context::get('module_info');

		if(count($this->disable_notify_bar_mid))
		{
			if(in_array($module_info->mid, $this->disable_notify_bar_mid)) return;
		}

		// admin 모듈이면 중지
		if($module_info->module == 'admin' ) return;

		$oNcenterliteModel = &getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();

		// 알림센터가 비활성화 되어 있으면 중지
		if($config->use != 'Y') return new Object();

		// 노티바 제외 페이지이면 중지
		if(in_array($module_info->module_srl, $config->hide_module_srls)) return new Object();

		Context::set('ncenterlite_config', $config);

		$oModuleModel = &getModel('module');
		$ncenterlite_module_info = $oModuleModel->getModuleInfoXml('ncenterlite');
		$jsCacheRefresh = '?'.$ncenterlite_module_info->version.'.'.$ncenterlite_module_info->date.'.js';
		Context::addJsFile('./modules/ncenterlite/tpl/js/ncenterlite.js'.$jsCacheRefresh, true, '', 100000);


		$oNcenterliteModel = &getModel('ncenterlite');

		// 알림 목록 가져오기
		$_output = $oNcenterliteModel->getMyNotifyList();
		if(!$_output->data) return; // 알림 메시지가 없어도 항상 표시하게 하려면 이 줄을 제거 또는 주석 처리하세요.

		$_latest_notify_id = array_slice($_output->data, 0, 1);
		$_latest_notify_id = $_latest_notify_id[0]->notify;
		Context::set('ncenterlite_latest_notify_id', $_latest_notify_id);

		if($_COOKIE['_ncenterlite_hide_id'] && $_COOKIE['_ncenterlite_hide_id'] == $_latest_notify_id) return;
		setcookie('_ncenterlite_hide_id', '', 0, '/');

		$oMemberModel = &getModel('member');
		$memberConfig = $oMemberModel->getMemberConfig();
		if($memberConfig->profile_image == 'Y')
		{
			$profileImage = $oMemberModel->getProfileImage($logged_info->member_srl);
			Context::set('profileImage', $profileImage);
		}
		Context::set('useProfileImage', ($memberConfig->profile_image == 'Y') ? true : false);

		Context::set('ncenterlite_list', $_output->data);
		Context::set('ncenterlite_page_navigation', $_output->page_navigation);

		$this->template_path = sprintf('%sskins/%s/', $this->module_path, $config->skin);
		if(!is_dir($this->template_path)||!$config->skin)
		{
			$config->skin = 'default';
			$this->template_path = sprintf('%sskins/%s/',$this->module_path, $config->skin);
		}

		if($config->skin == 'default')
		{
			Context::addHtmlFooter('<script type="text/javascript">');
			if($config->message_notify != 'N') Context::addHtmlFooter('window.xeNotifyMessage = function() {};');
			Context::addHtmlFooter('(function(){setTimeout(function(){var s = jQuery(document).scrollTop();jQuery(document).scrollTop(s-30);}, 700);})();</script>');
		}

		$this->_addFile();
		$html = $this->_getTemplate();
		$output_display = $html . $output_display;
	}

	function _addFile()
	{
		$oModuleModel = &getModel('module');
		$module_info = $oModuleModel->getModuleInfoXml('ncenterlite');
		if(file_exists(FileHandler::getRealPath($this->template_path . 'ncenterlite.css')))
		{
			Context::addCssFile($this->template_path . 'ncenterlite.css', true, 'all', '', 100);
		}

		$oNcenterliteModel = &getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		if($config->colorset && file_exists(FileHandler::getRealPath($this->template_path . 'ncenterlite.' . $config->colorset . '.css')))
		{
			Context::addCssFile($this->template_path . 'ncenterlite.'.$config->colorset.'.css', true, 'all', '', 100);
		}

		if($config->zindex)
		{
			Context::set('ncenterlite_zindex', ' style="z-index:' . $config->zindex . ';" ');
		}

		if(Mobile::isFromMobilePhone())
		{
			Context::loadFile(array('./common/js/jquery.min.js', 'head', '', -100000), true);
			Context::loadFile(array('./common/js/xe.min.js', 'head', '', -100000), true);
			Context::addCssFile($this->template_path . 'ncenterlite.mobile.css', true, 'all', '', 100);
		}
	}

	function _getTemplate()
	{
		$oNcenterModel = &getModel('ncenterlite');
		$config = $oNcenterModel->getConfig();

		$oTemplateHandler = TemplateHandler::getInstance();
		$result = '';

		$path = sprintf('%sskins/%s/', $this->module_path, $config->skin);
		$result = $oTemplateHandler->compile($path, 'ncenterlite.html');

		return $result;
	}

	function updateNotifyRead($notify, $member_srl)
	{
		$args->member_srl = $member_srl;
		$args->notify = $notify;
		//$output = executeQuery('ncenterlite.updateNotifyReaded', $args);
		$output = executeQuery('ncenterlite.deleteNotify', $args);

		return $output;
	}

	function updateNotifyReadiByTargetSrl($target_srl, $member_srl)
	{
		$args->member_srl = $member_srl;
		$args->target_srl = $target_srl;
		//$output = executeQuery('ncenterlite.updateNotifyReadedByTargetSrl', $args);
		$output = executeQuery('ncenterlite.deleteNotifyByTargetSrl', $args);

		return $output;
	}

	function updateNotifyReadAll($member_srl)
	{
		$args->member_srl = $member_srl;
		//$output = executeQuery('ncenterlite.updateNotifyReadedAll', $args);
		$output = executeQuery('ncenterlite.deleteNotifyByMemberSrl', $args);

		return $ouptut;
	}

	function procNcenterliteNotifyRead()
	{
		$logged_info = Context::get('logged_info');
		$target_srl = Context::get('target_srl');
		if(!$logged_info || !$target_srl) return new Object(-1, 'msg_invalid_request');

		$output = $this->updateNotifyRead($notify, $logged_info->member_srl);
		return $output;
	}

	function procNcenterliteNotifyReadAll()
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_invalid_request');

		$output = $this->updateNotifyReadAll($logged_info->member_srl);
		return $output;
	}

	function procNcenterliteRedirect()
	{
		$logged_info = Context::get('logged_info');
		$url = Context::get('url');
		$notify = Context::get('notify');
		if(!$logged_info || !$url || !$notify) return new Object(-1, 'msg_invalid_request');

		$output = $this->updateNotifyRead($notify, $logged_info->member_srl);
		if(!$output->toBool()) return $output;

		$url = str_replace('&amp;', '&', $url);
		header('location: ' . $url);
		Context::close();
		exit;
	}

	/**
	 * @brief 익명으로 노티해야 할지 체크하여 반환
	 * @return boolean
	 **/
	function _isAnonymous($source_type, $triggerObj)
	{
		// 회원번호가 음수
		if($triggerObj->member_srl < 0) return TRUE;

		$module_info = Context::get('module_info');

		// DX 익명 체크박스
		if($module_info->module == 'beluxe' && $triggerObj->anonymous == 'Y') return TRUE;

		if($source_type == $this->_TYPE_COMMENT)
		{
			// DX 익명 강제
			if($module_info->module == 'beluxe' && $module_info->use_anonymous == 'Y') return TRUE;
		}

		if($source_type == $this->_TYPE_DOCUMENT)
		{
			// DX 익명 강제
			if($module_info->module == 'beluxe' && $module_info->use_anonymous == 'Y') return TRUE;
		}

		return FALSE;
	}

	function _insertNotify($args, $anonymous = FALSE)
	{
		// 비회원 노티 제거
		if($args->member_srl <= 0) return new Object();

		$logged_info = Context::get('logged_info');

		if($anonymous == TRUE)
		{
			// 익명 노티 시 회원정보 제거
			$args->target_member_srl = 0;
			$args->target_nick_name = 'Anonymous';
			$args->target_user_id = 'Anonymous';
			$args->target_email_address = 'Anonymous';

			$oMemberModel = &getModel('member');
			$push_sender_info = $oMemberModel->getMemberInfoByMemberSrl($args->member_srl);
			if($push_sender_info->obid){
				$oAppModuleModel = &getModel('app_module');
				$oAppModuleModel->getPushMessage($args,$push_sender_info->obid);
			}
		}
		else if($logged_info)
		{
			// 익명 노티가 아닐 때 로그인 세션의 회원정보 넣기
			$args->target_member_srl = $logged_info->member_srl;
			$args->target_nick_name = $logged_info->nick_name;
			$args->target_user_id = $logged_info->user_id;
			$args->target_email_address = $logged_info->email_address;

			$oMemberModel = &getModel('member');
			$push_sender_info = $oMemberModel->getMemberInfoByMemberSrl($args->member_srl);
			if($push_sender_info->obid){
				$oAppModuleModel = &getModel('app_module');
				$oAppModuleModel->getPushMessage($args,$push_sender_info->obid);
			}
		}
		else
		{
			// 비회원
			$args->target_member_srl = 0;
			$args->target_user_id = '';
		}

		$output = executeQuery('ncenterlite.insertNotify', $args);
		return $output;
	}

	/**
	 * @brief 노티 ID 반환
	 **/
	function _getNotifyId($args)
	{
		return md5(uniqid('') . $args->member_srl . $args->srl . $args->target_srl . $args->type . $args->target_type);
	}

	/**
	 * @brief 멘션 대상 member_srl 목록 반환
	 * @return array
	 **/
	function _getMentionTarget($content)
	{
		$oNcenterliteModel = &getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();
		$logged_info = Context::get('logged_info');

		$list = array();

		$content = strip_tags($content);
		$content = str_replace('&nbsp;', ' ', $content);

		// 정규표현식 정리
		$split = array();
		if(in_array('comma', $config->mention_format)) $split[] = ',';
		$regx = join('', array('/(^|\s)@([^@\s', join('', $split), ']+)/i'));

		preg_match_all($regx, $content, $matches);

		// '님'문자 이후 제거
		if(in_array('respect', $config->mention_format))
		{
			foreach($matches[2] as $idx => $item)
			{
				$pos = strpos($item, '님');
				if($pos !== false && $pos > 0)
				{
					$matches[2][$idx] = trim(substr($item, 0, $pos));
					if($logged_info && $logged_info->nick_name == $matches[2][$idx]) unset($matches[2][$idx]);
				}
			}
		}

		$nicks = array_unique($matches[2]);

		foreach($nicks as $nick_name)
		{
			$vars = null;
			$vars->nick_name = $nick_name;
			$output = executeQuery('ncenterlite.getMemberSrlByNickName', $vars);
			if($output->data && $output->data->member_srl) $list[] = $output->data->member_srl;
		}

		return $list;
	}
}

