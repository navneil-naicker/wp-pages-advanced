<?php
/**
	Plugin Name: WordPress Pages Advanced
	Plugin URI: https://wordpress.org/plugins/wp-pages-advanced/
	Description: Structures the pages in WordPress for more robust page adding, edit, sorting and managing. It is more clean you know.
	Version: 1.0.8
	Author: Navneil Naicer
	Author URI: http://www.navz.me
	License: GPLv2 or later
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
	
	Copyright 2016 Navneil Naicker

*/

//Preventing from direct access
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class navzPageTree{
	public $version = '1.0.8';
	public $prefix = 'wp-pages-advanced';
	public $dir;
	public $url;
	public $hierarchy = array();

	public function __construct(){
		add_action( 'admin_init', array($this, 'navz_redirect'));
		add_action( 'admin_menu', array($this, 'navz_change_post_menu_label'));
		$this->dir = plugin_dir_path( __FILE__ );
		$this->url = plugins_url() . '/' . $this->prefix;
	 	add_action('admin_menu', array($this, 'menu') );
		add_action('admin_enqueue_scripts', array($this, 'scripts'));
		add_filter( 'page_attributes_dropdown_pages_args', array($this, 'wppa_remove_pages_from_attr_metabox'), 10, 2 ); 
	}
	
	//All Pages should redirect to navz page when plugin is activated
	public function navz_redirect(){
    global $pagenow;
    if($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'page' && empty($_GET['page']) and empty($_GET['post_status']) and empty($_GET['s']) ){
        wp_redirect( admin_url('/edit.php?post_type=page&page=' . $this->prefix, 'http'), 301);
        exit;
    }
	}
	
	//Changing the All Pages link to navz Page when plugin is activated
	public function navz_change_post_menu_label() {
		global $menu, $submenu;
		if( isset($menu[20][2]) and isset($submenu['edit.php?post_type=page'][5][2]) ){
			$menu[20][2] = 'edit.php?post_type=page&page=' . $this->prefix;
			$submenu['edit.php?post_type=page&page=' . $this->prefix] = $submenu['edit.php?post_type=page'];
			$submenu['edit.php?post_type=page'][5][2] = 'edit.php?post_type=page&page=' . $this->prefix;
		}
	}
	
	//Our Custom styles and js scripts
	public function scripts( $hook ){
		wp_register_style( $this->prefix . '-style', $this->url . '/css/'. $this->prefix .'.css', false, $this->version);
		wp_enqueue_style( ''. $this->prefix .'-style' );
		wp_enqueue_script( 'jquery-ui-sortable', 'jquery-ui-sortable', 'jQuery', $this->version, true);
		wp_enqueue_script( ''. $this->prefix .'-js', $this->url . '/js/'. $this->prefix .'.js', array(), $this->version, true );
	}
	
	//Get the pages from the database
	public function pages(){
		$navzPages = get_pages(array(
			'sort_column' => 'menu_order',
			'sort_order' => 'asc',
			'post_status' => 'publish,private,draft,pending'
		));
		return $navzPages;
	}
	
	//Loop over the database results and save it as associative array
	public function get(){
		$hierarchy = array();
		$pages = $this->pages();
		if( !empty($pages) ){
			foreach( $pages as $page ){
				$post_parent = $page->post_parent;
				$hierarchy[$post_parent][] = $page;
			}
			$this->hierarchy = $hierarchy;
		} else {
			echo 'Truth be told, you don\'t have any pages. I know you excited so click on <i><b>Add Multiple</b></i> link at the top of this page to <i>Get Started</i>';
		}
	}
	
	//Loop over all the pages and layout them as a tree
	public function hierarchy( $post_id, $class = 'wp-pages-advanced-parent'){
		$pages = $this->hierarchy;
		if( !empty($pages[$post_id]) ){
			$nonce = wp_create_nonce( 'wp-pages-advanced-nonce' );
			$padv_hide_from_pagelist = get_option('padv_hide_from_pagelist');
			$padv_hide_from_pagelist = explode(',', $padv_hide_from_pagelist);
			echo '<ul' . ' class=' . $class . ' id="item-' . $post_id. '">';
			foreach( $pages[$post_id] as $page ){
				$post_id = $page->ID;
				$post_title = $page->post_title;
				$post_author = $page->post_author;
				$post_status = $page->post_status;
				$post_permalink = get_permalink( $post_id );
				$post_edit = admin_url() . 'post.php?post=' . $post_id . '&action=edit';
				
				//Links
				if( !empty($pages[$post_id]) and !in_array($post_id, $padv_hide_from_pagelist) ){
					$plusLink = '<a href="#" class="dashicons-plus-link" data-child="'.$post_id.'"><span class="dashicons dashicons-plus" title="Show child pages"></span></a>';
					$viewLink = '<a href="' . $post_permalink . '" class="dashicons-page-link"><span class="dashicons dashicons-admin-links" title="View this page"></span></a>';
					$dragLink = '<a href="#" class="dashicons dashicons-sort wp-pages-advanced-handle-sort" title="Click and hold me to sort/order"></a>';
					$publishLabel = $post_status !== 'publish'? ' — <i style="font-size: 12px">'. $post_status . '</i>': '';
				} else {
					$plusLink = '<a class="dashicons-plus-link">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a>';
					$viewLink = '<a href="' . $post_permalink . '" class="dashicons-page-link"><span class="dashicons dashicons-admin-links" title="View this page"></span></a>';
					$dragLink = '<a href="#" class="dashicons dashicons-sort wp-pages-advanced-handle-sort" title="Click and hold me to sort/order"></a>';
					$publishLabel = $post_status !== 'publish'? ' — <i style="font-size: 12px">'. $post_status . '</i>': '';
				}
				
				if( current_user_can('delete_others_pages') or current_user_can('administrator') ){
					$deleteLink = '<a href="' . admin_url('admin-ajax.php') . '?action=AjaxPagesAdvancedPageTreeDelete&_wpnonce=' . $nonce . '&id=' . $post_id . '" class="dashicons-trash-link" data-id="' . $post_id . '" data-title="' . $post_title . '"><span class="dashicons dashicons-trash" title="Trash this page"></span></a>';
				} else {
					$deleteLink = '<a class="dashicons-trash-link wp-page-advanced-link-disabled" disabled><span class="dashicons dashicons-trash" title="You don\'t have permission to delete this page"></span></a>';
				}
				
				if( current_user_can('edit_others_pages') or current_user_can('administrator') ){
					$editLink = '<a href="' . $post_edit . '" class="dashicons-menu-edit-link"><span class="dashicons dashicons-edit" title="Edit this page"></span></a>';
				} else {
					$editLink = '<a class="dashicons-menu-edit-link wp-page-advanced-link-disabled" disabled><span class="dashicons dashicons-edit" title="You don\'t have permission to edit this page"></span></a>';
				}
				
				if( current_user_can('edit_others_pages') or current_user_can('administrator') ){
					$addPageLink = '<a data-id="' . $post_id .'" href="#" class="dashicons-menu-add-page-link"><span class="dashicons dashicons-admin-page"></span></a>';
				} else {
					$addPageLink = '<a class="dashicons-menu-add-page-link wp-page-advanced-link-disabled" disabled><span class="dashicons dashicons-edit" title="You don\'t have permission to edit this page"></span></a>';
				}
				
				$post_title = '<span>' . $post_title . '</span>';
				
				if( !empty($pages[$post_id]) and !in_array($post_id, $padv_hide_from_pagelist) ){
					echo '<li id="item_' . $post_id . '">';
					echo $plusLink;
					echo $editLink;
					echo $deleteLink;
					echo $addPageLink;
					echo $viewLink;
					echo $dragLink;
					echo $post_title;
					echo $publishLabel;
					echo '</li>';
					$this->hierarchy( $post_id, 'wp-pages-advanced-children');
				} else {
					echo '<li id="item_' . $post_id . '">';
					echo $plusLink;
					echo $editLink;
					echo $deleteLink;
					echo $addPageLink;
					echo $viewLink;
					echo $dragLink;
					echo ( in_array($post_id, $padv_hide_from_pagelist)) ? $post_title . ' <span style="padding-top:10px;color:#ba281e;font-size:18px;" class="dashicons dashicons-info" title="This pages children are hidden"></span>': $post_title;
					echo $publishLabel;
					echo '</li>';
				}
			}
			echo '</ul>';
		}
	}
	
	//Create a menu in the admin sidebar
	public function menu(){
		add_pages_page('Pages', 'All Pages', 'edit_pages', $this->prefix, array($this, 'show') );
	}
	
	//Include the html layouts
	public function show(){
		require_once( $this->dir . 'templates/add-multiple.php' );
		require_once( $this->dir . 'templates/show.php' );
	}
	
	public function wppa_remove_pages_from_attr_metabox( $dropdown_args, $post ){
		$padv_hide_from_pagelist = get_option('padv_hide_from_pagelist');
		$dropdown_args['exclude'] =  $padv_hide_from_pagelist;
		return $dropdown_args;
	}
	
}

