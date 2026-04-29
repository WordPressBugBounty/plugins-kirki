<?php
namespace KirkiComponentLib;

use Kirki\HelperFunctions;

define( 'KIRKI_COMPONENT_LIBRARY_APP_PREFIX', 'KirkiComponentLibrary' );
define( 'KIRKI_COMPONENT_LIBRARY_ROOT_URL', plugin_dir_url( __FILE__ ) );
define( 'KIRKI_COMPONENT_LIBRARY_ROOT_PATH', plugin_dir_path( __FILE__ ) );

require_once KIRKI_COMPONENT_LIBRARY_ROOT_PATH . '/controller/CompLibFormHandler.php';
require_once KIRKI_COMPONENT_LIBRARY_ROOT_PATH . '/controller/ShowUserMetadata.php';
require_once KIRKI_COMPONENT_LIBRARY_ROOT_PATH . '/controller/ElementGenerator.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class KirkiComponentLibrary {

	private $component_lib_forms = array();

	public function __construct() {
		$this->init();
		add_filter( 'kirki_element_generator_' . KIRKI_COMPONENT_LIBRARY_APP_PREFIX, array( $this, 'element_generator' ), 10, 2 );
		add_filter( 'kirki_external_collection_options', array( $this, 'modify_external_collection_options' ), 10, 2 );
		add_filter( 'kirki_collection_comments', array( $this, 'kirki_collection_comments' ), 10, 2 );

		add_filter( 'kirki_dynamic_content', array( $this, 'kirki_dynamic_content' ), 10, 2 );
		new ShowUserMetadata();
	}

	public function init() {
	  //phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$action = sanitize_text_field( isset( $_GET['action'] ) ? $_GET['action'] : null );
		if ( 'kirki' === $action ) {
			$load_for = sanitize_text_field( isset( $_GET['load_for'] ) ? wp_unslash( $_GET['load_for'] ) : null );
			if ( 'kirki-iframe' !== $load_for ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'load_editor_assets' ), 1 );
			}
		}
	}

	public function load_editor_assets() {
		wp_enqueue_script( KIRKI_COMPONENT_LIBRARY_APP_PREFIX . '-editor', KIRKI_COMPONENT_LIBRARY_ROOT_URL . 'assets/js/' . 'editor.min.js', array(), KIRKI_VERSION, array( 'in_footer' => true ) );
		wp_add_inline_script(
			KIRKI_COMPONENT_LIBRARY_APP_PREFIX . '-editor',
			'const ' . KIRKI_COMPONENT_LIBRARY_APP_PREFIX . ' = ' . json_encode(
				array(
					'base_url' => KIRKI_COMPONENT_LIBRARY_ROOT_URL,
				)
			),
			'before'
		);
		add_action(
			'wp_enqueue_scripts',
			function () {
				global $kirki_editor_assets;
				$kirki_editor_assets['scripts'][] = KIRKI_COMPONENT_LIBRARY_APP_PREFIX . '-editor';
			},
			50
		);
	}

	public function add_component_library_script( $script_tags ) {
		$value  = $this->component_lib_forms;
		$val    = wp_json_encode( $value );
		$script = 'var ' . KIRKI_COMPONENT_LIBRARY_APP_PREFIX . ' = window.' . KIRKI_COMPONENT_LIBRARY_APP_PREFIX . " === undefined? {form: $val, root_url:'" . KIRKI_COMPONENT_LIBRARY_ROOT_URL . "'} : {..." . KIRKI_COMPONENT_LIBRARY_APP_PREFIX . ', form:{...(' . KIRKI_COMPONENT_LIBRARY_APP_PREFIX . ".form || {}), ...$val}};";

		$script_tags .= "<script data='" . KIRKI_COMPONENT_LIBRARY_APP_PREFIX . "-elements-property-vars'>$script</script>";

		return $script_tags;
	}

	public function load_element_scripts_and_styles() {
		add_filter('kirki_add_script_tags', array( $this, 'add_component_library_script' ) );
	  //phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$action = sanitize_text_field( isset( $_GET['action'] ) ? $_GET['action'] : null );
		if ( 'kirki' !== $action ) {
			add_action(
				'wp_enqueue_scripts',
				function () {
					wp_enqueue_style( KIRKI_COMPONENT_LIBRARY_APP_PREFIX, KIRKI_COMPONENT_LIBRARY_ROOT_URL . 'assets/css/' . 'main.min.css', array(), KIRKI_VERSION );
					wp_enqueue_script( KIRKI_COMPONENT_LIBRARY_APP_PREFIX, KIRKI_COMPONENT_LIBRARY_ROOT_URL . 'assets/js/' . 'preview.min.js', array(), KIRKI_VERSION, array( 'in_footer' => true ) );
				}
			);
		}
	}

	public function element_generator( $string, $props ) {
		$this->load_element_scripts_and_styles();
		$props['component_lib_forms'] = $this->component_lib_forms;
		$hide                         = false;
		if (
		'kirki-login-error' === $props['element']['name'] ||
		'kirki-register-error' === $props['element']['name'] ||
		'kirki-forgot-password-error' === $props['element']['name'] ||
		'kirki-change-password-error' === $props['element']['name'] ||
		'kirki-retrieve-username-error' === $props['element']['name']
		) {
			$hide = true;
		}
		$eg                        = new ElementGenerator( $props );
		$gen                       = $eg->generate_common_element( $hide );
		$this->component_lib_forms = $eg->component_lib_forms;
		return $gen;
	}

	public function modify_external_collection_options( $options, $args ) {
		$comment_collection = array(
			'title'               => 'Comments',
			'value'               => 'comments',
			'inherit'             => true,
			'pegination'          => true,
			'default_select_type' => 'comment',
			'group'               => array(
				array(
					'title'    => 'Post Comments',
					'value'    => 'comment',
					'itemType' => 'comment',
				),
			  // TODO: need to get all comment type and add it as list here.
			),
		);
		$options[] = $comment_collection;
		return $options;
	}

	public function kirki_collection_comments( $value, $args ) {
		$all_comments = array();
		$c_args       = array( 'post_id' => $args['post_parent'] );
		if ( isset( $args['parent_item_type'] ) && $args['parent_item_type'] === 'comment' ) {
			$c_args['parent'] = $args['context']['comment_ID'];
		} elseif ( isset( $args['context']['comment_ID'] ) ) {
			$c_args['parent'] = $args['context']['comment_ID'];
		}

		$c_args['current_page']  = 1;
		$c_args['item_per_page'] = 100;
		$ac                      = HelperFunctions::get_comments( $c_args );

		foreach ( $ac['data'] as $comment ) {
			$all_comments[] = array(
				'id'                   => $comment->comment_ID,
				'comment_post_ID'      => $comment->comment_post_ID,
				'comment_author'       => $comment->comment_author,
				'comment_author_email' => $comment->comment_author_email,
				'comment_author_url'   => $comment->comment_author_url,
				'comment_date'         => $comment->comment_date,
				'comment_date_gmt'     => $comment->comment_date_gmt,
				'comment_content'      => $comment->comment_content,
				'comment_approved'     => $comment->comment_approved,
				'comment_agent'        => $comment->comment_agent,
				'comment_type'         => $comment->comment_type,
				'comment_parent'       => $comment->comment_parent,
				'user_id'              => $comment->user_id,
			);
		}
		return array(
			'data'       => $all_comments,
			'pagination' => array(),
			'itemType'   => 'comment',
		);
	}

	public function kirki_dynamic_content( $value, $args ) {
		if ( isset( $args['dynamicContent'] ) ) {
			if ( $args['dynamicContent']['type'] === 'comment' ) {
				if ( isset( $args['options']['comment'], $args['dynamicContent']['value'], $args['options']['comment'][ $args['dynamicContent']['value'] ] ) ) {
					return $args['options']['comment'][ $args['dynamicContent']['value'] ];
				} elseif ( isset( $args['dynamicContent']['value'] ) ) {
					return $args['dynamicContent']['value'];
				}
			}
		}
		return $value;
	}
}


new KirkiComponentLibrary();
