<?php

/*
Plugin Name: WooCommerce Color Swatch
Description: Add WooCommerce Variation Swatches and Photos
Author: CactusThemes
Version: 1.0
Author URI: http://www.cactusthemes.com
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if(!class_exists('CT_Woo_Color_Swatch')) {	

	class CT_Woo_Color_Swatch {
		const VERSION = '1.0';
		
		const LANGUAGE_DOMAIN = 'cactus';
		
		// Plugin directory path
		public $plugin_path;
		
		private static $instance;
			
		public static function getInstance(){
			if(null == self::$instance){
				self::$instance = new CT_Woo_Color_Swatch();
			}
			
			return self::$instance;
		}
		
		private function __construct() {
			// constructor
			$this->includes();
			
			$woo_extension = CT_Woo_Extension_Color_Swatch::getInstance();
			$woo_extension->setup();
			
			$tax_meta = new CT_Woo_Tax_Meta();
			$tax_meta->init();
						
			add_action( 'init', array($this, 'init'));
		}
		
		function includes(){
			include 'includes/woo-extensions.php';
			include 'includes/tax_meta.php';
			
			
		}
		
		function init(){
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			
			$sizes = apply_filters('ct-color-swatch-option-size', array(20, 20));
			add_image_size('color-swatch', $sizes[0], $sizes[1]);
		}
		
		function enqueue_scripts(){
			wp_enqueue_script( 'ct-woo-colorswatch', plugins_url( 'assets/main.js', __FILE__ ), array('jquery'));
			wp_enqueue_style( 'ct-woo-colorswatch', plugins_url( 'assets/main.css', __FILE__ ));
		}
	}
	
	CT_Woo_Color_Swatch::getInstance();
}