$navzPageTree = new navzPageTree;
	//Add custom fields on screen options
	function wppa_pages_custom_screen_options( $settings, \WP_Screen $screen ){
	if( $screen->id == 'page' ){
			$padv_hide_from_pagelist = get_option('padv_hide_from_pagelist');			
			$padv_hide_from_pagelist = explode(',', $padv_hide_from_pagelist);
			$padv_hide_from_pagelist = (!empty($padv_hide_from_pagelist) and in_array(get_the_ID(), $padv_hide_from_pagelist))? 'checked': null;

			return sprintf('
				<fieldset class="post-pref">
					<legend class="screen-layout">Attributes</legend>
					<div>
						<label class="attr-prefs-5"><input type="checkbox" ' . $padv_hide_from_pagelist . ' id="screen-page-hide-from-page-list" data-id="padv_hide_from_pagelist" value="' . get_the_ID() . '"> This page cannot have anymore children</label>
					</div>
				</fieldset>		
			');
		}
	}
	add_filter( 'screen_settings', 'wppa_pages_custom_screen_options', 10, 2);

	//The Attributes section on the screen option is saved using this function
	function wppa_menu_screen_option(){
		
		if( isset($_POST['data']) and $_POST['data'] == 'padv_hide_from_pagelist' ){
			$value = $_POST['value'];
			$value = preg_replace('/\D/', '', $value);
			$option = get_option('padv_hide_from_pagelist');
			if( empty($option) ){
				update_option('padv_hide_from_pagelist', $value);
			} else {
				$option = explode(',', $option);
				if( in_array($value, $option) ){
					foreach($option as $key => $item){
						if( $item == $value ){
							unset( $option[$key] );
						}
					}
					update_option('padv_hide_from_pagelist', implode(',', $option) );
				} else {
					$option[] = $value;
					update_option('padv_hide_from_pagelist', implode(',', $option) );
				}
			}
		}
		
		die();
	}
	add_action( 'wp_ajax_wppa_menu_screen_option', 'wppa_menu_screen_option' );
	add_action( 'wp_ajax_nopriv_wppa_menu_screen_option', 'wppa_menu_screen_option' );
		
	//Add some JavaScript in the footer that we can use for our custom fields that was hooked in the CMS
	add_action('admin_footer', 'wppa_admin_footer');
	function wppa_admin_footer(){
?>
  <style type="text/css">
  	#expiretimestampdiv select#mm{
			padding: 0;
			margin: 0;
			font-size: 12px;
			height: auto;
			line-height: none;
		}
  </style>
	<script type="text/javascript">
		jQuery('#adv-settings #screen-page-hide-from-page-list, #adv-settings #screen-page-hide-children-from-dropdown-page-list, #adv-settings #screen-page-hide-children-from-dropdown-edit-screen').change(function(){
			var action, data, value;
			data = jQuery(this).attr('data-id');
			value = jQuery(this).val();
			action = 'wppa_menu_screen_option';
			jQuery.post(ajaxurl, {'action': action, 'data': data, 'value': value});
		});
			
		jQuery('.misc-expire-curtime .expire-edit-timestamp, #expiretimestampdiv .cancel-timestamp').click(function(){
			jQuery('#expiretimestampdiv').slideToggle('fast');
			return false;
		});
	</script>
<?php
	}

