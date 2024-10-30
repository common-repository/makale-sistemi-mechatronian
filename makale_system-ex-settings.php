<?php
if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists("Makale_System_Settings")) :


class Makale_System_Settings {

	public static $default_settings = 
		array( 	
				'MBotToken' => 'Token giriniz',
				'MBotdomainName' => 'blog.deneme.com',
				'Mbotid' => 'bot id',
				'Msiteid' => '',
				'Maktif' => 'checked',
				'Spinner' => '',
				'postmeta' => 'add_post_meta( $id, \'meta-etiketim\', \'meta-degeri\' );
				//add_post_meta( $id, \'meta-etiketim\', \'meta-degeri\' );',
				'codeblogu' => '// burada php kodu kullanabilirsiniz $BaslikSon ve $icerikSon gibi değişkenlere erişebilirsiniz ve php fonksiyonları kullanabilirsiniz.',
				'codeblogu2' => '$taglar=$taglar.\',mekatronik,mechatronic\';// burada php kodu kullanabilirsiniz $taglar.',
				'BaslikTemplate' => '$BaslikSon=$isim.\' - Mechatronian.com\';',
				'icerikTemplate' => '$icerikSon=$icerik;',
				'tagTemplate' => '$tag=$tag;',
				'catTemplate' => '$cat=$cat;',
				'Featureimage' => 'checked',
				'addType' => 'publish',
				'addByWho' => '',
				'otoAddCat' => 'checked',
				'standarCat' => '1'
				);
	var $pagehook, $page_id, $settings_field, $options;

	
	function __construct() {	
		$this->page_id = 'makale_system';
		// This is the get_options slug used in the database to store our plugin option values.
		$this->settings_field = 'makale_system_options';
		$this->options = get_option( $this->settings_field );

		add_action('admin_init', array($this,'admin_init'), 20 );
		add_action( 'admin_menu', array($this, 'admin_menu'), 20);
	}
	
	function admin_menu() {
		if ( ! current_user_can('update_plugins') )
			return;
	
		// Add a new submenu to the standard Settings panel
		$this->pagehook = $page =  add_options_page(	
			__('Makale Sistemi', 'makale_system'), __('Makale Sistemi', 'makale_system'), 
			'administrator', $this->page_id, array($this,'render') );
		
		// Executed on-load. Add all metaboxes.
		add_action( 'load-' . $this->pagehook, array( $this, 'metaboxes' ) );

		// Include js, css, or header *only* for our settings page
		add_action("admin_print_scripts-$page", array($this, 'js_includes'));
//		add_action("admin_print_styles-$page", array($this, 'css_includes'));
		add_action("admin_head-$page", array($this, 'admin_head') );
	}
	
	function admin_head() { 
		echo '
		<style>
		
		</style>';
	}
	
	function admin_init() {
		register_setting( $this->settings_field, $this->settings_field, array($this, 'sanitize_theme_options') );
		add_option( $this->settings_field, Makale_System_Settings::$default_settings );
		
	}


	function js_includes() {
		wp_enqueue_script( 'postbox' );
	}


	/*
		Sanitize our plugin settings array as needed.
	*/	
	function sanitize_theme_options($options) {
		$options['example_text'] = stripcslashes($options['example_text']);
		return $options;
	}


	/*
		Settings access functions.
		
	*/
	protected function get_field_name( $name ) {

		return sprintf( '%s[%s]', $this->settings_field, $name );

	}

	protected function get_field_id( $id ) {

		return sprintf( '%s[%s]', $this->settings_field, $id );

	}

	protected function get_field_value( $key ) {

		return $this->options[$key];

	}
		

	/*
		Render settings page.
		
	*/
	
