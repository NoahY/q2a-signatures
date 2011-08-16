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

						$keys[] = 'form-signature';
						$vals[] = $this->user_signature_form();

						$content = array_merge(array_combine($keys, $vals), array_combine($keys2, $vals2));
					}
					else $content['form-signature'] = $this->user_signature_form();  // this shouldn't happen

				}
			}

			qa_html_theme_base::main_parts($content);

		}

	// worker functions

		function user_signature_form() {
			// displays signature form in user profile
			
			global $qa_request;
			
			$handle = preg_replace('/^[^\/]+\/([^\/]+).*/',"$1",$qa_request);
			
			$userid = $this->getuserfromhandle($handle);
			
			if(!$userid) return;

			$ok = null;
			
			if (qa_clicked('signature_save')) {
				if(strlen(qa_post_text('signature_text')) > qa_opt('signatures_length')) {
					$error = 'Max possible signature length is 1000 characters';
				}
				else {
					qa_db_query_sub(
						'INSERT INTO ^usersignatures (userid,signature) VALUES (#,$) ON DUPLICATE KEY UPDATE signature=$',
						$userid,qa_post_text('signature_text'),qa_post_text('signature_text')
					);
					$ok = 'Signature Saved.';
				}
			}
			

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
						'tags' => 'NAME="signature_text"',
						'rows' => 8,
						'value' => @$result,
						'type' => 'textarea',
				);
				$buttons[] = array(
						'label' => 'Save',
						'tags' => 'NAME="signature_save"',
				);

				return array(
					
					'title' => '<a name="signature_text">Signature</a>',

					'tags' =>  'action="'.qa_self_html().'#signature_text" method="POST"',

					'style' => 'tall',
					
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

