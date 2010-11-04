<?php

function make_folders( $destination )
{
	if ( empty( $destination ) || !strstr( $destination, "/" ) )
		return false;

	$dir_array = explode( "/", dirname( $destination ) );
	$dir = "";

	foreach ( $dir_array as $part )
	{
		$dir.=$part . '/';
		if ( !is_dir( $dir ) && strlen( $dir ) > 0 )
		{
			mkdir( $dir );
			chmod( $dir, 0777 );
		}
	}

	return $destination;
}

class Images
{

	/**
	 * Calls the PHPThumb methods.
	 *
	 * @param string $method
	 * @param mixed $args
	 * @return mixed
	 */
	function __call( $method, $args )
	{
		return call_user_func_array( array( $this->images, $method ), $args );
	}

	/**
	 * Resize an image.
	 *
	 * @param file $from
	 * @param file $to
	 * @param integer $width
	 * @param integer $height
	 * @param boolean $crop
	 * @return boolean
	 */
	public function resizeAndSave( $from, $to, $width, $height, $crop = false )
	{
		require_once dirname( __FILE__ ) . '/PHPThumb/ThumbLib.inc.php';

		$thumb = PhpThumbFactory::create( $from, array(
					'resizeUp' => true,
					'jpegQuality' => 75
						) );

		if ( false === $crop )
		{
			$thumb->resize( $width, $height );
		}
		else
		{
			$thumb->adaptiveResize( $width, $height );
		}

		$fileinfo = pathinfo( $to );

		$thumb->save( $to, $fileinfo['extension'] );

		return true;
	}

	/**
	 * Upload and resize an image.
	 *
	 * @param file $from
	 * @param file $to
	 * @param integer $width
	 * @param integer $height
	 * @param boolean $crop
	 * @return boolean
	 */
	public function uploadResizeAndSave( $post_file, $destination, $width, $height, $crop = false )
	{
		$old_name = $post_file['tmp_name'];
		$upload_info = pathinfo( $old_name );
		$new_name = $upload_info['dirname'] . '/' . $post_file['name'];

		move_uploaded_file( $old_name, $new_name );

		self::resizeAndSave( $new_name, $destination, $width, $height, $crop );

		return true;
	}
}