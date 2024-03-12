<?php

function my_child_theme_styles(): void {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}

add_action( 'wp_enqueue_scripts', 'my_child_theme_styles' );
