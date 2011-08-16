<?php
	class qa_signatures_admin {

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
			return ($template!='admin');
		}	   
			
		function admin_form(&$qa_content)
		{					   
							
		// Process form input
			
			$ok = null;
			
			if (qa_clicked('signatures_save')) {
				if(qa_post_text('signatures_length') > 1000) {
					$error = 'Max possible signature length is 1000 characters';
				}
				else {
					if(!qa_opt('signatures_c_enable') && qa_post_text('signatures_enable')) {
						$table_exists = qa_db_read_one_value(qa_db_query_sub("SHOW TABLES LIKE '^usersignature'"),true);
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
			
					
			// Create the form for display
			$formats = array();
			$formats[] = 'plain text';
			
			$editors = qa_list_modules('viewer');
			if(in_array('Markdown Viewer',$editors)) {
			$formats[] = 'markdown';
			}
			
			$formats[]='html';
				
			$fields = array();
			
			$fields[] = array(
				'label' => 'Enable signatures',
				'tags' => 'NAME="signatures_enable"',
				'value' => qa_opt('signatures_enable'),
				'type' => 'checkbox',
			);
			
			$fields[] = array(
				'label' => 'in questions',
				'tags' => 'NAME="signatures_q_enable"',
				'value' => qa_opt('signatures_q_enable'),
				'type' => 'checkbox',
			);
			
			$fields[] = array(
				'label' => 'in answers',
				'tags' => 'NAME="signatures_a_enable"',
				'value' => qa_opt('signatures_a_enable'),
				'type' => 'checkbox',
			);
			
			$fields[] = array(
				'label' => 'in comments',
				'tags' => 'NAME="signatures_c_enable"',
				'value' => qa_opt('signatures_c_enable'),
				'type' => 'checkbox',
			);
			$fields[] = array(
				'label' => 'Signature length (chars)',
				'type' => 'number',
				'value' => qa_opt('signatures_length'),
				'tags' => 'NAME="signatures_length"',
				'note' => 'max possible is 1000 characters',
			);		   
			$fields[] = array(
				'label' => 'Signature format',
				'tags' => 'NAME="signatures_format"',
				'type' => 'select',
				'options' => $formats,
				'value' => $formats[qa_opt('signatures_format')],
			);
				
		  

			return array(		   
				'ok' => ($ok && !isset($error)) ? $ok : null,
					
				'fields' => $fields,
			 
				'buttons' => array(
					array(
						'label' => 'Save',
						'tags' => 'NAME="signatures_save"',
					)
				),
			);
		}
	}

