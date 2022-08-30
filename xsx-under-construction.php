<?php

/**
 * -----------------------------------------------------------------------------
 * Plugin Name: Under Construction
 * Description: Redirect not logged in users. Allow testers to see the site sending a magic link.
 * Version: 1.1.0
 * Requires PHP: 5.6
 * Requires CP: 1.4
 * Author: Simone Fioravanti
 * Author URI: https://software.gieffeedizioni.it
 * Plugin URI: https://software.gieffeedizioni.it
 * Text Domain: xsx-under-construction
 * Domain Path: /languages
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * -----------------------------------------------------------------------------
 * This is free software released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.txt.
 * -----------------------------------------------------------------------------
 */

namespace XXSimoXX\UnderConstruction;

require_once 'classes/UpdateClient.class.php';

class UnderConstruction {

	private $options = false;

	const SLUG = 'xsx-under-construction';

	public function __construct() {
		add_action('template_redirect', [$this, 'maybe_redirect']);
		add_action('admin_menu', [$this, 'create_preview_menu'], 100);
		add_action('wp_before_admin_bar_render', [$this, 'toolbar']);
		add_action('admin_enqueue_scripts', [$this, 'styles']);
		register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);
	}

	private function load_options() {

		if ($this->options !== false) {
			return;
		}

		$options = get_option('xsx_under_construction', false);

		if ($options !== false) {
			$this->options = $options;
			return;
		}

		// Default values
		$this->options = [
			'ver'		  => '001',
			'redirect_to' => plugin_dir_url(__FILE__).'templates/maintenance-1.html',
			'keys'        => [
				['key' => 12453679, 'notes' => 'Key given to Joe.',],
				['key' => 47893215, 'notes' => 'Key given to Kate.',],
			],
		];
		$this->save_options();

	}

	private function save_options() {
		update_option('xsx_under_construction', $this->options);
	}

	public function create_preview_menu() {

		$page = add_submenu_page(
			'options-general.php',
			esc_html__('Under Construction', 'xsx-under-construction'),
			esc_html__('Under Construction', 'xsx-under-construction'),
			'manage_options',
			self::SLUG,
			[$this, 'render_menu']
		);

		add_action('load-'.$page, [$this, 'delete_action']);
		add_action('load-'.$page, [$this, 'new_action']);
		add_action('load-'.$page, [$this, 'url_action']);

	}

	private function render_embedded () {
		$templates = scandir(dirname(__FILE__).'/templates');
		foreach ($templates as $template) {
			if (!preg_match('{\.html$}', $template)) {
				continue;
			}
			$name  = preg_replace('{\.html$}', '', $template);
			$image = plugin_dir_url(__FILE__).'images/'.$name.'.svg';
			$url = esc_url_raw(plugin_dir_url(__FILE__).'templates/'.$template);
			echo '<a href="#" onclick="document.getElementById(\'url\').value=\''.esc_url_raw($url).'\'"><img src="'.esc_url_raw($image).'" title="'.esc_html($name).'" class="xuc-pw"></a>';

		}
	}

	public function render_menu () {

		echo '<div class="wrap">';

		$this->display_notices();
		$this->load_options();

		echo '<div class="xuc xuc-general">';
		echo '<h1>'.esc_html__('Under Construction', 'xsx-under-construction').'</h1>';
		echo '<p>'.esc_html__('Not logged in users can\'t see your site and are redirected. To enable your site back deactivate "Under Construction" plugin.', 'xsx-under-construction').'</p>';
		echo '</div>';

		echo '<div class="xuc xuc-url">';
		echo '<h2>'.esc_html__('Redirect to', 'xsx-under-construction').'</h2>';
		echo '<p>'.esc_html__('Here you can change were your not logged in user are redirected to.', 'xsx-under-construction').'<br>';

		echo '<form action="'.esc_url_raw(add_query_arg(['action' => 'url'], admin_url('admin.php?page='.self::SLUG))).'" method="POST">';
		wp_nonce_field('url', '_xuc');
		echo '<label for="url">'.esc_html__('Url to redirect to: ', 'xsx-under-construction').'</label>';
		echo '<input type="text" size="'.(int)(strlen($this->options['redirect_to'])).'" name="url" id="url" value="'.esc_url_raw($this->options['redirect_to']).'"></input>';
		echo '<input type="submit" class="button button-primary" value="'.esc_html__('Update', 'xsx-under-construction').'"></input>';
		echo '</form>';
		echo '<p><i>'.esc_html__('Click on the images below to get a basic page.', 'xsx-under-construction').'</i></p>';
		$this->render_embedded();
		echo '</div>';

		echo '<div class="xuc xuc-keys">';
		echo '<h2>'.esc_html__('Magic links', 'xsx-under-construction').'</h2>';
		echo '<p>'.esc_html__('If you want someone to give a look to this site send them a magic link.', 'xsx-under-construction').'<br>';
		/* translators: %1$s is site URL. */
		echo sprintf(esc_html__('The link will set a session cookie and redirect them to the actual site: %1$s.', 'xsx-under-construction'), '<i>'.esc_url_raw(site_url()).'</i>').'<br>';
		echo esc_html__('Use the "Copy to clipboard" link under the key to get the url to send.', 'xsx-under-construction').'</p>';

		$ListTable = new UnderConstructionListTable();
		$ListTable->load_items($this->options['keys']);
		$ListTable->prepare_items();
		$ListTable->display();

		echo '<p>'.esc_html__('You can add new magic links. Use the "Notes" field as a reminder. The key will be autogenerated.', 'xsx-under-construction').'<br>';
		echo esc_html__('You can also delete magic links and people with that key will again be redirected to the Under Construction page.', 'xsx-under-construction').'</p>';

		echo '<form action="'.esc_url_raw(add_query_arg(['action' => 'new'], admin_url('admin.php?page='.self::SLUG))).'" method="POST">';
		wp_nonce_field('new', '_xuc');
		echo '<label for="new_note">'.esc_html__('Notes about the key: ', 'xsx-under-construction').'</label>';
		echo '<input type="text" size="40" maxlength="40" name="new_note" id="new_note" placeholder="'.esc_html__('A note about this key.', 'xsx-under-construction').'"></input>';
		echo '<input type="submit" class="button button-primary" value="'.esc_html__('New key', 'xsx-under-construction').'"></input>';
		echo '</form>';
		echo '</div>';

		echo '</div>';

	}

	private function add_notice($message, $failure = false) {
		$other_notices = get_transient('xsx_under_construction_notices');
		$notice = $other_notices === false ? '' : $other_notices;
		$failure_style = $failure ? 'notice-error' : 'notice-success';
		$notice .= '<div class="notice '.$failure_style.' is-dismissible go-away-soon">';
		$notice .= '    <p>'.wp_kses($message, ['br' => [], 'i' => [],]).'</p>';
		$notice .= '</div>';
		$notice .= '<script>jQuery(".go-away-soon").delay(3000).hide("slow", function() {});</script>';
		set_transient('xsx_under_construction_notices', $notice, \HOUR_IN_SECONDS);
	}

	private function display_notices() {
		$notices = get_transient('xsx_under_construction_notices');
		if ($notices === false) {
			return;
		}
		// This contains html formatted from 'add_notice' function that uses 'wp_kses'.
		echo $notices; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		delete_transient('xsx_under_construction_notices');
	}

	public function new_action() {

		if (!isset($_GET['action'])) {
			return;
		}
		if ($_GET['action'] !== 'new') {
			return;
		}
		if (!check_admin_referer('new', '_xuc')) {
			return;
		}
		if (!current_user_can('manage_options')) {
			return;
		}
		if (!isset($_REQUEST['new_note'])) {
			return;
		}

		$note = substr(sanitize_text_field(wp_unslash($_REQUEST['new_note'])), 0, 40);
		$this->load_options();
		// Generate a non existing random key.
		do {
			$random_key = wp_rand(10000000, 99999999);
		} while (array_search($random_key, array_column($this->options['keys'], 'key')) !== false);

		array_push(
			$this->options['keys'],
			[
				'key'   => $random_key,
				'notes' => $note,
			]
		);

		$this->save_options();
		$this->add_notice(esc_html__('New key generated.', 'xsx-under-construction').'<br><i>'.$note.'</i>', false);

		$sendback = remove_query_arg(['action', 'new_note', '_xuc'], wp_get_referer());
		wp_safe_redirect($sendback);
		exit;

	}

	public function url_action() {

		if (!isset($_GET['action'])) {
			return;
		}
		if ($_GET['action'] !== 'url') {
			return;
		}
		if (!check_admin_referer('url', '_xuc')) {
			return;
		}
		if (!current_user_can('manage_options')) {
			return;
		}
		if (!isset($_REQUEST['url'])) {
			return;
		}

		$url = esc_url_raw(wp_unslash($_REQUEST['url']));

		$this->load_options();
		$this->options['redirect_to'] = $url;

		$this->save_options();
		$this->add_notice(esc_html__('Url updated.', 'xsx-under-construction').'<br><i>'.$url.'</i>', false);

		$sendback = remove_query_arg(['action', 'url', '_xuc'], wp_get_referer());
		wp_safe_redirect($sendback);
		exit;

	}

	public function delete_action() {

		if (!isset($_GET['action'])) {
			return;
		}
		if ($_GET['action'] !== 'delete') {
			return;
		}
		if (!check_admin_referer('delete', '_xuc')) {
			return;
		}
		if (!current_user_can('manage_options')) {
			return;
		}
		if (!isset($_REQUEST['key'])) {
			return;
		}

		$this->load_options();

		$notes = '';

		foreach ($this->options['keys'] as $key => $value) {
			if ($value['key'] === intval($_REQUEST['key'])) {
				$notes = $value['notes'];
				unset($this->options['keys'][$key]);
				break;
			}
		}

		$this->save_options();
		$this->add_notice(esc_html__('Key deleted.', 'xsx-under-construction').'<br><i>'.$notes.'</i>', false);

		$sendback = remove_query_arg(['action', 'key', '_xuc'], wp_get_referer());
		wp_safe_redirect($sendback);
		exit;

	}

	private function is_valid_key($key) {
		$this->load_options();
		foreach ($this->options['keys'] as $v) {
			if ($v['key'] === intval($key)) {
				return true;
			}
		}
		return false;
	}

	public function maybe_redirect() {

		// User logged in can see the site.
		if (is_user_logged_in()) {
			return;
		}

		// Don't break WP CLI.
		if (defined('WP_CLI') && WP_CLI) {
			return;
		}

		// No needed information? Define the field.
		if (!isset($_SERVER['SCRIPT_NAME'])) {
			$_SERVER['SCRIPT_NAME'] = '';
		}
		if (!isset($_SERVER['REQUEST_SCHEME'])) {
			$_SERVER['REQUEST_SCHEME'] = '';
		}
		if (!isset($_SERVER['HTTP_HOST'])) {
			$_SERVER['HTTP_HOST'] = '';
		}
		if (!isset($_SERVER['REQUEST_URI'])) {
			$_SERVER['REQUEST_URI'] = '';
		}

		// Let people login.
		if (stripos(sanitize_text_field(wp_unslash($_SERVER['SCRIPT_NAME'])), strrchr(wp_login_url(), '/')) !== false) {
			return;
		}

		// Let people view our maintenance page.
		$this->load_options();
		if (esc_url_raw(wp_unslash($_SERVER['REQUEST_SCHEME']).'://'.wp_unslash($_SERVER['HTTP_HOST']).wp_unslash($_SERVER['REQUEST_URI'])) === $this->options['redirect_to']) {
			return;
		}

		// Check for the "magic key" cookie.
		if (isset($_COOKIE['selective_preview']) && $this->is_valid_key(intval($_COOKIE['selective_preview']))) {
			return;
		}

		// Redirect to selected destination. Not using wp_safe_redirect for flexibility.
		wp_redirect($this->options['redirect_to'], 307); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit();

	}

	public function toolbar() {
		global $wp_admin_bar;
		$wp_admin_bar->add_node([
			'id'    => self::SLUG,
			'title' => esc_html__('Under Construction', 'xsx-under-construction'),
			'meta'  => ['class' => 'xuc-admin-bar'],
			'href'  => admin_url('admin.php?page='.self::SLUG),
		]);
	}

	public function styles() {
		wp_enqueue_style('xsx-under-construction-css', plugins_url('css/style.css', __FILE__), [], '0.0.2');
	}

	public static function uninstall() {
		delete_option('xsx_under_construction');
	}

}

