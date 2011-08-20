<?php

	class qa_html_theme_layer extends qa_html_theme_base {

		var $signatures;
		
	// theme replacement functions
	
		function doctype()
		{
			if (qa_opt('signatures_enable')) {

				// add user signature

				if($this->template == 'user') { 
					$sig_form = $this->user_signature_form();
					
					// readd scripts for forms...
					
					$script=array('<SCRIPT TYPE="text/javascript"><!--');
					
					global $qa_root_url_relative;
					$qa_content = $this->content;
					
					if (isset($qa_content['script_var']))
						foreach ($qa_content['script_var'] as $var => $value)
							$script[]='var '.$var.'='.qa_js($value).';';
							
					if (isset($qa_content['script_lines']))
						foreach ($qa_content['script_lines'] as $scriptlines) {
							$script[]='';
							$script=array_merge($script, $scriptlines);
						}
						
					if (isset($qa_content['focusid']))
						$qa_content['script_onloads'][]=array(
							"var elem=document.getElementById(".qa_js($qa_content['focusid']).");",
							"if (elem) {",
							"\telem.select();",
							"\telem.focus();",
							"}",
						);
						
					if (isset($qa_content['script_onloads'])) {
						array_push($script,
							'',
							'var qa_oldonload=window.onload;',
							'window.onload=function() {',
							"\tif (typeof qa_oldonload=='function')",
							"\t\tqa_oldonload();"
						);
						
						foreach ($qa_content['script_onloads'] as $scriptonload) {
							$script[]="\t";
							
							foreach ((array)$scriptonload as $scriptline)
								$script[]="\t".$scriptline;
						}
				
						$script[]='}';
					}
					
					$script[]='//--></SCRIPT>';
					
					if (isset($qa_content['script_rel'])) {
						$uniquerel=array_unique($qa_content['script_rel']); // remove any duplicates
						foreach ($uniquerel as $script_rel)
							$script[]='<SCRIPT SRC="'.qa_html($qa_root_url_relative.$script_rel).'" TYPE="text/javascript"></SCRIPT>';
					}
					
					if (isset($qa_content['script_src']))
						foreach ($qa_content['script_src'] as $script_src)
							$script[]='<SCRIPT SRC="'.qa_html($script_src).'" TYPE="text/javascript"></SCRIPT>';
				
					$this->content['script']=array_merge($this->content['script'],$script);

				
				// insert our form
					
					if($this->content['q_list']) {  // paranoia
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
					else $this->content['form-signature'] = $sig_form;  // this shouldn't happen

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
			if($this->template == 'user' && qa_opt('signatures_enable')) {
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
			if (qa_opt('signatures_enable') && qa_opt('signatures_q_enable')) {
				$result = qa_db_read_all_assoc(
					qa_db_query_sub(
						'SELECT signature,userid,format FROM ^usersignatures'
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
				qa_error_log($this->signatures);
				if(@$this->signatures['user'.$q_view['raw']['userid']]) $q_view['content'].=qa_opt('signatures_header').$this->signatures['user'.$q_view['raw']['userid']].qa_opt('signatures_footer');
			}
			
			qa_html_theme_base::q_view_content($q_view);

		}
		function a_item_content($a_item)
		{
			if (qa_opt('signatures_enable') && qa_opt('signatures_a_enable')) {
				if(isset($this->signatures[$a_item['raw']['userid']])) $a_item['content'].=qa_opt('signatures_header').$this->signatures[$a_item['raw']['userid']].qa_opt('signatures_footer');
			}
			qa_html_theme_base::a_item_content($a_item);

		}
		function c_item_content($c_item)
		{
			if (qa_opt('signatures_enable') && qa_opt('signatures_c_enable')) {
				if(isset($this->signatures[$c_item['raw']['userid']])) $c_item['content'].=qa_opt('signatures_header').$this->signatures[$c_item['raw']['userid']].qa_opt('signatures_footer');
			}
			qa_html_theme_base::c_item_content($c_item);
		}
		
	// worker functions

		function user_signature_form() {
			// displays signature form in user profile
			
			global $qa_request;
			
			$handle = preg_replace('/^[^\/]+\/([^\/]+).*/',"$1",$qa_request);
			
			$userid = $this->getuserfromhandle($handle);
			
			if(!$userid) return;

			if(qa_get_logged_in_handle() == $handle) {

				$ok = null;
				
				$formats = qa_list_modules('editor');
				
				$editorname = $formats[qa_opt('signatures_format')];
				$editor=qa_load_module('editor', $editorname);
				error_log(qa_opt('editor_for_qs'));
				qa_error_log($editor);
				
				if (qa_clicked('signature_save')) {
				
					if(strlen(qa_post_text('signature_text')) > qa_opt('signatures_length')) {
						$error = 'Max possible signature length is 1000 characters';
					}
					else {
						
						$readdata=$editor->read_post('signature_text');
						$informat=$readdata['format'];	
						
						$incontent = qa_post_text('signature_text');
						
						qa_db_query_sub(
							'INSERT INTO ^usersignatures (userid,signature,format) VALUES (#,$,$) ON DUPLICATE KEY UPDATE signature=$,format=$',
							$userid,$incontent,$informat,$incontent,$informat
						);
						$ok = 'Signature Saved.';
					}
				}
				$content = qa_db_read_one_assoc(
					qa_db_query_sub(
						'SELECT signature,format FROM ^usersignatures WHERE userid=#',
						$userid
					),
					true
				);
				
				$fields['content'] = $editor->get_field($this->content, $content['signature'], $content['format'], 'signature_text', 12, true);

				if((!$editorname || $editorname == 'Markdown Editor')) $fields['elCount'] = array(
					'label' => '<div id="elCount">'.qa_opt('signatures_length').'</div>',
					'type' => 'static',
				);

				$form=array(
				
					'ok' => ($ok && !isset($error)) ? $ok : null,
					
					'style' => 'tall',
					
					'title' => '<a name="signature_text"></a>Signature',

					'tags' =>  'action="'.qa_self_html().'#signature_text" method="POST"',
					
					'fields' => $fields,
					
					'buttons' => array(
						array(
							'label' => qa_lang_html('main/save_button'),
							'tags' => 'NAME="signature_save"',
						),
					),
					
					'hidden' => array(
						'editor' => qa_html($editorname),
						'dosavesig' => '1',
					),
				);
				return $form;
			}
			else if(qa_opt('signatures_profile_enable')) {
				$content = qa_db_read_one_assoc(
					qa_db_query_sub(
						'SELECT signature,format FROM ^usersignatures WHERE userid=#',
						$userid
					),
					true
				);

				if(!$content) return;

				$informat=$content['format'];					
				$viewer=qa_load_viewer($content['signature'], $informat);
				
				global $options;
				
				$signature=qa_viewer_html($content['signature'], $informat, array(
					'blockwordspreg' => @$options['blockwordspreg'],
					'showurllinks' => @$options['showurllinks'],
					'linksnewwindow' => @$options['linksnewwindow'],
				));

				$fields[] = array(
						'label' => qa_opt('signatures_header').$signature.qa_opt('signatures_footer'),
						'type' => 'static',
				);

				return array(
					'title' => 'Signature',
					'fields' => $fields,
					'style' => 'tall'
				);
			}				
			
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

