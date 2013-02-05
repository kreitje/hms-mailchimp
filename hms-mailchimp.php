<?php
/*
Plugin Name: MailChimp Importer
Plugin URI: http://hitmyserver.com/lp/mailchimp-importer/
Description: Add your MailChimp newsletters to your WordPress blog.
Version: 1.0
Author: HitMyServer LLC
Author URI: http://hitmyserver.com
License: License: GPLv2
*/

require_once plugin_dir_path(__FILE__) . 'mcapi/MCAPI.class.php';

add_filter('cron_schedules', 'hms_cron_schedules');

add_action('admin_menu', 'hms_mc_page');
add_action('admin_init', 'hms_mc_init');
add_action('hms_mc_cron_hook', 'hms_mc_cron');
add_shortcode('mailchimp', 'hms_mc_shortcode');

/*
* Remove cron when plugin is deactivated
**/
register_deactivation_hook(__FILE__, 'hms_mc_deactivate');


function hms_mc_page() { HMS_MC_Importer::getInstance()->add_menu_page(); }
function hms_mc_init() {
	$options = get_option('hms_mc_importer');

	HMS_MC_Importer::getInstance(); 

	/*
 		Make sure our cron isn't scheduled before registering it
 	**/
	if (!wp_next_scheduled('hms_mc_cron_hook') && ($options['cron'] != 'HMSMCNONE'))
		wp_schedule_event(time(), 'hourly', 'hms_mc_cron_hook');
}



class HMS_MC_Importer {

	public $options;
	public $lists = array();
	public static $instance;

	public function __construct() {

		$this->options = get_option('hms_mc_importer');
		$this->register_settings();

	}

	public static function getInstance() {


		if (!isset(self::$instance))
			self::$instance = new HMS_MC_Importer();

		return self::$instance;
	}

	public function add_menu_page() {
		$n = HMS_MC_Importer::getInstance();
		add_options_page('MailChimp Importer', 'MailChimp Importer', 'administrator', __FILE__, array($n, 'display_options_page'));
	}

	public function display_options_page() {
		?>

		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>MailChimp Importer</h2>

			<p>All campaigns will have the plain text version imported.</p>

			<div style="float:left;width:49%;">
				<form method="post" action="options.php">
					<?php settings_fields('hms_mc_importer'); ?>
					<?php do_settings_sections(__FILE__); ?>

					<p class="submit">
						<input name="submit" type="submit" class="button-primary" value="Save Changes" />
					</p>
				</form>
				<br /><br />

				<table class="wide widefat">
					<tr>
						<th>List Name</th>
						<th>List ID</th>
					</tr>

					<?php foreach($this->lists as $id => $n) { ?>
						<tr>
							<td><?php echo $n; ?></td>
							<td><?php echo $id; ?></td>
						</tr>
					<?php } ?>
				</table>
	
			</div>
			<div style="float:right;width:49%;">
				<div align="center">
					<h3>Don't have a MailChimp account?</h3>
					<a href="http://eepurl.com/uilIn">
						<img src="<?php echo plugin_dir_url(__FILE__); ?>mcapi/mclogo.png" width="200" /><br />
						Get One Now
					</a>
				</div>
			</div>
		</div>

		<?php
	}

	public function register_settings() {
		register_setting('hms_mc_importer', 'hms_mc_importer', array($this, 'validate'));
		add_settings_section('hms_mc_main', 'Settings', array($this, 'hms_mc_main_section_cb'), __FILE__);
		add_settings_field('hms_mc_apikey', 'API Key:', array($this, 'hms_mc_apikey_setting'), __FILE__, 'hms_mc_main');
		add_settings_field('hms_mc_list', 'List:', array($this, 'hms_mc_apilist_setting'), __FILE__, 'hms_mc_main');
		add_settings_field('hms_mc_author', 'Author:', array($this, 'hms_mc_author_setting'), __FILE__, 'hms_mc_main');
		add_settings_field('hms_mc_category', 'Categories:', array($this, 'hms_mc_categories_setting'), __FILE__, 'hms_mc_main');
		add_settings_field('hms_mc_cron', 'Interval To Check:', array($this, 'hms_mc_cron_setting'), __FILE__, 'hms_mc_main');
	}