//Saving the order into the database
add_action( 'wp_ajax_AjaxAdvancedPageTreeUpdateSortOrder', 'AjaxAdvancedPageTreeUpdateSortOrder' );
add_action( 'wp_ajax_nopriv_AjaxAdvancedPageTreeUpdateSortOrder', 'AjaxAdvancedPageTreeUpdateSortOrder' );
function AjaxAdvancedPageTreeUpdateSortOrder(){
	set_time_limit( 0 );
	if( current_user_can('publish_pages') ){
		global $wpdb;
		$items = array();
		parse_str($_POST['items'], $items);
		$items = $items['item'];
		$key = 1;	
		foreach($items as $item){
			wp_update_post(array(
				'ID'  => $item,
				'menu_order' => $key
			));
			$key++;
		}
	}
	die();
}

//Moving the page to the trash
add_action( 'wp_ajax_AjaxPagesAdvancedPageTreeDelete', 'AjaxPagesAdvancedPageTreeDelete' );
add_action( 'wp_ajax_nopriv_AjaxPagesAdvancedPageTreeDelete', 'AjaxPagesAdvancedPageTreeDelete' );
function AjaxPagesAdvancedPageTreeDelete(){
	if( current_user_can('delete_others_pages') and wp_verify_nonce($_GET['_wpnonce'], 'wp-pages-advanced-nonce') and !empty($_GET['id']) ){
		$id = $_GET['id'];
		$id = preg_replace('/\D/', '', $id);
		$args = array( 
			'post_parent' => $id,
			'post_type' => 'page'
		);
		$posts = get_posts( $args );
		if ( is_array($posts) && count($posts) > 0 ){
			foreach($posts as $post){
				wp_update_post(array(
					'ID' => $post->ID,
					'post_status' => 'trash'
        		));
			}
		} else {
			wp_update_post(array(
				'ID' => $id,
				'post_status' => 'trash'
			));
		}
	}
	die();
}

