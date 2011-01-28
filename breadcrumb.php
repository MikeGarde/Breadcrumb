<?php
/*
Plugin Name: Breadcrumb
Plugin URI: http://wordpressfire.com/
Description: Breadcrumb maker and builder
Author: Philip Joyner
Version: 0.1
Author URI: http://philipjoyner.com/
Updated: 2010-11-18
*/
/*
Creates breadcrumb for a given post
var 1 - $post - is the full post
var 2 - $sep - separater between breadcrumb pages
var 3 - $add_home - Add a link to the homepage into the breadcrumb from given title
var 4 - $needle - ID of page where breadcrumb should stop (not required)
*/
if(!function_exists("breadcrumb")) {
function breadcrumb($post=null, $sep='<span>/</span>', $add_home='Home', $post_cat_to_page=false, $needle=null) {	
	if(!$post) return false;
	global $wpdb;
		
	$breadcrumb = null;
	$crumbs = array();
	$hp = $post;
		
	$find = false;
	if($post_cat_to_page=true) {
		$cats = get_the_category();
		if($cats) {
			$bread = false;
			foreach($cats as $cat) {
				$find_post = $wpdb->query("
					SELECT $fields FROM {$wpdb->posts} wpost
					WHERE wpost.post_type='page'
					AND wpost.post_status='publish'
					AND wpost.post_name='{$cat->slug}'
				");
				if(isset($find_post[0])) {
					$post = $find_post[0];
					$break = true;
				}
				if($break) break;
			}
		}
	}
		
	if($post) {
		$ancestors = array();
		if(isset($post->ancestors)) $ancestors = $this->__parse_parents($post, $needle); else $ancestors = $this->__get_parents($post, $needle);

		if($add_home) $crumbs[] = array('title' => $add_home, 'link' => get_bloginfo('siteurl'));
		foreach($ancestors as $a) {
			$find = $wpdb->get_results("SELECT wpost.ID, wpost.post_title FROM {$wpdb->posts} wpost WHERE wpost.ID=$a ORDER BY menu_order asc");
			if(isset($find[0])) $crumbs[] = array('title' => $find[0]->post_title, 'link' => get_permalink($find[0]->ID));
		}

		if($crumbs) {
			$total = count($crumbs) - 1;
			foreach($crumbs as $k => $crumb) {
				$add = $crumb['title'];
				if($k == $total && !$find_post)
					$breadcrumb.= $add . $sep;
				else
					$breadcrumb.= $this->mk_link($crumb['link'], $add, array('alt' => $add), false) . $sep;
			}
			if($find_post) {
				$breadcrumb.= $hp->post_title . $sep;
			}
			$breadcrumb = substr($breadcrumb, 0, (strlen($sep) * -1));
		}
		echo $breadcrumb;
	}
}
}
	

if(!function_exists("breadcrumb_build")) {	
function breadcrumb_build($slugs = array(), $last=null, $sep='<span>/<span>', $home=false, $echo=true) {
	if(!$slugs || !is_array($slugs)) return;
	global $dev;
	global $wpdb;
	
	$breadcrumb = null;
	if($home) $breadcrumb.= '<a href="' . get_bloginfo('url') . '">' . $home . '</a>' . $sep;
	
	foreach($slugs as $slug) {
		$page = false;
		$page = $wpdb->get_results("SELECT * FROM {$wpdb->posts} wpost WHERE wpost.post_status='publish' AND wpost.post_name='{$slug}'");
		if(isset($page[0])) $page = $page[0];
		if($page) $breadcrumb.= '<a href="' . get_permalink($page->ID) . '">' . $page->post_title . '</a>' . $sep;
	}
	
	if($last) $breadcrumb.= $last . $sep;
	
	if($breadcrumb) {
		$breadcrumb = substr($breadcrumb, 0, (strlen($sep) * -1));
		if($echo) echo $breadcrumb;
		else return $breadcrumb;
	}
}
}
?>