	public function validate($input) {

		if ($input['cron'] != 'HMSMCNONE') {

			if ($this->options['cron'] !== $input['cron']) {
				$time = wp_next_scheduled('hms_mc_cron_hook');
				wp_unschedule_event($time, 'hms_mc_cron_hook');

				wp_schedule_event(time(), $input['cron'], 'hms_mc_cron_hook');
			}
		} else {
			$time = wp_next_scheduled('hms_mc_cron_hook');
			wp_unschedule_event($time, 'hms_mc_cron_hook');			
		}


		return $input;


	}

	public function hms_mc_main_section_cb(){}

	public function hms_mc_apikey_setting() {
		echo '<input type="text" name="hms_mc_importer[apikey]" value="'.$this->options['apikey'].'" />';
	}

	public function hms_mc_apilist_setting() {
		$lists = array();



		if ($this->options['apikey'] != '') {

			$api = new MCAPI($this->options['apikey']);
			$retval = $api->lists();

			if ($retval !== false) {
				foreach ($retval['data'] as $list)
					$lists[$list['id']] = $list['name'];

				$this->lists = $lists;

			} else {
				echo 'Could not load lists from MailChimp. Please verify your api key is correct.';
				return;
			}
		} else {
			echo 'Please enter your MailChimp api key.'; return;
		}

		echo '<select name="hms_mc_importer[apilist]">';
		if (count($lists)> 0) {
			foreach($lists as $id => $name) {
				echo '<option value="' . $id . '"'. (($this->options['apilist'] == $id) ? 'selected="selected"' : '') .'>' . $name . '</option>';
			}
		}
		echo '</select>';
	}

	public function hms_mc_author_setting() {

		$authors = get_users();

		echo '<select name="hms_mc_importer[author]">';

		foreach($authors as $a) {

			echo '<option value="' . $a->ID . '"' . (($this->options['author'] == $a->ID) ? ' selected="selected"' : '') . '>' . $a->user_login . ' ( ' . $a->display_name .' )</option>';
		}

		echo '</select>';
	}

	public function hms_mc_categories_setting() {

		$categories = get_categories( array('type' => 'post', 'hide_empty' => 0, 'taxonomy' => 'category') );

		foreach($categories as $c) {
			echo '<input type="checkbox" id="cat-' . $c->term_id. '" name="hms_mc_importer[categories][]" value="'.$c->term_id.'"';
			if (is_array($this->options['categories']) && in_array($c->term_id, $this->options['categories']))
				echo ' checked="checked"';

			echo ' /> &nbsp;&nbsp; <label for="cat-' . $c->term_id . '">'. $c->name.'</label><br />';
		}

	}

	public function hms_mc_cron_setting() {

		$gs = wp_get_schedules();

		$times = array();

		foreach($gs as $k => $v)
			$times[$k] = $v['display'];


		echo '<select name="hms_mc_importer[cron]">';
		echo '<option value="HMSMCNONE"' . ((($this->options['cron'] == 'HMSMCNONE') || ($this->options['cron']=='')) ? ' selected="selected"' : '') . '>- Do Not Import -</option>';
		foreach($times as $i => $v) {
			echo '<option value="' . $i . '"' . (($i == $this->options['cron']) ? ' selected="selected"' : '') .'>' . $v . '</option>';
		}


		echo '</select>';

	}
}



