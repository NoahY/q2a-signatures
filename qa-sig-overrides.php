<?php
		
	function qa_get_permit_options() {
		$permits = qa_get_permit_options_base();
		$permits[] = 'signature_allow';
		$permits[] = 'signature_edit_allow';
		return $permits;
	}

	function qa_get_request_content() {
		$qa_content = qa_get_request_content_base();
		
		// displays signature form in user profile
		
		$qa_request=strtolower(qa_request());
		$reqs = explode('/',$qa_request);
		
		if($reqs[0] == 'user') {
			$qa_content['user_signature_form'] = array();
			
			$userid = $qa_content['raw']['userid'];
			if(!$userid) return $qa_content;
			
			$handles = qa_userids_to_handles(array($userid));
			$handle = $handles[$userid];

			if((qa_get_logged_in_handle() == $handle && !qa_user_permit_error('signature_allow')) || !qa_user_permit_error('signature_edit_allow')) {

				$ok = null;
				
				$formats = qa_list_modules('editor');
				$format = qa_opt('signatures_format');
				$editorname = $formats[$format];
				$editor=qa_load_editor('', $format, $editorname);

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
						'SELECT BINARY signature AS signature,format FROM ^usersignatures WHERE userid=#',
						$userid
					),
					true
				);
				$field=qa_editor_load_field($editor, $qa_content, $content['signature'], $content['format'], 'signature_text', 12, false);
				$field['label']=qa_lang_html('signature_plugin/signature');
				
				$fields['content'] = $field;
				
				if((!$editorname || $editorname == 'Markdown Editor')) $fields['elCount'] = array(
					'label' => '<div id="elCount">'.qa_opt('signatures_length').'</div>',
					'type' => 'static',
				);

				$form=array(
				
					'ok' => ($ok && !isset($error)) ? $ok : null,
					
					'error' => @$error,
					
					'style' => 'tall',
					
					'title' => '<a name="signature_text"></a>'.qa_lang_html('signature_plugin/signature'),

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
				$qa_content['user_signature_form'] = $form;
			}
			else if(qa_opt('signatures_profile_enable')) {
				$content = qa_db_read_one_assoc(
					qa_db_query_sub(
						'SELECT BINARY signature as signature, format FROM ^usersignatures WHERE userid=#',
						$userid
					),
					true
				);

				if(!$content) return $qa_content;

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

				$qa_content['user_signature_form'] = array(
					'title' => 'Signature',
					'fields' => $fields,
					'style' => 'tall'
				);
			}
		}
		return $qa_content;
	}
						
/*							  
		Omit PHP closing tag to help avoid accidental output
*/							  
						  