	function render() {
		global $wp_meta_boxes;
		if(!session_id()) {
				session_start();
		}
		$_SESSION['myip']=$_SERVER['REMOTE_ADDR'];
		
		$title = __('Makale Sistemi', 'makale_system');
		?>
		<div class="wrap">   
			<h2><?php echo esc_html( $title ); ?></h2>
		
			<form method="post" action="options.php">
				<p>
				<input type="submit" class="button button-primary" name="save_options" value="<?php esc_attr_e('Ayarları Kaydet'); ?>" />
				</p>
                
                <div class="metabox-holder">
                    <div class="postbox-container" style="width: 99%;">
                    <?php 
						// Render metaboxes
                        settings_fields($this->settings_field); 
                        do_meta_boxes( $this->pagehook, 'main', null );
                      	if ( isset( $wp_meta_boxes[$this->pagehook]['column2'] ) )
 							do_meta_boxes( $this->pagehook, 'column2', null );
                    ?>
                    </div>
                </div>

				<p>
				<input type="submit" class="button button-primary" name="save_options" value="<?php esc_attr_e('Ayarları Kaydet'); ?>" />
				</p>
			</form>
		</div>
        
        <!-- Needed to allow metabox layout and close functionality. -->
		<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready( function ($) {
				// close postboxes that should be closed
				$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
				// postboxes setup
				postboxes.add_postbox_toggles('<?php echo $this->pagehook; ?>');
			});
			//]]>
		</script>
	<?php }
	
	
	function metaboxes() {
		//plugin tarihini göster
		add_meta_box( 'makale_system-version', __( 'Bilgilendirme', 'makale_system' ), array( $this, 'info_box' ), $this->pagehook, 'main', 'high' );

		//Bot aktif
		add_meta_box( 'makale_system-conditions', __( 'Sistem Aktif', 'makale_system' ), array( $this, 'condition_box' ), $this->pagehook, 'main' );

		// 
		add_meta_box( 	'makale_system-all', 
						__( 'Sistem Genel Ayarları', 'makale_system' ), 
						array( $this, 'do_settings_box' ), $this->pagehook, 'main' );
						
		add_meta_box( 	'makale_system-post', 
						__( 'Sistem Post Ayarları', 'makale_system' ), 
						array( $this, 'post_settings_box' ), $this->pagehook, 'main' );	
						
		add_meta_box( 	'makale_system-advanced', 
						__( 'Gelişmiş Ayarlar', 'makale_system' ), 
						array( $this, 'advanced_box' ), $this->pagehook, 'main' );				

	}

	function info_box() {
		echo '<p><strong>'._e( 'Version:', 'makale_system' ).'</strong>'. MAKALE_SYSTEM_VERSION.'&middot;</p>';
		
		echo '<p><strong>'._e( 'Tarih:', 'makale_system' ).'</strong>'.MAKALE_SYSTEM_RELEASE_DATE.'</p>';
		
		echo '</p><h3>Sistem Linki: '. admin_url( 'admin-ajax.php?action=add_post_MAKALESYSTEM' ).'</h3>';
		echo '</p><h3> Bu link ile Mechatronian.com dan site oluşturunuz.</h3></p>';
		echo '<a href="'.admin_url( 'admin-ajax.php?action=add_post_MAKALESYSTEM&er=1&token='.$this->get_field_value( 'MBotToken' ) ).'" target="_blank"><h1>Şimdi Test Yap</h1></a>';
	}
	
	function condition_box() {
		$state=(isset($this->options['Maktif']) ? 'checked' : '' );
		echo '<p><input type="checkbox" name="'.$this->get_field_name( 'Maktif' ).'" id="'.$this->get_field_id( 'mbox_example_checkbox1' ).'" value="1" '.$state.'/> 
			<label for="'.$this->get_field_id( 'Maktif' ).'">'._e( 'Sistem Aktif', 'makale_system' ).'</label><br/></p>';
		
	}


	function do_settings_box() {
		$state=(isset($this->options['Spinner']) ? 'checked' : '' );
		
		echo '<p>Api ayarlarınızı buraya giriniz detaylı bilgi için. <a href="http://makale.mechatronian.com/document">TIKLAYINIZ</a></p>';
		?>
        Sitenizin Tokeni</p><input id="token" style="width:50%;"  type="text" name="<?php echo $this->get_field_name( 'MBotToken' ); ?>" placeholder="TRXg9qqysWSnLrA2zesx1J7nqFoPCEfa" value="<?php echo esc_attr( $this->get_field_value( 'MBotToken' ) ); ?>" /> Sitenizin tokenını kimse ile paylaşmayın aksi takdirde sorunlar ortaya çıkailir.</p>	
        Sitenizin İsmi</p><input id="tokenname" style="width:50%;"  type="text" name="<?php echo $this->get_field_name( 'MBotdomainName' ); ?>" placeholder="mechatronian.com" value="<?php echo esc_attr( $this->get_field_value( 'MBotdomainName' ) ); ?>" /></p>	
        Zamanlayıcı İd</p><input id="Mbotid" style="width:50%;"  type="text" name="<?php echo $this->get_field_name( 'Mbotid' ); ?>" placeholder="12" value="<?php echo esc_attr( $this->get_field_value( 'Mbotid' ) ); ?>" /></p>	
		<?php 
		echo 'Makale Özgünleştirici(spinner) Aktif <input type="checkbox" name="'.$this->get_field_name( 'Spinner' ).'" id="'.$this->get_field_id( 'Spinner' ).'"  '.$state.'/></p>';
	}
	