function hms_mc_cron() {
	$option = get_option('hms_mc_importer');

	if ($option['apikey'] == '' || $option['apilist'] == '') return true;

	$api = new MCAPI($option['apikey']);

	$campaigns = $api->campaigns(
		array(
			'list_id' => $option['apilist'],
			'status' => 'sent',
			'exact' => true
		)
	);



	if (isset($campaigns['data']) && (count($campaigns['data'])>0)) {

		foreach($campaigns['data'] as $c) {
			/**
			 * Check and see if it's already in the database
			 **/
			$mq = new Wp_Query();
			$mq->query(array('meta_key' => 'mailchimp_id', 'meta_value' => $c['id']));
			if ($mq->have_posts()) continue;

			$content = $api->campaignContent($c['id']);
			if (!isset($content['html'])) continue;


			$save = array();
			$save['post_title'] = wp_strip_all_tags($c['subject']);
			$save['post_content'] = '<a href="'. $c['archive_url'].'" target="_blank">View the HTML Version</a>'."\r\n&nbsp;\r\n". $content['text'];
			$save['post_status'] = 'publish';
			$save['post_author'] = (int)$option['author'];
			$save['post_date'] = $c['send_time'];

			$postid = wp_insert_post($save);

			if (is_wp_error($postid)) continue;

			if (is_array($option['categories']) && (count($option['categories'])>0))
				wp_set_post_terms($postid, $option['categories'], 'category');

			add_metadata('post', $postid, 'mailchimp_id', $c['id']);
			add_metadata('post', $postid, 'mailchimp_url', $c['archive_url']);

		}

	}

	

}
// hourly, twice daily, daily


function hms_cron_schedules($schedules) {

	$schedules['five_minutes'] = array(
		'interval' => 300,
		'display' => 'Every Five Minutes'
	);
	$schedules['ten_minutes'] = array(
		'interval' => 600,
		'display' => 'Every Ten Minutes'
	);
	$schedules['fifteen_minutes'] = array(
		'interval' => 900,
		'display' => 'Every Fifteen Minutes'
	);
	$schedules['thirty_minutes'] = array(
		'interval' => 1800,
		'display' => 'Every Thirty Minutes'
	);

	return $schedules;
}

function hms_mc_deactivate() {
	wp_clear_scheduled_hook('hms_mc_cron_hook');
}



function hms_mc_shortcode( $atts ) {

	$options = get_option('hms_mc_importer');

	extract(shortcode_atts(array(
		'apikey' => $options['apikey'],
		'listid' => $options['apilist'],
		'showdate' => false,
		'dateformat' => 'm/d/Y h:i a',
		'cache' => 600
	), $atts));

	if ($apikey == '' || $apikey == null) return 'No MailChimp API key provided.';
	if ($listid == '' || $listid == null) return 'No MailChimp List Id provided.';

	if (!isset($options['sc_cache']) || $options['sc_cache'] < (time()- (int)$cache)) {
		$api = new MCAPI($apikey);
		$campaigns = $api->campaigns(
			array(
				'list_id' => $listid,
				'status' => 'sent',
				'exact' => true
			)
		);
		
		if ($api->errorCode) return $api->errorMessage;
		if (isset($campaigns['data']) && (count($campaigns['data'])<1)) return 'No campaign could be found.';

		$options['sc_cache_items'] = array();
		foreach($campaigns['data'] as $c)
			$options['sc_cache_items'][] = array('archive_url' => $c['archive_url'], 'subject' => $c['subject'], 'send_time' => $c['send_time']);
		

		$options['sc_cache'] = time();
		update_option('hms_mc_importer', $options);
	}

	$ret = '';

	if (count($options['sc_cache_items'])>0) {

		$ret = '<ul class="hms_mc_list">';

		foreach($options['sc_cache_items'] as $c) {
			$ret .= '<li><a href="'.$c['archive_url'].'" target="_blank">'.$c['subject'].'</a>';

			if ($showdate !== false)
				$ret .= '&nbsp; <span class="date">sent on '.date($dateformat, strtotime($c['send_time'])).'</span>';

			$ret .= '</li>';
		}

		$ret .= '</ul>';
	}
	return $ret;
}