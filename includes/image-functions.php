<?php
  
/*-------  The Resizer  -------*/ 
//Resizes image on disk. 
//
//11/09/08 Altered so that siz_resized is width only to facilitate new site specs (Trevor)

function resizeImage($picture_location, $picture_save, $size_resized=60) {
    $img_size = GetImageSize($picture_location); 
    $imageWidth  = $img_size[0]; 
    $imageHeight = $img_size[1]; 
    $width        = $img_size[0];
    $height         = $img_size[1]; 

    if      ($new_img = @ImageCreateFromPNG($picture_location))  $type = "PNG";
    else if ($new_img = @ImageCreateFromGIF($picture_location))  $type = "GIF";
    else if ($new_img = @ImageCreateFromJPEG($picture_location)) $type = "JPEG";
    else {
        @unlink($picture_location);
        echo "<b>That image type is not supported please use a PNG, JPEG, or GIF and try again.<b>"; 
        exit;
    }
    
    if($imageWidth > $size_resized || $imageHeight > $size_resized) { 
        $width  = $size_resized;
        $height = ($width/$imageWidth)*$imageHeight;
        $img = ImageCreateTrueColor($width, $height);         
        ImageCopyResampled($img, $new_img, 0, 0, 0, 0, $width, $height, $imageWidth, $imageHeight);
    }
    else {
        $img = ImageCreateTrueColor($width, $height);
        ImageCopy($img, $new_img, 0,0,0,0,$width,$height);
    }
    ImageJPEG($img,$picture_save,100); 
    ImageDestroy($img); 
    ImageDestroy ($new_img);
}

/*-------  Create Image Shadow ------*/
function shadowImage($picture_location, $picture_save, $shadowsize=8, $shade_color=87) {
    $img_size = GetImageSize($picture_location); 
    $width        = $img_size[0];
    $height         = $img_size[1]; 
    $new_width   = $width + $shadowsize;
    $new_height  = $height + $shadowsize;
    if      ($new_img = @ImageCreateFromPNG($picture_location))  $type = "PNG";
    else if ($new_img = @ImageCreateFromGIF($picture_location))  $type = "GIF";
    else if ($new_img = @ImageCreateFromJPEG($picture_location)) $type = "JPEG";
    else {
        @unlink($picture_location);
        echo "<b>That image type is not supported please use a PNG, JPEG, or GIF and try again.<b>"; 
        exit;
    }
    $img = ImageCreateTrueColor($width + $shadowsize, $height + $shadowsize); 
    //Fill around
    $color = $shade_color;
    for ($x=$width;$x<=$new_width;$x++) {
        for ($y=0;$y<=$new_height;$y++) {
            $rgb = imagecolorallocate($img,$color,$color,$color);
            imagesetpixel($img,$x,$y,$rgb);
        }
        $color += ((255 - $shade_color) / $shadowsize);
    }
    $color = $shade_color;
    for ($y=$height;$y<=$new_height;$y++) {
        for ($x=0;$x<=$new_width;$x++) {
            $rgb = imagecolorallocate($img,$color,$color,$color);
            imagesetpixel($img,$x,$y,$rgb);
        }
        $color += ((255 - $shade_color) / $shadowsize);
    }
    
    //Top Right
    $color1 = $shade_color;
    for ($y=$shadowsize-1;$y>=0;$y--) {
        $color2 = $color1;
        for ($x=$width;$x<=$new_width;$x++) {
            $rgb = imagecolorallocate($img,$color2,$color2,$color2);
            imagesetpixel($img,$x,$y,$rgb);
            $color2+= (255 - $color1) / $shadowsize;
        }
        $color1 += (255 - $shade_color) / $shadowsize;
    }
    //Bottom Left
    $color1 = $shade_color;
    for ($x=$shadowsize-1;$x>=0;$x--) {
        $color2 = $color1;
        for ($y=$height;$y<=$new_height;$y++) {
            $rgb = imagecolorallocate($img,$color2,$color2,$color2);
            imagesetpixel($img,$x,$y,$rgb);
            $color2+= (255 - $color1) / $shadowsize;
        }
        $color1 += (255 - $shade_color) / $shadowsize;
    }
    //Bottom Right
    $color1 = $shade_color;
    for ($x=$width;$x<=$new_width;$x++) {
        $color2 = $color1;
        for ($y=$height;$y<=$new_height;$y++) {
            $rgb = imagecolorallocate($img,$color2,$color2,$color2);
            imagesetpixel($img,$x,$y,$rgb);
            $color2+= (255 - $color1) / $shadowsize;
        }
        $color1 += (255 - $shade_color) / $shadowsize;
    }        
    
        
    ImageCopy($img, $new_img, 0,0,0,0,$width,$height);
    ImageJPEG($img,$picture_save,100); 
    ImageDestroy($img); 
    ImageDestroy ($new_img);
}
?>