//Add mulitple pages lightbox
add_action( 'wp_ajax_wp_page_advanced_add_mulitple_pages', 'wp_page_advanced_add_mulitple_pages' );
add_action( 'wp_ajax_nopriv_wp_page_advanced_add_mulitple_pages', 'wp_page_advanced_add_mulitple_pages' );
function wp_page_advanced_add_mulitple_pages(){
	if( current_user_can('publish_pages') ){
		$post_titles = $_POST['page-titles'];
		$post_parent = $_POST['page-parent'];
		$post_status = $_POST['page-status'];
		$post_parent = preg_replace('/\D/', '', $post_parent);
		$post_status = sanitize_title($post_status);
		if( count($post_titles ) ){
			foreach( $post_titles as $post_title ){
				if( !empty($post_title) ){
					$post = array(
						'post_title' => empty( $post_title )? '': $post_title,
						'post_status' => empty( $post_status )? 'publish': $post_status,
						'post_type' => 'page',
						'post_parent' => empty( $post_parent )? 0: $post_parent,
					);
					wp_insert_post( $post );				
				}
			}
			die('1 | Pages successfully been added.');
		}		
	}
	die();
}

function wppa_get_pages( $args = array() ) {
	global $wpdb;
	$defaults = array(
		'child_of' => 0, 'sort_order' => 'ASC',
		'sort_column' => 'post_title', 'hierarchical' => 1,
		'exclude' => array(), 'include' => array(),
		'meta_key' => '', 'meta_value' => '',
		'authors' => '', 'parent' => -1, 'exclude_tree' => array(),
		'number' => '', 'offset' => 0,
		'post_type' => 'page', 'post_status' => 'publish',
	);

	$r = wp_parse_args( $args, $defaults );

	$number = (int) $r['number'];
	$offset = (int) $r['offset'];
	$child_of = (int) $r['child_of'];
	$hierarchical = $r['hierarchical'];
	$exclude = $r['exclude'];
	$meta_key = $r['meta_key'];
	$meta_value = $r['meta_value'];
	$parent = $r['parent'];
	$post_status = $r['post_status'];

	// Make sure the post type is hierarchical.
	$hierarchical_post_types = get_post_types( array( 'hierarchical' => true ) );
	if ( ! in_array( $r['post_type'], $hierarchical_post_types ) ) {
		return false;
	}

	if ( $parent > 0 && ! $child_of ) {
		$hierarchical = false;
	}

	// Make sure we have a valid post status.
	if ( ! is_array( $post_status ) ) {
		$post_status = explode( ',', $post_status );
	}
	if ( array_diff( $post_status, get_post_stati() ) ) {
		return false;
	}

	// $args can be whatever, only use the args defined in defaults to compute the key.
	$key = md5( serialize( wp_array_slice_assoc( $r, array_keys( $defaults ) ) ) );
	$last_changed = wp_cache_get( 'last_changed', 'posts' );
	if ( ! $last_changed ) {
		$last_changed = microtime();
		wp_cache_set( 'last_changed', $last_changed, 'posts' );
	}

	$cache_key = "get_pages:$key:$last_changed";
	if ( $cache = wp_cache_get( $cache_key, 'posts' ) ) {
		// Convert to WP_Post instances.
		$pages = array_map( 'get_post', $cache );
		/** This filter is documented in wp-includes/post.php */
		$pages = apply_filters( 'get_pages', $pages, $r );
		return $pages;
	}

	$inclusions = '';
	if ( ! empty( $r['include'] ) ) {
		$child_of = 0; //ignore child_of, parent, exclude, meta_key, and meta_value params if using include
		$parent = -1;
		$exclude = '';
		$meta_key = '';
		$meta_value = '';
		$hierarchical = false;
		$incpages = wp_parse_id_list( $r['include'] );
		if ( ! empty( $incpages ) ) {
			$inclusions = ' AND ID IN (' . implode( ',', $incpages ) .  ')';
		}
	}

	$exclusions = '';
	if ( ! empty( $exclude ) ) {
		$expages = wp_parse_id_list( $exclude );
		if ( ! empty( $expages ) ) {
			$exclusions = ' AND ID NOT IN (' . implode( ',', $expages ) .  ')';
		}
	}

	$author_query = '';
	if ( ! empty( $r['authors'] ) ) {
		$post_authors = preg_split( '/[\s,]+/', $r['authors'] );

		if ( ! empty( $post_authors ) ) {
			foreach ( $post_authors as $post_author ) {
				//Do we have an author id or an author login?
				if ( 0 == intval($post_author) ) {
					$post_author = get_user_by('login', $post_author);
					if ( empty( $post_author ) ) {
						continue;
					}
					if ( empty( $post_author->ID ) ) {
						continue;
					}
					$post_author = $post_author->ID;
				}

				if ( '' == $author_query ) {
					$author_query = $wpdb->prepare(' post_author = %d ', $post_author);
				} else {
					$author_query .= $wpdb->prepare(' OR post_author = %d ', $post_author);
				}
			}
			if ( '' != $author_query ) {
				$author_query = " AND ($author_query)";
			}
		}
	}

	$join = '';
	$where = "$exclusions $inclusions ";
	if ( '' !== $meta_key || '' !== $meta_value ) {
		$join = " LEFT JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id )";

		// meta_key and meta_value might be slashed
		$meta_key = wp_unslash($meta_key);
		$meta_value = wp_unslash($meta_value);
		if ( '' !== $meta_key ) {
			$where .= $wpdb->prepare(" AND $wpdb->postmeta.meta_key = %s", $meta_key);
		}
		if ( '' !== $meta_value ) {
			$where .= $wpdb->prepare(" AND $wpdb->postmeta.meta_value = %s", $meta_value);
		}

	}

	if ( is_array( $parent ) ) {
		$post_parent__in = implode( ',', array_map( 'absint', (array) $parent ) );
		if ( ! empty( $post_parent__in ) ) {
			$where .= " AND post_parent IN ($post_parent__in)";
		}
	} elseif ( $parent >= 0 ) {
		$where .= $wpdb->prepare(' AND post_parent = %d ', $parent);
	}

	if ( 1 == count( $post_status ) ) {
		$where_post_type = $wpdb->prepare( "post_type = %s AND post_status = %s", $r['post_type'], reset( $post_status ) );
	} else {
		$post_status = implode( "', '", $post_status );
		$where_post_type = $wpdb->prepare( "post_type = %s AND post_status IN ('$post_status')", $r['post_type'] );
	}

	$orderby_array = array();
	$allowed_keys = array( 'author', 'post_author', 'date', 'post_date', 'title', 'post_title', 'name', 'post_name', 'modified',
		'post_modified', 'modified_gmt', 'post_modified_gmt', 'menu_order', 'parent', 'post_parent',
		'ID', 'rand', 'comment_count' );

	foreach ( explode( ',', $r['sort_column'] ) as $orderby ) {
		$orderby = trim( $orderby );
		if ( ! in_array( $orderby, $allowed_keys ) ) {
			continue;
		}

		switch ( $orderby ) {
			case 'menu_order':
				break;
			case 'ID':
				$orderby = "$wpdb->posts.ID";
				break;
			case 'rand':
				$orderby = 'RAND()';
				break;
			case 'comment_count':
				$orderby = "$wpdb->posts.comment_count";
				break;
			default:
				if ( 0 === strpos( $orderby, 'post_' ) ) {
					$orderby = "$wpdb->posts." . $orderby;
				} else {
					$orderby = "$wpdb->posts.post_" . $orderby;
				}
		}

		$orderby_array[] = $orderby;

	}
	$sort_column = ! empty( $orderby_array ) ? implode( ',', $orderby_array ) : "$wpdb->posts.post_title";

	$sort_order = strtoupper( $r['sort_order'] );
	if ( '' !== $sort_order && ! in_array( $sort_order, array( 'ASC', 'DESC' ) ) ) {
		$sort_order = 'ASC';
	}
	
	$padv_hide_from_pagelist = get_option('padv_hide_from_pagelist');
	if( !empty($padv_hide_from_pagelist) ){
		$where_post_type .= " AND post_parent NOT IN ($padv_hide_from_pagelist)";
	}

	$query = "SELECT * FROM $wpdb->posts $join WHERE ($where_post_type) $where ";
	$query .= $author_query;
	$query .= " ORDER BY " . $sort_column . " " . $sort_order ;

	if ( ! empty( $number ) ) {
		$query .= ' LIMIT ' . $offset . ',' . $number;
	}

	$pages = $wpdb->get_results($query);

	if ( empty($pages) ) {
		/** This filter is documented in wp-includes/post.php */
		$pages = apply_filters( 'get_pages', array(), $r );
		return $pages;
	}

	// Sanitize before caching so it'll only get done once.
	$num_pages = count($pages);
	for ($i = 0; $i < $num_pages; $i++) {
		$pages[$i] = sanitize_post($pages[$i], 'raw');
	}

	// Update cache.
	update_post_cache( $pages );

	if ( $child_of || $hierarchical ) {
		$pages = get_page_children($child_of, $pages);
	}

	if ( ! empty( $r['exclude_tree'] ) ) {
		$exclude = wp_parse_id_list( $r['exclude_tree'] );
		foreach ( $exclude as $id ) {
			$children = get_page_children( $id, $pages );
			foreach ( $children as $child ) {
				$exclude[] = $child->ID;
			}
		}

		$num_pages = count( $pages );
		for ( $i = 0; $i < $num_pages; $i++ ) {
			if ( in_array( $pages[$i]->ID, $exclude ) ) {
				unset( $pages[$i] );
			}
		}
	}

	$page_structure = array();
	foreach ( $pages as $page ) {
		$page_structure[] = $page->ID;
	}

	wp_cache_set( $cache_key, $page_structure, 'posts' );

	// Convert to WP_Post instances
	$pages = array_map( 'get_post', $pages );

	/**
	 * Filters the retrieved list of pages.
	 *
	 * @since 2.1.0
	 *
	 * @param array $pages List of pages to retrieve.
	 * @param array $r     Array of get_pages() arguments.
	 */
	return apply_filters( 'get_pages', $pages, $r );
}

