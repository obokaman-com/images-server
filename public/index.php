<?php

$root_path = realpath( dirname( __FILE__ ) ) . '/';

if ( !file_exists( $root_path . '../config.php' ) )
{
	echo "Ops! You should copy sample_config.php to config.php and fill all the params.";
	exit();
}
// Include configuration file.
include_once( $root_path . '../config.php' );

// Include image manipulation library.
include_once( $root_path . '../libs/Images.php' );

/**
 *  Step 1. Parse URL to extract information about the image.
 */
$parsed_uri = parse_url( $_SERVER['REQUEST_URI'] );
$request_uri = $parsed_uri['path'];

$url_info = pathinfo( $request_uri );

// If we detect "delete" as first part on URL, we look for all files
// matching the main name, to delete them.
if ( preg_match( '/^\/delete\/.+/', $url_info['dirname'] ) )
{
	$delete_pattern = $root_path . preg_replace( '/\/delete\//', '', $url_info['dirname'] ) . '/' . $url_info['filename'] . '*';
	$deleted_files = array( );
	foreach ( glob( $delete_pattern ) as $file )
	{
		$deleted_files[$file] = unlink( $file );
	}
	echo json_encode( array( 'deleted_images' => $deleted_files ) );
	exit();
}

$file['type'] = 'f';
if ( preg_match( "/.+_[" . $config['valid_image_sizes_string'] . "]$/", $url_info['filename'] ) )
{
	$file['type'] = preg_replace( "/.+_([" . $config['valid_image_sizes_string'] . "])$/i", "\\1", $url_info['filename'] );
}

$file['extension'] = ( isset( $url_info['extension'] ) ) ? $url_info['extension'] : '';
$file['original_location'] = ( $url_info['dirname'] != '/') ?
		$url_info['dirname'] . '/' . $url_info['filename'] :
		$url_info['filename'];

$file['original_location'] = preg_replace( "/(^\/|_$file[type]$)/i", "", $file['original_location'] ) . ".$file[extension]";
$file['temp_location'] = $root_path . 'volatile/' . uniqid() . '.' . $file['extension'];
$file['final_location'] = $root_path . preg_replace( "/(^\/)/i", "", $request_uri );

// We prepare the temporal folder.
make_folders( $file['temp_location'] );

// If we've defined a set of path conversions, we apply them to original_location.
if ( sizeof( $config['path_conversions'] ) >= 1 )
{
	foreach ( $config['path_conversions'] as $pattern => $conversion )
	{
		$patterns[] = $pattern;
		$conversions[] = $conversion;
	}

	$file['original_location'] = preg_replace( $patterns, $conversions, $file['original_location'] );
}

/**
 *  Step 2. Check if image extension is valid, and if original file exists on the origin. If not, 404 al canto.
 */
function check404()
{
	global $config;
	global $file;

	if ( sizeof( $config['default_images'] ) >= 1 )
	{
		foreach ( $config['default_images'] as $pattern => $default_image )
		{
			if ( preg_match( $pattern, $file['original_location'] ) )
			{
				$file['original_location'] = $default_image;
				return true;
			}
		}
	}
	header( "HTTP/1.0 404 Not Found" );
	echo "Ops! You're asking for somethign that doesn't exists, buddy.";
	exit();
}
// If Amazon S3 is used, we include the S3 library.
if ( 'AmazonS3' == $config['filesystem'] )
{
	include_once( $root_path . '../libs/S3.php' );

	$s3 = new S3( $config['AmazonS3']['access_key'], $config['AmazonS3']['secret_key'], false );

	if ( !in_array( strtolower( $file['extension'] ), $config['valid_image_extensions'] ) ||
			!$s3->getObjectInfo( $config['AmazonS3']['bucket_name'], $file['original_location'] ) )
	{
		check404();
	}

	// We download original image to a temp location, so we can handle and resize, if necessary.
	$s3->getObject( $config['AmazonS3']['bucket_name'], $file['original_location'], $file['temp_location'] );
	chmod( $file['temp_location'], 0777 );
}
elseif ( 'local' == $config['filesystem'] )
{
	if ( !in_array( strtolower( $file['extension'] ), $config['valid_image_extensions'] ) ||
			!file_exists( $config['local_files'] . $file['original_location'] ) )
	{
		check404();
	}

	// We copy the original file to volatile destination.
	copy( $config['local_files'] . $file['original_location'], $file['temp_location'] );
	chmod( $file['temp_location'], 0777 );
}

// We prepare the destination folders.
make_folders( $file['final_location'] );

$image = new Images();


/**
 * Step 3. If asking for a full image, simply copy it. If not, resize and eventually crop it.
 */
if ( $config['valid_image_sizes'][$file['type']]['name'] == 'full' )
{
	copy( $file['temp_location'], $file['final_location'] );
	chmod( $file['final_location'], 0777 );
}
else
{
	$image->resizeAndSave(
			$file['temp_location'],
			$file['final_location'],
			$config['valid_image_sizes'][$file['type']]['width'],
			$config['valid_image_sizes'][$file['type']]['height'],
			$config['valid_image_sizes'][$file['type']]['crop']
	);
}

// Cleaning the temporal image.
unlink( $file['temp_location'] );

/**
 * Step 4. Final check to be sure all went right. If not, 404 al canto.
 */
if ( file_exists( $file['final_location'] ) )
{
	$image_attributes = getimagesize( $file['final_location'] );
	header( 'Content-type: ' . $image_attributes['mime'] );
	echo file_get_contents( $file['final_location'] );
	exit();
}

header( "HTTP/1.0 404 Not Found" );
unlink( $file['final_location'] );
echo "Error saving image $origin_file on $storage_file";
