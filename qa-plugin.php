<?php
        
/*              
        Plugin Name: Signatures
        Plugin URI: https://github.com/NoahY/q2a-signatures
        Plugin Description: Signatures
        Plugin Version: 2.1
        Plugin Date: 2011-08-16
        Plugin Author: NoahY
        Plugin Author URI:                              
        Plugin License: GPLv2                           
        Plugin Minimum Question2Answer Version: 1.3
        Plugin Update Check URI: https://raw.github.com/NoahY/q2a-signatures/master/qa-plugin.php
*/                      
                        
                        
        if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
                        header('Location: ../../');
                        exit;   
        }               

        qa_register_plugin_module('module', 'qa-sig-admin.php', 'qa_signatures_admin', 'Signatures Admin');
        
        qa_register_plugin_layer('qa-sig-layer.php', 'Signature Layer');
                        
                        
/*                              
        Omit PHP closing tag to help avoid accidental output
*/                              
                          

