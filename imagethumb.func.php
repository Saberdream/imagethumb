<?php
function imagethumb($image_src, $image_dest = null, $max_size = 100, $expand = false, $square = false) {
	if( !file_exists($image_src) ) return FALSE;

	// get image infos
	$fileinfo = getimagesize($image_src);
	if( !$fileinfo ) return FALSE;

	$width		= $fileinfo[0];
	$height		= $fileinfo[1];
	$type_mime	= $fileinfo['mime'];
	$type_src	= str_replace('image/', '', $type_mime);
	$dst_x		= 0;
	$dst_y		= 0;
	
	if($image_dest) {
		$type = substr(strrchr($image_dest, '.'), 1);
		if($type == 'jpg')
			$type = 'jpeg';
	}
	else {
		$type = str_replace('image/', '', $type_mime);
	}
	
	if( !$expand && max($width, $height)<=$max_size && (!$square || ($square && $width==$height) ) )
	{
		// if the image si smaller than max_size
		if($image_dest)
		{
			return copy($image_src, $image_dest);
		}
		else
		{
			header('Content-Type: '. $type_mime);
			return (boolean) readfile($image_src);
		}
	}

	// calculate the new dimensions
	$ratio = $width / $height;

	if( $square )
	{
		$new_width = $dst_w = $new_height = $dst_h = $max_size;

		if( $ratio > 1 )
		{
			// Landscape
			$src_y = 0;
			$src_x = round( ($width - $height) / 2 );

			$src_w = $src_h = $height;
		}
		else
		{
			// Portrait
			$src_x = 0;
			$src_y = round( ($height - $width) / 2 );

			$src_w = $src_h = $width;
		}
	}
	elseif($expand)
	{
		// we need for an expansion to get the maximum dimensions of max_size
		$src_x = $src_y = 0;
		$src_w = $width;
		$src_h = $height;
		
		if(max($width, $height)<$max_size)
		{
			// the image is smaller than max_size and we need for a stretching
			$dst_x = ($max_size/2)-($width/2);
			$dst_y = ($max_size/2)-($height/2);
			$dst_w = $width;
			$dst_h = $height;
			$new_width = $max_size;
			$new_height = $max_size;
		}
		else
		{
			// if the image is bigger, we calculate the position manually
			if ( $ratio > 1 )
			{
				// Landscape
				$new_width = $dst_w = $new_height = $max_size;
				$dst_h = round( $max_size / $ratio );
				$dst_y = ($max_size/2)-($dst_h/2);
			}
			else
			{
				// Portrait
				$new_height = $dst_h = $new_width = $max_size;
				$dst_w = round( $max_size * $ratio );
				$dst_x = ($max_size/2)-($dst_w/2);
			}
		}
	}
	else
	{
		$src_x = $src_y = 0;
		$src_w = $width;
		$src_h = $height;

		if ( $ratio > 1 )
		{
			// Landscape
			$new_width = $dst_w = $max_size;
			$new_height = $dst_h = round( $max_size / $ratio );
		}
		else
		{
			// Portrait
			$new_height = $dst_h = $max_size;
			$new_width = $dst_w = round( $max_size * $ratio );
		}
	}

	// open the original image
	$func = 'imagecreatefrom' . $type_src;
	if( !function_exists($func) ) return FALSE;

	$image_src = $func($image_src);
	$new_image = imagecreatetruecolor( $new_width, $new_height );

	// transparence gestion for png
	if( $type=='png' )
	{
		imagealphablending($new_image,false);
		if( function_exists('imagesavealpha') )
			imagesavealpha($new_image,true);
	}

	// transparence gestion for gif
	elseif( $type=='gif' && imagecolortransparent($image_src)>=0 && imagecolorstotal($image_src) > imagecolortransparent($image_src) )
	{
		$transparent_index = imagecolortransparent($image_src);
		$transparent_color = imagecolorsforindex($image_src, $transparent_index);
		$transparent_index = imagecolorallocate($new_image, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
		imagefill($new_image, 0, 0, $transparent_index);
		imagecolortransparent($new_image, $transparent_index);
	}

	// image resizing
	imagecopyresampled(
		$new_image, $image_src,
		$dst_x, $dst_y, $src_x, $src_y,
		$dst_w, $dst_h, $src_w, $src_h
	);
	
	// image saving
	$func = 'image'. $type;
	if($image_dest)
	{
		$func($new_image, $image_dest);
	}
	else
	{
		header('Content-Type: '. $type_mime);
		$func($new_image);
	}

	// free the memory
	imagedestroy($new_image); 

	return true;
}