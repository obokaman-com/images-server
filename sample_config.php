<?php

/**
 * Main filesystem from where we will take the original images.
 *
 * Possible values: AmazonS3 | local
 */
$config['filesystem'] = 'local';

// If we choose 'amazon' as file system, we should fill the configuration params.
$config['AmazonS3']['bucket_name'] = 'bucket.name';
$config['AmazonS3']['access_key'] = 'key';
$config['AmazonS3']['secret_key'] = 'secret';

// If we choose local as filesystem, we should say the origin path
$config['local_files'] = realpath( dirname( __FILE__ ) ) . '/../dir_name/';

// Array with path conversions from original images location to final image locations.
// key = regular expression, value = replacement string
$config['path_conversions'] = array();

// Default images for 404.
// key = regular expression, value = default image to be used as original location.
$config['default_images'] = array();

/**
 * Define which image sizes will you serve from your images server.
 *
 * The 'key' is the character you'll find in the end of the URL.
 * Example: http://stc.domain.net/image_a.jpg
 */
$config['valid_image_sizes'] = array(
	'a' => array(
		'name' => 'avatar',
		'height' => 40,
		'width' => 40,
		'crop' => true
	),
	'm' => array(
		'name' => 'mini',
		'height' => 60,
		'width' => 60,
		'crop' => true
	),
	't' => array(
		'name' => 'thumbnail',
		'height' => 100,
		'width' => 100,
		'crop' => true
	),
	'd' => array(
		'name' => 'medium',
		'height' => 150,
		'width' => 150,
		'crop' => true
	),
	'b' => array(
		'name' => 'big',
		'height' => 1204,
		'width' => 1024,
		'crop' => false
	),
	'f' => array(
		'name' => 'full',
		'height' => false,
		'width' => false,
		'crop' => false
	),
);

/**
 *  Don't edit lines below.
 */

$config['valid_image_sizes_string'] = '';
foreach ( $config['valid_image_sizes'] as $image_size => $values )
{
	$config['valid_image_sizes_string'] .= $image_size;
}
$config['valid_image_extensions'] = array( 'jpg', 'jpeg', 'gif', 'png' );