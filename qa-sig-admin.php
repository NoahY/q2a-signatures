<?php
	class qa_signatures_admin {

		function option_default($option) {
			
			switch($option) {
				case 'signatures_length':
					return 1000;
					break;
				case 'signatures_header':
					return '<br>';
					break;
				case 'signatures_footer':
					return '';
					break;
				default:
					return false;				
					break;
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
					if(!qa_opt('signatures_enable') && qa_post_text('signatures_enable')) {
						$table_exists = qa_db_read_one_value(qa_db_query_sub("SHOW TABLES LIKE '^usersignatures'"),true);
						if(!$table_exists) {
							qa_db_query_sub(
							'CREATE TABLE ^usersignatures ('.
								'userid INT(11) NOT NULL,'.
								'signature VARCHAR (1000) DEFAULT \'\','.
								'id INT(11) NOT NULL AUTO_INCREMENT,'.
								'UNIQUE (userid),'.
								'PRIMARY KEY (id)'.
							') ENGINE=MyISAM DEFAULT CHARSET=utf8'
							);			
						}
					}
					qa_opt('signatures_enable',(bool)qa_post_text('signatures_enable'));
					qa_opt('signatures_q_enable',(bool)qa_post_text('signatures_q_enable'));
					qa_opt('signatures_a_enable',(bool)qa_post_text('signatures_a_enable'));
					qa_opt('signatures_c_enable',(bool)qa_post_text('signatures_c_enable'));
					qa_opt('signatures_length',(int)qa_post_text('signatures_length'));
					qa_opt('signatures_format',(int)qa_post_text('signatures_format'));
					qa_opt('signatures_header',qa_post_text('signatures_header'));
					qa_opt('signatures_footer',qa_post_text('signatures_footer'));
					$ok = 'Settings Saved.';
				}
			}
			
					
			// Create the form for display
			
			$formats = qa_list_modules('editor');
			
			foreach ($formats as $key => $format) 
				if(!strlen($format)) 
					$formats[$key] = qa_lang_html('admin/basic_editor');		
			
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
				'value' => (int)qa_opt('signatures_length'),
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
			$fields[] = array(
				'label' => 'Signature header',
				'type' => 'text',
				'value' => qa_opt('signatures_header'),
				'tags' => 'NAME="signatures_header"',
			);		   
				
			$fields[] = array(
				'label' => 'Signature footer',
				'type' => 'text',
				'value' => qa_opt('signatures_footer'),
				'tags' => 'NAME="signatures_footer"',
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