class wppa_Walker_PageDropdown extends Walker {
	public $tree_type = 'page';
	public $db_fields = array( 'parent' => 'post_parent', 'id' => 'ID' );
	public function start_el( &$output, $page, $depth = 0, $args = array(), $id = 0 ) {
		$pad = str_repeat('&nbsp;', $depth * 3);
		if ( ! isset( $args['value_field'] ) || ! isset( $page->{$args['value_field']} ) ) {
			$args['value_field'] = 'ID';
		}
		$output .= "\t<option class=\"level-$depth\" value=\"" . esc_attr( $page->{$args['value_field']} ) . "\"";
		if ( !empty($args['selected']) and $page->ID == $args['selected'] )
			$output .= ' selected="selected"';
		$output .= '>';
		$title = $page->post_title;
		if ( '' === $title ) {
			$title = sprintf( __( '#%d (no title)' ), $page->ID );
		}
		$output .= $pad . esc_html( $title );
		$output .= "</option>\n";
	}
}

function wppa_walk_page_dropdown_tree() {
	$args = func_get_args();
	if ( empty($args[2]['walker']) ) // the user's options are the third parameter
		$walker = new wppa_Walker_PageDropdown;
	else
		$walker = $args[2]['walker'];

	return call_user_func_array(array($walker, 'walk'), $args);
}

