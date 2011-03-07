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
function breadcrumb($post=null, $sep='<span>/</span>', $add_home='Home', $post_cat_to_page=false, $post_type='page', $needle=null) {	
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
					WHERE wpost.post_type='$post_type'
					AND (wpost.post_status='publish' OR wpost.post_status='private')
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
		if(isset($post->ancestors)) $ancestors = bc_parse_parents($post, $needle); else $ancestors = bc_get_parents($post, $needle);

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
					$breadcrumb.= bc_mk_link($crumb['link'], $add, array('alt' => $add), false) . $sep;
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

if(!function_exists("bc_parse_parents")) {
function bc_parse_parents($post=null, $needle = null, $reverse = true) {
	$key = null;
	if($post && isset($post->ancestors) && $post->ancestors) {
		$ancestors = $post->ancestors;
		$total = count($ancestors);
		if($reverse) {
			$ancestors = array_reverse($ancestors);
			$ancestors[] = $post->ID;
		}
		if($needle) {
			$key = array_search($needle, $ancestors) + 1;
			if($key) $ancestors = array_slice($ancestors, $key, $total);
		}
		return $ancestors;
	} else {
		$ancestors = array_reverse(bc_get_parents($post, $needle));
		return $ancestors;
	}
}
}


if(!function_exists("bc_mk_link")) {
function bc_mk_link($url=null, $title=null, $options=array(), $echo = true) {
	if(!stristr($url, 'http://')) $url = $this->site_url() . $url;
	if(!$options['href']) $options['href'] = $url;
	if($title) $_title = $title; else $_title = $url;
	$return = bc_tag('a', $options, true, $_title);
	if($echo) echo $return; else return $return;
}
}


if(!function_exists("bc_tag")) {
function bc_tag($tag=null, $options=array(), $close=false, $content=null) {
	if($tag) {
		if(isset($options['content'])) {
			$content = $options['content'];
			unset($options['content']);
		}
		
		$_options = bc_make_options($options);
		if($_options) $_options = ' ' . $_options;
		
		$build = '<' . $tag . $_options;
		if($close) $build.= '>' . $content . '</' . $tag . '>';
		else $build.= ' />';
		return $build;
	} else {
		return false;
	}
}
}


if(!function_exists("bc_make_options")) {
function bc_make_options($options=array()) {
	$_options = null;
	foreach($options as $k => $v) $_options.= " $k='$v'";
	return $_options;
}
}


if(!function_exists("bc_get_parents")) {
function bc_get_parents($post = null, $needle=null, $keep_needle = true, $results = array(), $loop = true) {
	global $wpdb;
	if($post) {
		$results[] = $post->ID;
		$result = $wpdb->get_results("SELECT wpost.ID, wpost.post_parent FROM {$wpdb->posts} wpost WHERE wpost.ID={$post->post_parent} ORDER BY wpost.menu_order asc");
		if(isset($result[0]->ID) && $loop) {
			if($needle) if($result[0]->post_parent == $needle) $loop = false;
			if(!$result[0]->post_parent) $loop = false;
			$results = bc_get_parents($result[0], $needle, $keep_needle, $results, $loop);
		}
		if(!$loop) if($keep_needle && ($post->post_parent == $needle)) $results[] = $needle;
	}
	return $results;
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
		$page = $wpdb->get_results("SELECT * FROM {$wpdb->posts} wpost WHERE (wpost.post_status='publish' OR wpost.post_status='private') AND wpost.post_name='{$slug}'");
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