new UnderConstruction;

if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
}

class UnderConstructionListTable extends \WP_List_Table {

	// Contains the requested filter type (all, plugins or themes).
	private $filtertype = 'all';

	// Contains the data to be rendered, as we want this to be passed from another class.
	private $keys = [];

	// Load list items, as we want this to be passed from another class.
	public function load_items($keys) {
		$this->keys = $keys;
	}

	// Output columns definition.
	function get_columns() {
		return [
			'key'       => esc_html__('Key', 'xsx-under-construction'),
			'link' 		=> esc_html__('Link', 'xsx-under-construction'),
			'notes' 	=> esc_html__('Notes', 'xsx-under-construction'),
		];
	}

	// Output hidden columns.
	function get_hidden_columns() {
		return [
			'link',
		];
	}


	// Just output the column.
	function column_default($item, $column_name) {
		return $item[$column_name];
	}

	// For "Key" column add row actions.
	function column_key($item) {
		$actions = [
			'delete' => '<a href="'.wp_nonce_url(add_query_arg(['action' => 'delete', 'key' => $item['key']]), 'delete', '_xuc').'">'.esc_html__('Delete', 'xsx-under-construction').'</a>',
			'copy'   => '<a href="#" onclick="navigator.clipboard.writeText(\''.$item['link'].'\')">'.esc_html__('Copy to clipboard', 'xsx-under-construction').'</a>',
		];
		$key = '<span class="row-title">'.$item['key'].'</span>';
		return sprintf('%1$s %2$s', $key, $this->row_actions($actions));
	}

	// For "Notes" column if empty add a message.
	function column_notes($item) {
		if ($item['notes'] !== '') {
			return $item['notes'];
		}
		return '<i>'.esc_html__('No note for this key.', 'xsx-under-construction').'</i>';
	}

	// Prepare our columns and insert data.
	function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = [];
		$this->_column_headers = [$columns, $hidden, $sortable];
		$data = [];
		foreach ($this->keys as $key) {
			$data[] = [
				'key'   => $key['key'],
				'link'  => plugin_dir_url(__FILE__).'preview.php?preview='.$key['key'],
				'notes' => $key['notes'],
			];
		}
		$this->items = $data;
	}

}