# Setup child theme for WordPress

## Theme Overview

The key difference between "twentytwenty-child" and "twentytwentyfour-child" is "twentytwentyfour-child" is using block editor and "twentytwenty-child" is using classic editor.

## Available Child Themes

We have crafted four child themes to cater to a variety of needs:

1. astra-child
2. hello-elementor-child
3. twentytwenty-child
4. twentytwentyfour-child

These themes are ready for download and can be easily integrated into your WordPress site to enhance its appearance and functionality.

## Installation Guide

1. Download: Obtain the project files from our GitHub repository.
2. Locate Theme Folders:
   - Navigate to the /admin/views directory.
   - Choose either the "twentytwenty-child" or "twentytwentyfour-child" folder, or any of the available child themes based on your preference.
3. Prepare for Upload: Compress the selected folder into a .zip file, excluding unnecessary files (e.g., "*.DS_Store" and "__MACOSX") with the following command: `zip -r <YOUR_ZIP_FILE_NAME>.zip <THE_FOLDER_YOU_WANT_TO_ZIP> -x "*.DS_Store" -x "__MACOSX"`.
4. Upload to WordPress:
   - Access your WordPress dashboard.
   - Go to 'Appearance' > 'Themes'.
   - Select 'Add New' > 'Upload Theme', then choose and upload the .zip file.
5. Activate the Theme: Follow the on-screen instructions to activate the newly uploaded child theme.

## Creating Custom Child Themes

If you need a theme that's not provided in our selection, you can create your own following these guidelines:

### 1. Creating a functions.php File

The functions.php file allows you to enqueue the parent theme styles and add any custom PHP functions. Create a functions.php in your child theme directory with the following content:

```php
<?php

function my_child_theme_styles(): void {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
}

add_action('wp_enqueue_scripts', 'my_child_theme_styles');
```

This snippet ensures that your child theme loads the parent theme's stylesheet.

### 2. Defining the style.css File

The style.css file is crucial for defining your child theme's information and styling. At the top of this file, you should include a header comment that WordPress uses to identify the theme, and then you can add your custom CSS rules below this header. Here’s an example header:

```css
/*
Theme Name: Twenty Twenty-Four Child
Theme URI: https://wordpress.org/themes/twentytwentyfour/
Description: Twenty Twenty-Four Theme
Author: Murmurations
Author URI: https://murmurations.network/
Template: twentytwentyfour
Version: 1.0.0
Text Domain: twentytwentyfour-child
*/
```

- Template: This should match the directory name of the parent theme.
- Text Domain: Modify it from <YOUR_PARENT_THEME> to <YOUR_PARENT_THEME>-child. This is important for internationalization and ensuring that any translations are loaded properly.

### 3. Copying the screenshot.png

For a visual identifier in the WordPress dashboard, copy the screenshot.png file from the parent theme into your child theme directory. This image is what you see as the theme thumbnail in the WordPress admin area. You can also replace it with your own screenshot to reflect the look of your child theme.

### 5. Add Single Schema Templates

1. Template Creation: Begin by copying the single.php file from the parent theme. Rename it to single-<schema_name>.php (e.g., single-offers-wants-prototype-schema.php). This custom file will allow our plugin to display the data appropriately based on the schema type.
2. Required Files: Ensure to create and customize the following files based on the single.php template, adapting them to suit different schema types:
   - single-organization-schema.php
   - single-people-schema.php
   - single-offers-wants-prototype-schema.php

3. Incorporate Shortcodes: Use the provided shortcodes to insert dynamic content into your custom templates. The following shortcode is what we use by default to insert it into the single schema template, you can put the shortcode in the appropriate place in your custom template:

   - single-organization-schema.php

      ```php
      echo do_shortcode( '[murmurations_data title="Name" path="name"]' );
      echo do_shortcode( '[murmurations_data title="Nickname" path="nickname"]' );
      echo do_shortcode( '[murmurations_data title="Description" path="description"]' );
      echo do_shortcode( '[murmurations_data title="Primary URL" path="primary_url"]' );
      echo do_shortcode( '[murmurations_data title="Tags" path="tags"]' );
      echo do_shortcode( '[murmurations_data title="URLs" path="urls"]' );
      echo do_shortcode( '[murmurations_data title="Relationships" path="relationships"]' );
      ```

   - single-people-schema.php

      ```php
      echo do_shortcode( '[murmurations_data title="Name" path="name"]' );
      echo do_shortcode( '[murmurations_data title="Nickname" path="nickname"]' );
      echo do_shortcode( '[murmurations_data title="Primary URL" path="primary_url"]' );
      echo do_shortcode( '[murmurations_data title="Tags" path="tags"]' );
      echo do_shortcode( '[murmurations_data title="Image" path="image"]' );
      echo do_shortcode( '[murmurations_data title="Knows Language" path="knows_language"]' );
      echo do_shortcode( '[murmurations_data title="Contact Details" path="contact_details"]' );
      echo do_shortcode( '[murmurations_data title="Telephone" path="telephone"]' );
      echo do_shortcode( '[murmurations_data title="Country Name" path="country_name"]' );
      echo do_shortcode( '[murmurations_data title="Country ISO 3166" path="country_iso_3166"]' );
      echo do_shortcode( '[murmurations_data title="Geolocation" path="geolocation"]' );
      ```

   - single-offers-wants-prototype-schema.php

      ```php
      echo do_shortcode( '[murmurations_data title="Exchange Type" path="exchange_type"]' );
      echo do_shortcode( '[murmurations_data title="Item Type" path="item_type"]' );
      echo do_shortcode( '[murmurations_data title="Tags" path="tags"]' );
      echo do_shortcode( '[murmurations_data title="Title" path="title"]' );
      echo do_shortcode( '[murmurations_data title="Description" path="description"]' );
      echo do_shortcode( '[murmurations_data title="Geolocation Scope" path="geolocation"]' );
      echo do_shortcode( '[murmurations_data title="Geographic Scope" path="geographic_scope"]' );
      echo do_shortcode( '[murmurations_data title="Contact Details" path="contact_details.contact_form"]' );
      echo do_shortcode( '[murmurations_data title="Transaction Type" path="transaction_type"]' );
      ```

By following these detailed steps, you can create child themes for WordPress, and you can go to [Installation Guide](#installation-guide) to upload and activate your custom child theme.
