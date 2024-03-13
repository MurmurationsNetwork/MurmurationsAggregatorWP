<?php

function my_child_theme_styles() {
	wp_enqueue_style( 'parent-style-min', get_template_directory_uri() . '/style.min.css' );
	wp_enqueue_style( 'parent-theme-min', get_template_directory_uri() . '/theme.min.css' );
}

add_action( 'wp_enqueue_scripts', 'my_child_theme_styles' );
