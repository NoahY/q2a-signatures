<?php

	class qa_html_theme_layer extends qa_html_theme_base {

		var $signatures;
		
	// theme replacement functions
	
		function doctype()
		{
			if (qa_opt('signatures_enable')) {
				if($this->request == 'admin/permissions') {

					$permits[] = 'signature_allow';
					$permits[] = 'signature_edit_allow';			
					foreach($permits as $optionname) {
						$value = qa_opt($optionname);
						$optionfield=array(
							'id' => $optionname,
							'label' => qa_lang_html('signature_plugin/'.$optionname).':',
							'tags' => 'NAME="option_'.$optionname.'" ID="option_'.$optionname.'"',
							'value' => $value,
							'error' => qa_html(@$errors[$optionname]),
						);					
						$widest=QA_PERMIT_USERS;
						$narrowest=QA_PERMIT_ADMINS;
						
						$permitoptions=qa_admin_permit_options($widest, $narrowest, (!QA_FINAL_EXTERNAL_USERS) && qa_opt('confirm_user_emails'));
						
						if (count($permitoptions)>1)
							qa_optionfield_make_select($optionfield, $permitoptions, $value,
								($value==QA_PERMIT_CONFIRMED) ? QA_PERMIT_USERS : min(array_keys($permitoptions)));
						$this->content['form']['fields'][$optionname]=$optionfield;

						$this->content['form']['fields'][$optionname.'_points']= array(
							'id' => $optionname.'_points',
							'tags' => 'NAME="option_'.$optionname.'_points" ID="option_'.$optionname.'_points"',
							'type'=>'number',
							'value'=>qa_opt($optionname.'_points'),
							'prefix'=>qa_lang_html('admin/users_must_have').'&nbsp;',
							'note'=>qa_lang_html('admin/points')
						);
						$checkboxtodisplay[$optionname.'_points']='(option_'.$optionname.'=='.qa_js(QA_PERMIT_POINTS).') ||(option_'.$optionname.'=='.qa_js(QA_PERMIT_POINTS_CONFIRMED).')';
					}
					qa_set_display_rules($this->content, $checkboxtodisplay);
				}

				// add user signature

				if($this->template == 'user' && isset($this->content['form_activity']) && !qa_get('tab')) { 
					
					$sig_form = $this->content['user_signature_form']; // from overrides
					
				// insert our form
					
					if(isset($this->content['q_list'])) {
						// array splicing kungfu thanks to Stack Exchange
						
						// This adds form-signature before q_list
					
						$keys = array_keys($this->content);
						$vals = array_values($this->content);

						$insertBefore = array_search('q_list', $keys);

						$keys2 = array_splice($keys, $insertBefore);
						$vals2 = array_splice($vals, $insertBefore);

						$keys[] = 'form-signature';
						$vals[] = $sig_form;

						$this->content = array_merge(array_combine($keys, $vals), array_combine($keys2, $vals2));
					}
					else $this->content['form-signature'] = $sig_form;

				}
			}

			qa_html_theme_base::doctype();
		}
		function head_css()
		{
			qa_html_theme_base::head_css();
			if($this->template == 'user' && qa_opt('signatures_enable')) {
					$this->output_raw('
<style>
	.sig-left-green {
		color:green;
	}
	.sig-left-orange {
		color:orange;
	}
	.sig-left-red {
		color:red;
	}
	
</style>');		
			}	
		}
		function head_custom()
		{
			qa_html_theme_base::head_custom();
			if(@$this->template == 'user' && qa_opt('signatures_enable')) {
				$formats = qa_list_modules('editor');
				$editorname = $formats[qa_opt('signatures_format')];
				$handle = preg_replace('/^[^\/]+\/([^\/]+).*/',"$1",$this->request);
				if(qa_get_logged_in_handle() == $handle && (!$editorname || $editorname == 'Markdown Editor')) {
					$this->output_raw('<script src="'.QA_HTML_THEME_LAYER_URLTOROOT.'textLimitCount.js" type="text/javascript"></script>');
					$this->output_raw("
<script>
	var signature_max_length = ".(qa_opt('signatures_length')?qa_opt('signatures_length'):1000).";
	jQuery('document').ready(function(){
		textLimiter(jQuery('textarea[name=\"signature_text\"]'),{
		maxLength: signature_max_length,
		elCount: 'elCount'
	  });
	});
</script>");
				}
			}
		}
	
		function q_view_content($q_view)
		{
			$this->signatures = array();
			if (qa_opt('signatures_enable')) {
				$result = qa_db_read_all_assoc(
					qa_db_query_sub(
						'SELECT BINARY signature AS signature, userid,format FROM ^usersignatures'
					)
				);
				
				foreach($result as $user) {
					if ($user['signature']) {
						
						$informat=$user['format'];					
						
						$viewer=qa_load_viewer($user['signature'], $informat);
						
						global $options;
						
						$signature=$viewer->get_html($user['signature'], $informat, array(
							'blockwordspreg' => @$options['blockwordspreg'],
							'showurllinks' => @$options['showurllinks'],
							'linksnewwindow' => @$options['linksnewwindow'],
						));
						$this->signatures['user'.$user['userid']] = $signature;
					}
				}
				
				if(qa_opt('signatures_q_enable') && @$this->signatures['user'.$q_view['raw']['userid']]) {
					if(!isset($q_view['content'])) $q_view['content'] = '';
					$q_view['content'].=$this->signature_output($q_view['raw']['userid']);
				}
			}
			
			qa_html_theme_base::q_view_content($q_view);

		}
		function a_item_content($a_item)
		{
			if (qa_opt('signatures_enable') && qa_opt('signatures_a_enable')) {
				if(isset($this->signatures['user'.$a_item['raw']['userid']]) && isset($a_item['content']))
					$a_item['content'].=$this->signature_output($a_item['raw']['userid']);
			}
			qa_html_theme_base::a_item_content($a_item);

		}
		function c_item_content($c_item)
		{
			if (qa_opt('signatures_enable') && qa_opt('signatures_c_enable')) {
				if(isset($this->signatures['user'.$c_item['raw']['userid']]) && isset($c_item['content'])) 
					$c_item['content'].=$this->signature_output($c_item['raw']['userid']);
			}
			qa_html_theme_base::c_item_content($c_item);
		}
		
	// worker functions

		function signature_output($uid) {
			if(qa_opt('signatures_html')) 
				$sig = $this->signatures['user'.$uid];
			else
				$sig = strip_tags($this->signatures['user'.$uid]);
			
			$sig = preg_replace('/nofollow/','',$sig);
				
			return qa_opt('signatures_header').$sig.qa_opt('signatures_footer');
		}

		function getuserfromhandle($handle) {
			require_once QA_INCLUDE_DIR.'qa-app-users.php';
			
			if (QA_FINAL_EXTERNAL_USERS) {
				$publictouserid=qa_get_userids_from_public(array($handle));
				$userid=@$publictouserid[$handle];
				
			} 
			else {
				$userid = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT userid FROM ^users WHERE handle = $',
						$handle
					),
					true
				);
			}
			if (!isset($userid)) return;
			return $userid;
		}
	}

