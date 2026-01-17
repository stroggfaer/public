<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
	/*-----------------------------------------------------------------------------------*/
	/* This file will be referenced every time a template/page loads on your Wordpress site
	/* This is the place to define custom fxns and specialty code
	/*-----------------------------------------------------------------------------------*/

// Define the version so we can easily replace it throughout the theme
define( 'NAKED_VERSION', 1.0 );

/*-----------------------------------------------------------------------------------*/
/*  Set the maximum allowed width for any content in the theme
/*-----------------------------------------------------------------------------------*/
if ( ! isset( $content_width ) ) $content_width = 900;

/*-----------------------------------------------------------------------------------*/
/* Add Rss feed support to Head section
/*-----------------------------------------------------------------------------------*/
add_theme_support( 'automatic-feed-links' );

/*-----------------------------------------------------------------------------------*/
/* Add post thumbnail/featured image support
/*-----------------------------------------------------------------------------------*/
add_theme_support( 'post-thumbnails' );

/*-----------------------------------------------------------------------------------*/
/* Register main menu for Wordpress use
/*-----------------------------------------------------------------------------------*/
register_nav_menus(
	array(
		'primary'	=>	__( 'Primary Menu', 'naked' ), // Register the Primary menu
		// Copy and paste the line above right here if you want to make another menu,
		// just change the 'primary' to another name
	)
);

/*-----------------------------------------------------------------------------------*/
/* Activate sidebar for Wordpress use
/*-----------------------------------------------------------------------------------*/
function naked_register_sidebars() {
	register_sidebar(array(				// Start a series of sidebars to register
		'id' => 'sidebar', 					// Make an ID
		'name' => 'Sidebar',				// Name it
		'description' => 'Take it on the side...', // Dumb description for the admin side
		'before_widget' => '<div>',	// What to display before each widget
		'after_widget' => '</div>',	// What to display following each widget
		'before_title' => '<h3 class="side-title">',	// What to display before each widget's title
		'after_title' => '</h3>',		// What to display following each widget's title
		'empty_title'=> '',					// What to display in the case of no title defined for a widget
		// Copy and paste the lines above right here if you want to make another sidebar,
		// just change the values of id and name to another word/name
	));
}
// adding sidebars to Wordpress (these are created in functions.php)
add_action( 'widgets_init', 'naked_register_sidebars' );

/*-----------------------------------------------------------------------------------*/
/* Enqueue Styles and Scripts
/*-----------------------------------------------------------------------------------*/
function naked_scripts()  {
    /*----Start CSS----*/
    wp_enqueue_style('element-ui-style', 'https://unpkg.com/element-ui/lib/theme-chalk/index.css', array(), NAKED_VERSION, 'all');
	// get the theme directory style.css and link to it in the header
	wp_enqueue_style('style.css', get_stylesheet_directory_uri() . '/style.css');
    wp_enqueue_style('font-awesome.min.css', get_stylesheet_directory_uri() . '/assets/font-awesome/css/font-awesome.min.css');
    wp_enqueue_style('font-awesome.min.css', get_stylesheet_directory_uri() . '/assets/font-awesome/css/font-awesome.min.css');
    wp_enqueue_style('main.min.css', get_stylesheet_directory_uri() . '/dist/css/main.min.css');
//    wp_enqueue_style('main.css', get_stylesheet_directory_uri() . '/assets/css/main.css');
    /*----End CSS----*/
    wp_enqueue_script( 'naked-jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js', array( 'jquery' ), NAKED_VERSION, true );
	// add theme scripts
    wp_enqueue_script( 'naked', get_template_directory_uri() . '/js/site.js', array(), NAKED_VERSION, true );

    /*----End JS----*/

}
add_action( 'wp_enqueue_scripts', 'naked_scripts' ); // Register this fxn and allow Wordpress to call it automatcally in the header

// Подключаем отдельный аякс для рубрики;
add_action('wp_enqueue_scripts', 'enqueue_ajax_pagination_scripts');

function enqueue_ajax_pagination_scripts() {
    wp_enqueue_script('ajax-pagination', get_template_directory_uri() . '/js/ajax-pagination.js', array('jquery'), null, true);
    wp_localize_script('ajax-pagination', 'ajaxpagination', array(
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
}

// Подключаем скрипты и стили для фильтра недвижимости
add_action('wp_enqueue_scripts', 'enqueue_property_filter_scripts');

function enqueue_property_filter_scripts() {
    wp_enqueue_script('property-filter', get_template_directory_uri() . '/js/property-filter.js', array('jquery'), null, true);
    wp_localize_script('property-filter', 'propertyFilter', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('property_filter_nonce')
    ));
}

// Админка часть;
require get_template_directory() . '/inc/admin/admin-function.php';
// Подключаем файл с функциями
require get_template_directory() . '/inc/frontend/template-function.php';
// Подключаем файл с функциями фильтрации
require get_template_directory() . '/inc/frontend/filter-functions.php';
// Подключаем файл с административными функциями фильтрации
require get_template_directory() . '/inc/admin/admin-filter-function.php';
// Подключаем файл с регистрацией CPT и таксономий
require get_template_directory() . '/inc/admin/pods-registration.php';
