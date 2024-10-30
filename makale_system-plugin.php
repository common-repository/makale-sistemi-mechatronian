<?php
/*
Plugin Name: Makale Sistemi Plugin
Plugin URI: http://makale.mechatronian.com
Description: Mechatronian.com postlarını Otomatik konu olarak açması için oluşturulmuştur.
Version: 1.0
Author: Mechatronian
Author URI: http://blog.mechatronian.com
*/


/*  Copyright 2015  Mechatronian.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/




if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

define( 'MAKALE_SYSTEM_VERSION', '1.0.0' );
define( 'MAKALE_SYSTEM_RELEASE_DATE', date_i18n( 'F j, Y', '1433422229' ) );
define( 'MAKALE_SYSTEM_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAKALE_SYSTEM_URL', plugin_dir_url( __FILE__ ) );


if (!class_exists("Makale_System")) :
class Makale_System {
	var $settings, $options_page;
	
	function __construct() {	

		if (is_admin()) {
			// Load example settings page
			if (!class_exists("MAKALE_SYSTEM_Settings"))
				require(MAKALE_SYSTEM_DIR . 'makale_system-ex-settings.php');
			$this->settings = new Makale_System_Settings();	
		}
		
		add_action('init', array($this,'init') );
		add_action('admin_init', array($this,'admin_init') );
		add_action('admin_menu', array($this,'admin_menu') );
		add_action( 'wp_ajax_add_post_MAKALESYSTEM', array($this,'addpostbot_callback') );
		add_action( 'wp_ajax_nopriv_add_post_MAKALESYSTEM', array($this,'addpostbot_callback') );

		
		register_activation_hook( __FILE__, array($this,'activate') );
		register_deactivation_hook( __FILE__, array($this,'deactivate') );
	}
	


	function addpostbot_callback(){
		
		$settings=($this->settings->options);
		
		$codeblogu=$settings['codeblogu'];
		$BaslikTemplate = $settings['BaslikTemplate'];
		$icerikTemplate = $settings['icerikTemplate'];
		$tagTemplate = $settings['tagTemplate'];
		$catTemplate = $settings['catTemplate'];
		if(isset($settings["Maktif"])){
			if(!session_id()) {
				session_start();
			}
			$myip="";
			if(isset($_SESSION['myip'])) {
				$myip=$_SESSION['myip'];
			}
			$token = $_REQUEST['token'];
			$ip = $_SERVER['REMOTE_ADDR'];
			//echo $ip=="46.20.9.186";
			if ($token==$settings["MBotToken"] && (($ip=="46.20.9.186") or ($ip==$myip))){
				
				$spin=isset($settings['Spinner']) ? 1 : 0;
				$siteHash=md5($settings['MBotdomainName']);
				$botid=$settings['Mbotid'];
				$url='http://makale.mechatronian.com/GetPost/'.$token.'/'.$siteHash.'/'.$botid.'/'.$spin.'/x/';
				
				$body = wp_remote_retrieve_body( wp_remote_get( $url ) );
				$body=json_decode($body,true);
				$error=$body['error'];
				if (isset($_REQUEST['er'])){
					echo '<center><p>Api Dökümantasyonu hakkında detaylı bilgi için. <a href="http://makale.mechatronian.com/document">TIKLAYINIZ</a></p>';
					switch($error){
						case 1:
							echo '<h1><p style="color:#FF0000 ">Hata: Token Yanlış!</p></h1>';
							break;
						case 2:
							echo '<h1><p style="color:#FF0000 ">Hata: Site ismi yanlış girilmiş!</p></h1></p> Bunu girmeyi deneyin : '.$_SERVER['HTTP_HOST'];
							break;
						case 3:
							echo '<h1><p style="color:#FF0000 ">Hata: Zamanlayıcı id yanlış girilmiş!</p></h1>';
							break;
						case 4:
							echo '<h1><p style="color:#FFA500 ">Zamanlayıcınızda konu kalmadı Yeni konu satın alarak ekleyebilirsiniz!</p></h1>';
							
					}
				}
				if ($error=="0"){
					$isim=urldecode($body['isim']);
					$icerikTemp=json_decode($body['icerik'],true);
					$special=$body['special'];
					$icerik=$this->nl2br2((urldecode($icerikTemp['content'])));
					$tags=json_decode($icerikTemp['tag'],true);
					$categories=$icerikTemp['categories'];
					$cats=array();
					
					eval($codeblogu); //template	
						
					foreach($categories as $cat){
						if (isset($settings['otoAddCat'])){
							if ($cat[0]!=1){ //eğer 1 ise system kategorisidir
								$cat_name = $cat[1];
								eval($catTemplate);
								$append = true ;
								$taxonomy='category';
								$catf  = get_term_by('name', $cat_name , 'category');
								
								if($catf == false){
									if ($cat[2]>0){
										$parent_term_id=get_term_by('name', $categories[$cat[2]][1] , 'category');
									}else{
										$parent_term_id=0;
									}
									$catf = wp_insert_term($cat_name, $taxonomy,array('parent'=> $parent_term_id));
									$cat_id = $catf['term_id'] ;
								}else{
									$cat_id = $catf->term_id ;
								}
								
								$cats[]=$cat_id;
							}else{
								eval($catTemplate);
							}
						}else{
							$cats=array($settings['standarCat']);
							break;
						}
						//$cats[]=;
					}
					
					$taglar='';
					foreach($tags as $tag){
						eval($tagTemplate);
						$taglar.=$tag.",";
					}

					eval($BaslikTemplate);//template
					eval($icerikTemplate);//template
					eval($codeblogu2); //template					
					$my_post = array();
					$my_post['post_title'] = $BaslikSon;
					$my_post['post_content'] = $icerikSon;
					$my_post['post_status'] = $settings['addType'];
					$my_post['post_author'] = $settings['addByWho'];
					
					
					$my_post['post_category'] = $cats;
					
					// Yazıyı veritabanına ekle
					$id=wp_insert_post( $my_post );
					
					if ($taglar!=''){
						wp_set_post_tags( $id, $taglar, true );
					}
					
					if (isset($settings['Featureimage']) && isset($special['coverImage'])){
						$filename = $this->downloadimage($special['coverImage']);
						$attach_id=$this->addAtachment($filename,$id);
						$this->setFeatured($attach_id,$id);
					}
					
					eval($settings['postmeta']);
					//
					if ($id>0){
						if (isset($_REQUEST['er'])){
							echo '<center><h1><p style="color:#008000">Başarılı: Artık Mechatronianlıyabilirsiniz!</p></h1>';
						}
					}
					
					
				}//error not
			}//bot token
		}//function
		
		wp_die(); // this is required to return a proper result
	
	}
	
	function addAtachment($filename,$id){
		$filetype = wp_check_filetype( basename( $filename ), null );
		$wp_upload_dir = wp_upload_dir();
		$attachment = array(
			'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, $filename, $id);
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		return $attach_id;
	}
	
	function setFeatured($attach_id,$id){
		set_post_thumbnail( $id, $attach_id );
	}
	
	function downloadimage($uploadedfile){
		$timeout_seconds = 5;
		
		// download file to temp dir
		$temp_file = download_url( $uploadedfile, $timeout_seconds );
		
		if (!is_wp_error( $temp_file )) {
		
			// array based on $_FILE as seen in PHP file uploads
			$file = array(
				'name' => basename($uploadedfile), // ex: wp-header-logo.png
				'type' => 'image/png',
				'tmp_name' => $temp_file,
				'error' => 0,
				'size' => filesize($temp_file),
			);
		
			$overrides = array(
				// tells WordPress to not look for the POST form
				// fields that would normally be present, default is true,
				// we downloaded the file from a remote server, so there
				// will be no form fields
				'test_form' => false,
		
				// setting this to false lets WordPress allow empty files, not recommended
				'test_size' => true,
		
				// A properly uploaded file will pass this test. 
				// There should be no reason to override this one.
				'test_upload' => true, 
			);
		
			// move the temporary file into the uploads directory
			$results = wp_handle_sideload( $file, $overrides );
		
			if (!empty($results['error'])) {
				// insert any error handling here
			} else {
		
				$filename = $results['file']; // full path to the file
				$local_url = $results['url']; // URL to the file in the uploads dir
				$type = $results['type']; // MIME type of the file
		
				// perform any actions here based in the above results
				return $filename;
			}
		}
	}
	
	function nl2br2($string)
	{
		$string = str_replace(array("\r\n", "\r", "\n"), '', $string);
		return $string;
	}  
	
	function to_utf8( $string ) {
	// From http://w3.org/International/questions/qa-forms-utf-8.html
		if ( preg_match('%^(?:
		[\x09\x0A\x0D\x20-\x7E]            # ASCII
		| [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
		| \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
		| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
		| \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
		| \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
		| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
		| \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
	)*$%xs', $string) ) {
			return $string;
		} else {
			return iconv( 'CP1252', 'UTF-8', $string);
		}
	}

	function network_propagate($pfunction, $networkwide) {
		global $wpdb;

		if (function_exists('is_multisite') && is_multisite()) {
			// check if it is a network activation - if so, run the activation function 
			// for each blog id
			if ($networkwide) {
				$old_blog = $wpdb->blogid;
				// Get all blog ids
				$blogids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
				foreach ($blogids as $blog_id) {
					switch_to_blog($blog_id);
					call_user_func($pfunction, $networkwide);
				}
				switch_to_blog($old_blog);
				return;
			}	
		} 
		call_user_func($pfunction, $networkwide);
	}

	function activate($networkwide) {
		$this->network_propagate(array($this, '_activate'), $networkwide);
	}

	function deactivate($networkwide) {
		$this->network_propagate(array($this, '_deactivate'), $networkwide);
	}

	function _activate() {}

	function _deactivate() {}
	

	function init() {
		load_plugin_textdomain( 'makale_system', MAKALE_SYSTEM_DIR . 'lang', 
							   basename( dirname( __FILE__ ) ) . '/lang' );
	}

	function admin_init() {
	}

	function admin_menu() {
	}

	function print_example($str, $print_info=TRUE) {
		if (!$print_info) return;
		__($str . "<br/><br/>\n", 'makale_system' );
	}


	function javascript_redirect($location) {
		// redirect after header here can't use wp_redirect($location);
		?>
		  <script type="text/javascript">
		  <!--
		  window.location= <?php echo "'" . $location . "'"; ?>;
		  //-->
		  </script>
		<?php
		exit;
	}

} // end class
endif;

// Initialize our plugin object.
global $makale_system;
if (class_exists("makale_system") && !$makale_system) {
    $makale_system = new Makale_System();	
}	
?>