function wppa_dropdown_pages(){
	global $wpdb;
	$args = array(
		'class' => 'wp-pages-advanced-multiple-fat-field', 
		'name' => 'page-parent', 
		'depth' => 0, 
		'echo' => 1, 
		'show_option_none' => 'No Parent',
		'option_none_value' => 'None', 
		'value_field' => 'ID', 
		'sort_column' => 'menu_order', 
		'sort_order' => 'asc'
	);	
	$defaults = array(
		'class' => 'wp-pages-advanced-multiple-fat-field', 
		'name' => 'page-parent', 
		'depth' => 0, 
		'echo' => 1, 
		'show_option_none' => 'No Parent',
		'option_none_value' => 'None', 
		'value_field' => 'ID', 
		'sort_column' => 'menu_order', 
		'sort_order' => 'asc'
	);
	$r = wp_parse_args( $args, $defaults );
	$pages = wppa_get_pages( $r );
	//echo $wpdb->last_query;
	
	$output = '';
	// Back-compat with old system where both id and name were based on $name argument
	if ( empty( $r['id'] ) ) {
		$r['id'] = $r['name'];
	}
	if ( ! empty( $pages ) ) {
		$class = '';
		if ( ! empty( $r['class'] ) ) {
			$class = " class='" . esc_attr( $r['class'] ) . "'";
		}
		$output = "<select name='" . esc_attr( $r['name'] ) . "'" . $class . " id='" . esc_attr( $r['id'] ) . "'>\n";
		if ( !empty($r['show_option_no_change']) ) {
			$output .= "\t<option value=\"-1\">" . $r['show_option_no_change'] . "</option>\n";
		}
		if ( !empty($r['show_option_none']) ) {
			$output .= "\t<option value=\"" . esc_attr( $r['option_none_value'] ) . '">' . $r['show_option_none'] . "</option>\n";
		}
		$output .= wppa_walk_page_dropdown_tree( $pages, $r['depth'], $r );
		$output .= "</select>\n";	
		if ( $r['echo'] ) {
			echo $output;
		}
		return $output;
	}	
	
}

