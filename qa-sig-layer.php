<?php

	class qa_html_theme_layer extends qa_html_theme_base {

		function option_default($option) {
			
			switch($option) {
				case 'signatures_length':
					return 1000;
				default:
					return false;
			}
			
		}
		function allow_template($template)
		{
			return ($template!='user');
		}	
		
	// theme replacement functions
	
		function main_parts($content)
		{
			if (qa_opt('signatures_enable')) {
			
				// add user badge list

				if($this->template == 'user') { 
					if($content['q_list']) {  // paranoia
					
						// array splicing kungfu thanks to Stack Exchange
						
						// This adds custom-badges before q_list
					
						$keys = array_keys($content);
						$vals = array_values($content);

						$insertBefore = array_search('q_list', $keys);

						$keys2 = array_splice($keys, $insertBefore);
						$vals2 = array_splice($vals, $insertBefore);

						$keys[] = 'custom-badges';
						$vals[] = $this->user_signature_form();

						$content = array_merge(array_combine($keys, $vals), array_combine($keys2, $vals2));
					}
					else $content['custom'] = $this->user_signature_form();  // this shouldn't happen

				}
			}

			qa_html_theme_base::main_parts($content);

		}

	// worker functions

		function user_badge_form() {

			$ok = null;
			
			if (qa_clicked('signature_save')) {
				if(qa_post_text('signature_text') > qa_opt('signatures_length')) {
					$error = 'Max possible signature length is 1000 characters';
				}
				else {
					if(!qa_opt('signatures_c_enable') && qa_post_text('signatures_enable')) {
						$table_exists = qa_db_read_one_value(qa_db_query_sub('SHOW TABLES LIKE ^usersignature'),true);
						if(!$table_exists) {
							qa_db_query_sub(
							'CREATE TABLE ^usersignatures ('.
								'userid INT(11) NOT NULL,'.
								'signature VARCHAR (1000) DEFAULT \'\','.
								'id INT(11) NOT NULL AUTO_INCREMENT,'.
								'PRIMARY KEY (id)'.
							') ENGINE=MyISAM DEFAULT CHARSET=utf8'
							);			
						}
					}
					qa_opt('signatures_enable',qa_post_text('signatures_enable'));
					qa_opt('signatures_q_enable',qa_post_text('signatures_q_enable'));
					qa_opt('signatures_a_enable',qa_post_text('signatures_a_enable'));
					qa_opt('signatures_c_enable',qa_post_text('signatures_c_enable'));
					qa_opt('signatures_length',qa_post_text('signatures_length'));
					qa_opt('signatures_format',(int)qa_post_text('signatures_format'));
					$ok = 'Settings Saved.';
				}
			}
			
			// displays signature form in user profile
			
			global $qa_request;
			
			$handle = preg_replace('/^[^\/]+\/([^\/]+).*/',"$1",$qa_request);
			
			$userid = $this->getuserfromhandle($handle);
			
			if(!$userid) return;

			$result = qa_db_read_one_value(
				qa_db_query_sub(
					'SELECT signature FROM ^usersignatures WHERE userid=#',
					$userid
				),
				true
			);
			
			if(qa_get_logged_in_handle() == $handle) {
				$fields[] = array(
						'label' => 'Signature',
						'tags' => 'ROWS="8" NAME="signature_text"',
						'value' => @$result['signature'],
						'type' => 'textarea',
				);
				$buttons[] = array(
						'label' => 'Save',
						'tags' => 'NAME="signature_save"',
					),

				return array(
					'title'=> 'Signature',
					
					'ok' => ($ok && !isset($error)) ? $ok : null,

					'fields' => $fields,

					'buttons' => $buttons
				);
			}
			else {
				$fields[] = array(
						'label' => @$result['signature'],
						'type' => 'static',
				);
				return array(
					'title' => 'Signature',
					'fields' => $fields
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