	function post_settings_box() {
		$state=(isset($this->options['Featureimage']) ? 'checked' : '' );
		$state1=(isset($this->options['otoAddCat']) ? 'checked' : '' );
		echo '<p>Detaylı bilgi için. <a href="http://makale.mechatronian.com/document">TIKLAYINIZ</a></p>';
		?>
		Başlık Tipi </p><input style="width:50%;"  type="text" name="<?php echo $this->get_field_name( 'BaslikTemplate' ); ?>" placeholder="<?php echo Makale_System_Settings::$default_settings['BaslikTemplate'] ;?>" value="<?php echo esc_attr( $this->get_field_value( 'BaslikTemplate' ) ); ?>" /> Bu alan yeni eklenecek olan başlığın nasıl olacağını belirler.</p>
		
		Etiketler Tipi </p><input style="width:50%;"  type="text" name="<?php echo $this->get_field_name( 'tagTemplate' ); ?>" placeholder="<?php echo Makale_System_Settings::$default_settings['tagTemplate'] ;?>" value="<?php echo esc_attr( $this->get_field_value( 'tagTemplate' ) ); ?>" /></p>
		
		Kategori Tipi </p><input style="width:50%;"  type="text" name="<?php echo $this->get_field_name( 'catTemplate' ); ?>" placeholder="<?php echo Makale_System_Settings::$default_settings['catTemplate'] ;?>" value="<?php echo esc_attr( $this->get_field_value( 'catTemplate' ) ); ?>" /></p>
		
		içerik Tipi</p><textarea placeholder="<?php echo Makale_System_Settings::$default_settings['icerikTemplate'] ;?>" style="width:50%" name="<?php echo $this->get_field_name( 'icerikTemplate' ); ?>"><?php echo esc_attr( $this->get_field_value( 'icerikTemplate' ) ); ?></textarea>Bu alan içerisinde html tagı kullanabilirsiniz.</p>
		
		Eklenme Tipi </p><input style="width:50%;"  type="text" name="<?php echo $this->get_field_name( 'addType' ); ?>" placeholder="<?php echo Makale_System_Settings::$default_settings['addType'] ;?>" value="<?php echo esc_attr( $this->get_field_value( 'addType' ) ); ?>" />Direk yayınlanması için: <b>publish</b> Taslağa eklemek için: <b>draft</b> yazınız.</p>
		
		Ekleyen Kişi ID</p><input style="width:50%;"  type="text" name="<?php echo $this->get_field_name( 'addByWho' ); ?>" placeholder="<?php echo get_current_user_id(); ?>" value="<?php echo  $this->get_field_value( 'addByWho' ) || get_current_user_id(); ?>" />Konuları kimin eklediğini ayarlar detaylı bilgi için dökümantasyonu inceleyiniz.</p>
		<?php
		echo 'Postların Ekleneceği Kategori:';
		wp_dropdown_categories( 'hide_empty=0&show_count=1&name='.$this->get_field_name( 'standarCat' ).'&selected='.$this->get_field_value( 'standarCat' ) ); 
		echo '</p>';
		echo 'Kategorileri Otomatik Ekle <input type="checkbox" name="'.$this->get_field_name( 'otoAddCat' ).'" id="'.$this->get_field_id( 'otoAddCat' ).'"  '.$state1.'/>Eğer bu seçeneği seçerseniz yukardaki iptal olur. </p>';
		
		echo 'Kapak Fotoğraflarını ekle <input type="checkbox" name="'.$this->get_field_name( 'Featureimage' ).'" id="'.$this->get_field_id( 'Featureimage' ).'"  '.$state.'/> Feature İmageleri Yerine Yerleştirir.</p>';		
	}
	
	function advanced_box() {
		echo '<p>Detaylı bilgi için. <a href="http://makale.mechatronian.com/document">TIKLAYINIZ</a></p>';
		?>
		Meta Tag Ekle</p><textarea placeholder="<?php echo Makale_System_Settings::$default_settings['postmeta'] ;?>" style="width:50%" name="<?php echo $this->get_field_name( 'postmeta' ); ?>"><?php echo esc_attr( $this->get_field_value( 'postmeta' ) ); ?></textarea></p>
		
		Eklentiye Kod İnjecte1 (PHP)</p><textarea placeholder="<?php echo Makale_System_Settings::$default_settings['codeblogu'] ;?>" style="width:50%" name="<?php echo $this->get_field_name( 'codeblogu' ); ?>"><?php echo esc_attr( $this->get_field_value( 'codeblogu' ) ); ?></textarea></p>
		
		Eklentiye Kod İnjecte2 (PHP)</p><textarea placeholder="<?php echo Makale_System_Settings::$default_settings['codeblogu2'] ;?>" style="width:50%" name="<?php echo $this->get_field_name( 'codeblogu2' ); ?>"><?php echo esc_attr( $this->get_field_value( 'codeblogu2' ) ); ?></textarea></p>
		<?php 	
	}

	
	

} // end class
endif;
?>