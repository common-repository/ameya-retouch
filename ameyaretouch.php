<?php
/*
Plugin Name: Ameya Retouch
Plugin URI: https://soft.candychip.net/?Ameya+Retouch
Description: Edit and post images. 
Version: 0.3
Author: Ameya
Author URI: https://soft.candychip.net/
License: MIT
*/

class AmeyaRetouch {
	const DIC = 'ameyaretouch';
	const PAGE = 'ameyaretouch';
	const OPTION = 'ameyaretouch_option';
	const SECTION = 'ameyaretouch_section';
	const GROUP = 'ameyaretouch_group';
	function __construct() {
		add_action('admin_menu', array($this, 'add_pages'));
		add_action('admin_init', array($this, 'add_settings'));
		add_action('plugins_loaded', array($this,'plugin_loaded'));
		add_action('admin_enqueue_scripts', array($this,'admin_enqueue'));
	}
	function admin_enqueue($hook_suffix) {
		if ($hook_suffix == 'media_page_ameyaretouch') {
			wp_enqueue_script('ameyaretouch_js', plugins_url( 'js/AmeyaRetouch.js', __FILE__ ), array('jquery'), '1.0');
			$v = get_option(self::OPTION);
			$prep = array();
			for ($i=0;$i<2;$i++) {
				array_push($prep,$this->get_preparation($v[$i]));
			}
			$script = "
jQuery(document).ready(function(){
	jQuery('.popup').click(function(){
		const n = jQuery(this).data('index');
		let prep = ".json_encode($prep).";
		AmeyaRetouch.prepare(prep[n]);
		AmeyaRetouch.saveSuccess = function(){alert('".__('Upload successful!',self::DIC)."');};
		AmeyaRetouch.saveFailure = function(){alert('".__('Upload failed!',self::DIC)."');};
	});
});";
			wp_add_inline_script('ameyaretouch_js',$script);
			wp_enqueue_style('ameyaretouch_css', plugins_url( 'css/ameyaretouch.css', __FILE__), array(), '1.0');
		}
	}
	function add_pages() {
		add_submenu_page( 'upload.php', __('Ameya Retouch',self::DIC).' - '.__('Retouch image',self::DIC), __('Retouch',self::DIC), 'upload_files', self::PAGE, array($this,'option_page'), null );
	}
	function add_settings() {
		add_settings_section( self::SECTION, __('Retouch Settings',self::DIC), array($this,'settings_section'), self::PAGE);
		for ($i=0;$i<2;$i++) {
			add_settings_field( 'opt-'.$i.'-name', __('Button Name',self::DIC).' #'.($i+1), array($this,'field'), self::PAGE, self::SECTION, array($i,'name','label_for'=> 'opt-'.$i.'-name') );
			add_settings_field( 'opt-'.$i.'-size_c', __('Fixed Size',self::DIC), array($this,'size_field'), self::PAGE, self::SECTION, array($i,'size','label_for'=> 'opt-'.$i.'-size_c') );
			add_settings_field( 'opt-'.$i.'-rotate', __('Rotate',self::DIC), array($this,'check_field'), self::PAGE, self::SECTION, array($i,'rotate','label_for'=> 'opt-'.$i.'-rotate') );
			add_settings_field( 'opt-'.$i.'-blur', __('Blur',self::DIC), array($this,'check_field'), self::PAGE, self::SECTION, array($i,'blur','label_for'=> 'opt-'.$i.'-blur') );
			add_settings_field( 'opt-'.$i.'-temper', __('Color temperature',self::DIC), array($this,'check_field'), self::PAGE, self::SECTION, array($i,'temper','label_for'=> 'opt-'.$i.'-temper') );
		}
		register_setting(
			self::GROUP, self::OPTION,
			array(
				'type' => 'object',
				'sanitize_callback' => array($this,'check'),
				'default' => array(
					array('name' => 'Button #1', 'size' => array( 'c' => 1, 'w' => 640, 'h' => 400), 'rotate' => 1, 'blur' => 1 , 'temper' => 1 ),
					array('name' => 'Button #2', 'size' => array( 'c' => 1, 'w' => 400, 'h' => 640), 'rotate' => 1, 'blur' => 1 , 'temper' => 1 )
				)
			)
		);
	}
	function plugin_loaded() {
		load_plugin_textdomain( self::DIC, false, dirname( plugin_basename( __FILE__ ) ).'/languages' );
	}
	function option_page() {
		if ($_POST['filename']) {
			$attachment_id = media_handle_upload( 'image', 0 );
			return;
		}
		$v = get_option(self::OPTION);
	?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<h2><?php _e('Start retouch',self::DIC); ?></h2>
	<div>
		<button class="popup" data-index="0"><?php echo esc_html($v[0]['name']); ?></button>
		<button class="popup" data-index="1"><?php echo esc_html($v[1]['name']); ?></button>
	</div>
	<hr>
	<?php settings_errors(); ?>
	<form action="options.php" method="post">
	<?php
		settings_fields( self::GROUP );
		do_settings_sections( self::PAGE );
		submit_button( __( 'Save Settings', self::DIC ) );
	?>
	</form>
</div>
	<?php
	}
	function field($args) {
		$v = get_option(self::OPTION);
		echo '<input type="text" name="'.esc_attr(self::OPTION.'['.$args[0].']['.$args[1].']').'" id="'.esc_attr('opt-'.$args[0].'-'.$args[1]).'" class="'.esc_attr('opt-'.$args[1]).'" value="'.esc_attr(esc_attr($v[$args[0]][$args[1]])).'">';
	}
	function size_field($args) {
		$v = get_option(self::OPTION);
		echo '<input type="checkbox" name="'.esc_attr(self::OPTION.'['.$args[0].']['.$args[1].'][c]').'" id="'.esc_attr('opt-'.$args[0].'-'.$args[1].'_c').'" class="'.esc_attr('opt-'.$args[1]).'" value="1"'.($v[$args[0]][$args[1]]['c'] ? ' checked' : '' ).'> ';
		echo '( '.esc_html(__('Width',self::DIC)).': <input type="number" name="'.esc_attr(self::OPTION.'['.$args[0].']['.$args[1].'][w]').'" id="'.esc_attr('opt-'.$args[0].'-'.$args[1].'_w').'" class="'.esc_attr('opt-'.$args[1]).'" value="'.esc_attr($v[$args[0]][$args[1]]['w']).'"> px, ';
		echo esc_html(__('Height',self::DIC)).': <input type="number" name="'.esc_attr(self::OPTION.'['.$args[0].']['.$args[1].'][h]').'" id="'.esc_attr('opt-'.$args[0].'-'.$args[1].'_h').'" class="'.esc_attr('opt-'.$args[1]).'" value="'.esc_attr($v[$args[0]][$args[1]]['h']).'"> px )';
	}
	function check_field($args) {
		$v = get_option(self::OPTION);
		echo '<input type="checkbox" name="'.esc_attr(self::OPTION.'['.$args[0].']['.$args[1].']').'" id="'.esc_attr('opt-'.$args[0].'-'.$args[1]).'" class="'.esc_attr('opt-'.$args[1]).'" value="1"'.($v[$args[0]][$args[1]] ? ' checked' : '' ).'> ';
	}
	function check($input) {
		for ($i=0;$i<2;$i++) {
			if ($input[$i]['name'] == '') $input[$i]['name'] = 'Button #'.($i+1);
			$input[$i]['size']['c'] = ($input[$i]['size']['c'] ? 1 : 0);
			if ($input[$i]['size']['c'] && $input[$i]['size']['w'] == '') $input[$i]['size']['w'] = 640;
			if ($input[$i]['size']['c'] && $input[$i]['size']['h'] == '') $input[$i]['size']['h'] = 400;
			$input[$i]['rotate'] = ($input[$i]['rotate'] ? 1 : 0);
			$input[$i]['blur'] = ($input[$i]['blur'] ? 1 : 0);
			$input[$i]['temper'] = ($input[$i]['temper'] ? 1 : 0);
		}
		return $input;
	}
	function settings_section() {
		echo '<p>'.esc_html(__('You can prepare two types of settings for the retouch function.',self::DIC)).'</p>';
	}
	function get_preparation($v) {
		$ret = array(
			'crop' => false,
			'button' => array(
				array( 'command' => 'save', 'title' => esc_js(__('Upload',self::DIC)), 'action' => '?page=ameyaretouch' ),
			)
		);
		if ($v['size']['c']) {
			$ret['sizefix'] = true;
			$ret['sizefixw'] = esc_js($v['size']['w']);
			$ret['sizefixh'] = esc_js($v['size']['h']);
		}
		if ($v['rotate']) {
			$ret['button'][] = array( 'command' => 'rotate', 'title' => esc_js(__('Rotate R',self::DIC)), 'deg' => 90 );
			$ret['button'][] = array( 'command' => 'rotate', 'title' => esc_js(__('Rotate L',self::DIC)), 'deg' => -90 );
		}
		if ($v['size']['c']) {
			$ret['button'][] = array( 'command' => 'sizefix', 'title' => esc_js($v['size']['w']."x".$v['size']['h']), 'width' => intval($v['size']['w']), 'height' => intval($v['size']['h']) );
			$ret['button'][] = array( 'command' => 'sizefix', 'title' => esc_js($v['size']['h']."x".$v['size']['w']), 'width' => intval($v['size']['h']), 'height' => intval($v['size']['w']) );
		} else {
			if (intval($v['size']['w'])>0) $ret['button'][] = array( 'command' => 'scale', 'title' => esc_js('W'.$v['size']['w']), 'dir' => 'w', 'size' => intval($v['size']['w']) );
			if (intval($v['size']['h'])>0) $ret['button'][] = array( 'command' => 'scale', 'title' => esc_js('H'.$v['size']['h']), 'dir' => 'h', 'size' => intval($v['size']['h']) );
		}
		if ($v['blur']) $ret['button'][] = array( 'command' => 'blur', 'title' => esc_js(__('Blur',self::DIC)), 'width' => 25, 'opacity' => 0.1, 'power' => 10 );
		if ($v['temper']) $ret['button'][] = array( 'command' => 'temperature', 'title' => esc_js(__('Temperature',self::DIC)) );
		$ret['button'][] = array( 'command' => 'undo', 'title' => esc_js(__('Undo',self::DIC)) );
		$ret['button'][] = array( 'command' => 'close', 'title' => esc_js(__('Close',self::DIC)) );
		return $ret;
	}
	function debug($str) {
		$f = fopen(plugin_dir_path( __FILE__ ).'debug.txt','a');
		fwrite($f,$str."\n");
		fclose($f);
	}
}
$ameyaretouch = new AmeyaRetouch;
?>
