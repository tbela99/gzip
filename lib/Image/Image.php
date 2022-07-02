<?php
/**
 * @file class.Image.php
 * @brief Contiene la classe Image per il trattamento di immagini
 *
 * @copyright 2014 Otto srl (http://www.opensource.org/licenses/mit-license.php) The MIT License
 * @author abidibo abidibo@gmail.com
 * @author Thierry Bela.
 *
 * extended to add face detection and namespacing, better crop area detection
 */

 namespace Image;

 use Exception;
 use InvalidArgumentException;
 use Potracio\Potracio;

 defined('_JEXEC') or die;

 // php 8.1
 if (!defined('IMAGETYPE_AVIF') && function_exists('imagecreatefromavif')) {

	 define('IMAGETYPE_AVIF', 'avif');
 }

 // php 7.1
 if (!defined('IMAGETYPE_WEBP') && function_exists('imagecreatefromwebp')) {

	 define('IMAGETYPE_WEBP', 'webp');
 }

/**
 * @brief Classe per il trattamento di immagini
 *
 * @copyright 2014 Otto srl (http://www.opensource.org/licenses/mit-license.php) The MIT License
 * @author abidibo abidibo@gmail.com
 */
class Image {

    private $_abspath;
    private $_image = null;
    private $_image_type;
	
	const CROP_DEFAULT = 1;
	const CROP_CENTER = 2;
	const CROP_ENTROPY = 3;
	const CROP_FACE = 4;
	
    /**
     * @brief Costruttore
     * @param string $abspath percorso assoluto del file
     */

    public function __construct($abspath = null) {

        if(!is_null($abspath)) {
			
			$this->load($abspath);
        }
	}

	public function getExtension() {

    	return image_type_to_extension ($this->_image_type, false);
	}

	public function getMimetype() {

		return image_type_to_mime_type($this->_image_type);
	}
	
	/**
     * Load image
     *
     * @param string $file
     * @return Image this instance
	 * @throw \InvalidArgumentException
     */
	public function load ($file) {

        if(!is_file($file)) {

            throw new Exception('File doesn\'t exists', 404);
        }		
		
        $image_info = getimagesize($file);


		if($image_info === false) {

			throw new Exception(sprintf('unsupported image format: %s', $file), 400);
		}


		$this->_abspath = $file;
        $this->_image_type = $image_info[2];
		
        if($this->_image_type == IMAGETYPE_JPEG) {
            $this->_image = imagecreatefromjpeg($file);
        }
        elseif($this->_image_type == IMAGETYPE_GIF) {
            $this->_image = imagecreatefromgif($file);
        }
        elseif($this->_image_type == IMAGETYPE_PNG) {
            $this->_image = imagecreatefrompng($file);
        }
        elseif(function_exists('imagecreatefromwebp') && strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'webp') {
            $this->_image = imagecreatefromwebp($file);
			$this->_image_type = IMAGETYPE_WEBP;
        }
		elseif(function_exists('imagecreatefromavif') && strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'avif') {
			$this->_image = imagecreatefromavif($file);
			$this->_image_type = IMAGETYPE_AVIF;
		}
        else {
			
			$image_string = file_get_contents($file);
			$this->_image = imagecreatefromstring($image_string);
			
			if (!$this->_image) {
				
            	throw new InvalidArgumentException ('Unsupported image type: '.$file, 400);
			}

			$image_size = getimagesizefromstring($image_string);
			$this->_image_type = $image_size[2];
        }

        imagealphablending($this->_image, false);
        imagesavealpha($this->_image, true);

        return $this;
	}

	public function __clone() {

		if ($this->_image) {

			$this->_image = $this->cloneImage($this->_image);
		}
	}
	
    /**
     * @brief Ritorna la larghezza dell'immagine
     * @return int larghezza immagine in px
     */
    public function getWidth() {
        return imagesx($this->_image);
    }

    /**
     * @brief Ritorna l'altezza dell'immagine
     * @return int altezza immagine in px
     */
    public function getHeight() {
        return imagesy($this->_image);
    }

    /**
     * @brief Ritorna la resource dell'immagine
     * @return resource immagine
     */
    public function getResource() {
        return $this->_image;
    }

    /**
     * 
     *
     * @param resource $image
     * @return void
     */
    public function setResource($image) {
        $this->_image = $image;
        return $this;
    }

    /**
     * @brief Salva l'immagine su filesystem
     * @param string $abspath percorso (default il percorso originale dell'immagine)
     * @param int $compression compressione, default 75
     * @param string $permission permessi
     * @return void
     */
    public function save($abspath = null, $compression=75) {
   
		$type = $this->_image_type;
	    $extension = null;
		
		if (!is_null($abspath)) {
        
            $extension = strtolower(pathinfo($abspath, PATHINFO_EXTENSION));

			switch($extension) {
			
				case 'jpg':
				
					$type = IMAGETYPE_JPEG;
					break;
				case 'png':
				
					$type = IMAGETYPE_PNG;
					break;
				case 'gif':
				
					$type = IMAGETYPE_GIF;
					break;
				case 'webp':
                
                    if (defined('IMAGETYPE_WEBP')) {
                            
                        $type = IMAGETYPE_WEBP;
					    break;
                    }
				case 'avif':

					if (defined('IMAGETYPE_AVIF')) {

						$type = IMAGETYPE_AVIF;
						break;
					}
			}
		}

		imageinterlace($this->_image, true);

        if($type == IMAGETYPE_JPEG) {
            imagejpeg($this->_image, $abspath, $compression);
        }
        elseif($type == IMAGETYPE_GIF) {
            imagegif($this->_image, $abspath);
        }
        elseif($type == IMAGETYPE_PNG) {
            imagepng($this->_image, $abspath);
        }
		elseif($type == IMAGETYPE_WEBP) {
			imagewebp($this->_image, $abspath, $compression);
		}
		elseif($type == IMAGETYPE_AVIF) {
			imageavif($this->_image, $abspath, $compression);
		}

        else {

	        throw new InvalidArgumentException('Unsupported file type '.$extension, 400);
        }

        return $this;
    }

	protected function getLuminance($pixel){
		$pixel = sprintf('%06x',$pixel);
		$red = hexdec(substr($pixel,0,2))*0.30;
		$green = hexdec(substr($pixel,2,2))*0.59;
		$blue = hexdec(substr($pixel,4))*0.11;
		return $red+$green+$blue;
	}
	
	public function detectEgdes() {

		$image = $this->cloneImage($this->_image);

		$width = imagesx($image);
		$height = imagesy($image);

		imagefilter($image,IMG_FILTER_GAUSSIAN_BLUR);
		$destination = imagecreatetruecolor($width, $height);

		//
		$transparency = imagecolorallocatealpha($destination, 0, 0, 0, 127);
		imagefill($destination, 0, 0, $transparency);

				// looping through ALL pixels!!
		for($x=1;$x<$width-1;$x++){
			for($y=1;$y<$height - 1;$y++){
				// getting gray value of all surrounding pixels
				$pixel_up = $this->getLuminance(imagecolorat($image,$x,$y-1));
				$pixel_down = $this->getLuminance(imagecolorat($image,$x,$y+1)); 
				$pixel_left = $this->getLuminance(imagecolorat($image,$x-1,$y));
				$pixel_right = $this->getLuminance(imagecolorat($image,$x+1,$y));
				$pixel_up_left = $this->getLuminance(imagecolorat($image,$x-1,$y-1));
				$pixel_up_right = $this->getLuminance(imagecolorat($image,$x+1,$y-1));
				$pixel_down_left = $this->getLuminance(imagecolorat($image,$x-1,$y+1));
				$pixel_down_right = $this->getLuminance(imagecolorat($image,$x+1,$y+1));
				
				// appliying convolution mask
				$conv_x = ($pixel_up_right+($pixel_right*2)+$pixel_down_right)-($pixel_up_left+($pixel_left*2)+$pixel_down_left);
				$conv_y = ($pixel_up_left+($pixel_up*2)+$pixel_up_right)-($pixel_down_left+($pixel_down*2)+$pixel_down_right);
				
				// calculating the distance
				#$gray = sqrt($conv_x*$conv_x+$conv_y+$conv_y);
				$gray = abs($conv_x)+abs($conv_y);
				
				// inverting the distance not to get the negative image                
				$gray = 255-$gray;
				
				// adjusting distance if it's greater than 255 or less than zero (out of color range)
				if($gray > 255){
					$gray = 255;
				}
				if($gray < 0){
					$gray = 0;
				}
				
				// creation of the new gray
				$new_gray  = imagecolorallocate($destination,$gray,$gray,$gray);
				
				// adding the gray pixel to the new image        
				imagesetpixel($destination,$x,$y,$new_gray);            
			}
		}

	//	imagetruecolortopalette ($destination, false , 8);
		return (new Image())->setResource($destination);
	}

	protected function getDominantColor($image) {

		$width = imagesx($image);
		$height = imagesy($image);

		$total = 0;
		$rTotal = 0;
		$gTotal = 0;
		$bTotal = 0;
		
		for ($x=0;$x<$width;$x++) {
			for ($y=0;$y< $height;$y++) {

				$rgb = imagecolorat($image,$x,$y);
				$r   = ($rgb >> 16) & 0xFF;
				$g   = ($rgb >> 8) & 0xFF;
				$b   = $rgb & 0xFF;
				$rTotal += $r;
				$gTotal += $g;
				$bTotal += $b;
				$total++;
			}
		}

		return [round($rTotal / $total), round($gTotal / $total), round($bTotal / $total)];
	}

	public function toHexColor ($color) {

		return '#'.\dechex($color[0]).\dechex($color[1]).\dechex($color[2]);
	}

	public function toSvg() {

		$svg =  new Potracio(['fill' => $this->toHexColor($this->getDominantColor($this->_image))]);

		$svg->loadImageFromResource($this->_image);
		$svg->process();

		return $svg->getSVG(1);
	}
	
    public function resizeAndCrop($width, $height = null, $method = Image::CROP_DEFAULT, $x0 = 0, $y0 = 0, $options = array()) {

    	// if height is null then resize according to the width, no need to crop
        if (\is_null($height)) {

            $this->setSize($width, $height, $options);
            return $this;
        }

        switch ($method) {

            case Image::CROP_ENTROPY:

                $this->cropEntropy($width, $height, $options);
                break;
                
            case Image::CROP_FACE:

                $this->cropFace($width, $height, $options);
                break;
                
            case Image::CROP_CENTER:

                $this->cropCenter($width, $height, $options);
                break;
                
            default:
            
                $this->crop($width, $height, $x0, $y0, $options);
                break;
        }

        return $this;
    }

    /**
     * @brief Crop dell'immagine con larghezza, altezza e punto iniziali dati
     * @param int $width Larghezza crop
     * @param int $height Altezza crop
     * @param int $xo Coordinata x punto top left di taglio
     * @param int $yo Coordinata y punto top left di taglio
     * @param array $options Opzioni.
     * @return void
     */
    public function crop($width, $height, $x0, $y0, $options = array()) {
		
		
		$size = $this->getBestFit($width, $height);
				
		$x0 = max(0, $x0 - $size['width'] / 2);
		$y0 = max(0, $y0 - $size['height'] / 2);
				
		$x0 = min($x0, $this->getWidth() - $size['width']);
		$y0 = min($y0, $this->getHeight() - $size['width']);

        $this->_image = $this->cropImage($this->_image, $size['width'], $size['height'], $x0, $y0, $options);
        return $this->setSize($width, $height, $options);
        
    }

    /**
     * @brief Crop centrale dell'immagine con larghezza e altezza dati
     * @param int $width Larghezza crop
     * @param int $height Altezza crop
     * @param array $options Opzioni.
     * @return void
     */
    public function cropCenter($width, $height, $options = array()) {
		
		$size = $this->getBestFit($width, $height);
						
        $x0 = ($this->getWidth() - $size['width'])/2;
        $y0 = ($this->getHeight() - $size['height'])/2;
				
        $this->_image = $this->cropImage($this->_image, $size['width'], $size['height'], $x0, $y0, $options);
		return $this->setSize($width, $height, $options);
    }
	
	public function getBestFit($width, $height) {

			// get best crop size 
			$scale = $width > $height ? $height / $width : $width / $height;
			
		//	if ($crX != $crY) {
			$side = min($this->getWidth(), $this->getHeight()); // * $scale;

				// crop the image with our region inside
			$newWidth = $width > $height ? $side : $side * $scale;
			$newHeight = $width > $height ? $side * $scale : $side;

			return ['width' => $newWidth, 'height' => $newHeight];
}

	public function cropFace($width, $height) {

		$this->detectFaces();

		if (empty($this->faceRects)) {

			$this->_image = $this->cropImageEntropy($this->_image, $width, $height);
		}

		else {

            // x, y width, height
			$x0 = null;
			$y0 = null;
			$x1 = null;
			$y1 = null;

			foreach($this->faceRects as $rect) {

				if (is_null($x0) || $x0 > $rect['x']) {

					$x0 = $rect['x'];
				}

				if (is_null($y0) || $y0 > $rect['y']) {

					$y0 = $rect['y'];
				}

				if (is_null($x1) || $x1 < $rect['x'] + $rect['width']) {

					$x1 = $rect['x'] + $rect['width'];
				}

				if (is_null($y1) || $y1 < $rect['y'] + $rect['height']) {

					$y1 = $rect['y'] + $rect['height'];
				}
			}


            $rect = ['x' => $x0, 'width' => $x1 - $x0, 'y' => $y0, 'height' => $y1 - $y0];

			if ($rect['y'] > 500) {

				$rect['y'] -= 500;
			//	$rect['width'] += 200;
			}

			if ($rect['y'] > 300) {

				$rect['y'] -= 300;
			//	$rect['width'] += 200;
			}

			else if ($rect['y'] > 200) {

				$rect['y'] -= 200;
			//	$rect['width'] += 200;
			}
			else {

				$rect['y'] = 0;
			//	$rect['width'] += 200;
			}


			$size = $this->getBestFit($width, $height);

			$x0 = max(0, $rect['x'] + ($rect['width'] - $size['width']) / 2);
			$y0 = max(0, $rect['y'] + ($rect['height'] - $size['height']) / 2);

			$x0 = min($x0, $this->getWidth() - $size['width']);
			$y0 = min($y0, $this->getHeight() - $size['width']);

            // crop and resize
            $this->_image = $this->cropImage($this->_image, $size['width'], $size['height'], $x0, $y0);
			// compute rect that contains all the faces
		}

		return $this->setSize($width, $height);
	}

	/**
	 * @brief Crop dell'immagine con larghezza e altezza dati nella zona a massima entropia
	 * @param int $width Larghezza crop
	 * @param int $height Altezza crop
	 * @return void
	 */
    public function cropEntropy($width, $height) {
        $this->_image = $this->cropImageEntropy($this->_image, $width, $height);
		return $this->setSize($width, $height);
    }

	protected function detectFaces() {

		if (empty($this->stages)) {

			$this->initClassifier();
		}

		$this->faceRects = [];

		$width = $this->getWidth();
		$height = $this->getHeight();

		$maxScale = min($width/$this->classifierSize[0], $height/$this->classifierSize[1]);
		$grayImage = array_fill(0, $width, array_fill(0, $height, null));
		$img = array_fill(0, $width, array_fill(0, $height, null));
		$squares = array_fill(0, $width, array_fill(0, $height, null));

		for($i = 0; $i < $width; $i++)
		{
			$col=0;
			$col2=0;
			for($j = 0; $j < $height; $j++)
			{
				$colors = imagecolorsforindex($this->_image, imagecolorat($this->_image, $i, $j));

				$value = (30*$colors['red'] +59*$colors['green'] +11*$colors['blue'])/100;
				$img[$i][$j] = $value;
				$grayImage[$i][$j] = ($i > 0 ? $grayImage[$i-1][$j] : 0) + $col + $value;
				$squares[$i][$j]=($i > 0 ? $squares[$i-1][$j] : 0) + $col2 + $value*$value;
				$col += $value;
				$col2 += $value*$value;
			}
		}

		$baseScale = 2;
		$scale_inc = 1.25;
		$increment = 0.1;
//		$min_neighbors = 3;

		for($scale = $baseScale; $scale < $maxScale; $scale *= $scale_inc)
		{
			$step = (int)($scale*24*$increment);
			$size = (int)($scale*24);

			for($i = 0; $i < $width-$size; $i += $step)
			{
				for($j = 0; $j < $height-$size; $j += $step)
				{
					$pass = true;
					$k = 0;
					foreach($this->stages as $s)
					{

						if(!$s->pass($grayImage, $squares, $i, $j, $scale))
						{
							$pass = false;
							//echo $k."\n";
							break;
						}
						$k++;
					}
					if($pass)
					{
						$this->faceRects[]= array("x" => $i, "y" => $j, "width" => $size, "height" => $size);
					}
				}
			}
		}
	}

	protected function initClassifier($classifierFile = __DIR__.'/haarcascade_frontalface_default.xml')
	{
		$xmls = file_get_contents($classifierFile);
		$xmls = preg_replace("/<!--[\S|\s]*?-->/", "", $xmls);
		$xml = simplexml_load_string($xmls);
				
		$this->classifierSize = explode(" ", strval($xml->children()->children()->size));
		$this->stages = array();
		
		$stagesNode = $xml->children()->children()->stages;
		
		foreach($stagesNode->children() as $stageNode)
		{
			$stage = new Stage(floatval($stageNode->stage_threshold));
				
			foreach($stageNode->trees->children() as $treeNode)
			{
				$feature = new Feature(floatval($treeNode->_->threshold), floatval($treeNode->_->left_val), floatval($treeNode->_->right_val), $this->classifierSize);
				
				foreach($treeNode->_->feature->rects->_ as $r)
				{
					$feature->add(Rect::fromString(strval($r)));
				}
				
				$stage->features[] = $feature;
			}
			
			$this->stages[] = $stage;
		}		
	}

	/**
	 * @brief Resize dell'immagine alle dimensioni fornite
	 * @param resource $image resource dell'immagine
	 * @param int $width Larghezza della thumb
	 * @param int $height Altezza della thumb
	 * @return resource immagine ridimensionata
	 */
    public function resizeImage($image, $width, $height) {

        $new_image = imagecreatetruecolor($width, $height);

        // transparent background
		$transparency = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
		imagefill($new_image, 0, 0, $transparency);

        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));

        return $new_image;
    }

	/**
	 * @brief Resize dell'immagine alle dimensioni fornite
	 * @param int $width Larghezza della thumb
	 * @param int $height Altezza della thumb
	 * @return Image immagine ridimensionata
	 */
    public function setSize($width, $height = null) {

        if (is_null($height)) {

            $height = $this->getHeight() * $width / $this->getWidth();
        }

        if ($width == $this->getWidth() && $height == $this->getHeight()) {

            return $this;
        }
 
        $this->_image = $this->resizeImage($this->_image, $width, $height);
        return $this;
    }

    /**
     * @brief Crop dell'immagine nella parte con maggiore entropia
     * @param resource $image resource immagine
     * @param int $width larghezza crop
     * @param int $height altezza crop
     * @param array $options Opzioni.
     * @return resource immagine
     */
    private function cropImageEntropy($image, $width, $height) {

        $clone = $this->cloneImage($image);

        imagefilter($clone, IMG_FILTER_EDGEDETECT);
        imagefilter($clone, IMG_FILTER_GRAYSCALE);

        $this->blackThresholdImage($clone, 30, 30, 30);
        imagefilter($clone,  IMG_FILTER_SELECTIVE_BLUR);
        $left_x = $this->slice($image, $width, 'h');
        $top_y = $this->slice($image, $height, 'v');

		$size = $this->getBestFit($width, $height);

		$left_x = max(0, $left_x - $size['width'] / 2);
		$top_y = max(0, $top_y - $size['height'] / 2);

		if ($top_y + $size['height'] > $this->getHeight()) {

			$top_y = $this->getHeight() - $size['height'];
		}

		$crop = imagecreatetruecolor($size['width'], $size['height']);

		//
		$transparency = imagecolorallocatealpha($crop, 0, 0, 0, 127);
		imagefill($crop, 0, 0, $transparency);

		imagecopy($crop, $image, 0, 0, $left_x, $top_y, $size['width'], $size['height']);

		return $crop;
    }

    /**
     * @brief Converte ogni px con rgb maggiore di una soglia a nero
     * @param resource $image image resource
     * @param int $rt red threshold
     * @param int $gt green threshold
     * @param int $bt blue threshold
     * @return void
     */
    private function blackThresholdImage($image, $rt, $gt, $bt) {
        $xdim = imagesx($image);
        $ydim = imagesy($image);
        $black = imagecolorallocate($image , 0, 0, 0);
        for($x = 1; $x <= $xdim-1; $x++) {
            for($y = 1; $y <= $ydim-1; $y++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if($r < 30 and $g < 30 and $b < 30) {
                    imagesetpixel($image, $x, $y, $black);
                }
            }
        }
    }

    /**
     * @brief Clona una risorsa immagine
     * @param resopurce $image risorsa
     * @return resource clone
     */
    private function cloneImage($image) {
        $clone = imagecreatetruecolor(imagesx($image), imagesy($image));
		//
		$transparency = imagecolorallocatealpha($clone, 0, 0, 0, 127);
		imagefill($clone, 0, 0, $transparency);
        imagecopy($clone, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        return $clone;
    }

    /**
     * @brief slice
     * @param resource $image
     * @param int $target_size dimensione finale
     * @param string $axis asse h=orizzontale, v=verticale
     * @return int coordinata dall quale tagliare
     */
    private function slice($image, $target_size, $axis) {

        $rank = array();
        $original_size = $axis == 'h' ? imagesx($image) : imagesy($image);
        $long_size = $axis == 'h' ? imagesy($image) : imagesx($image);

        if($original_size == $target_size) {

            return 0;
        }

        $number_of_slices = 30; // Arbitrary number, maybe base it on image dimensions
        $slice_size = ceil($original_size / $number_of_slices);
        // How many slices out of the ranked slices we need to get our target width.
        $required_slices = ceil($target_size / $slice_size);
        $start = 0;
        $i = 0;
        while($start <= $original_size) {
            $i++;
            $slice = $this->cloneImage($image);
            if($axis === 'h') {
                $slice = $this->cropImage($slice, $slice_size, $long_size, $start, 0);
            }
            else {
                $slice = $this->cropImage($slice, $long_size, $slice_size, 0, $start);
            }
            $rank[] = array(
                'offset'=>$start,
                'entropy' => $this->grayscaleEntropy($slice)
            );
            $start += $slice_size;
        }

        $max = 0;
        $max_index = 0;
        for($i = 0; $i < $number_of_slices - $required_slices; $i++) {
            $temp = 0;
            for($j = 0; $j < $required_slices; $j++) {
                $temp += $rank[$i+$j]['entropy'];
            }
            if($temp > $max) {
                $max_index = $i;
                $max = $temp;
            }
        }
        return $rank[$max_index]['offset'];
    }

    /**
     * Brief Ritorna il crop di un'immagine
     * @param resource $image Immagine da croppare
     * @param int $width larghezza immagine croppata
     * @param int $height altezza immagine croppata
     * @param int $x0 coordinata x dalla quale partire a tagliare
     * @param int $y0 coordinata y dalla quale partire a tagliare
     * @param array $options Opzioni.
     * @return resource immagine croppata
     */
    private function cropImage($image, $width, $height, $x0, $y0) {
        $crop = imagecreatetruecolor($width, $height);
		//
		$transparency = imagecolorallocatealpha($crop, 0, 0, 0, 127);
		imagefill($crop, 0, 0, $transparency);

        imagecopy($crop, $image, 0, 0, $x0, $y0, $width, $height);
        return $crop;
    }

    /**
     * @brief Calcola l'entropia di un'immagine
     * @param resource $image resource immagine
     * @return float entropia
     */
    private function grayscaleEntropy($image) {
        // The histogram consists of a list of 0-254 and the number of pixels that has that value
        $histogram = $this->getImageHistogram($image);
        return $this->getEntropy($histogram, imagesx($image) * imagesy($image));
    }

    /**
     * @brief Ricava una array di frequenze di tonalità di grigio dell'immagine
     * @param resource $image resource dell'immagine
     * @return array istogramma
     */
    private function getImageHistogram($image) {
        $histogram = array();
        $xdim = imagesx($image);
        $ydim = imagesy($image);
        for($x = 1; $x <= $xdim-1; $x++) {
            for($y = 1; $y <= $ydim-1; $y++) {
                $rgb = imagecolorat($image, $x, $y);
                if(!isset($histogram[$rgb])) {
                    $histogram[$rgb] = 1;
                }
                else {
                    $histogram[$rgb] += 1;
                }
            }
        }

        return $histogram;
    }

    /**
     * @brief Calcola l'entropia dato l'istogramma di frequenze di colori
     * @param array $histogram istogramma di frequenze di colori
     * @param int $area area dell'immagine
     * @return float entropia
     */
    private function getEntropy($histogram, $area) {
        $value = 0.0;
//        $colors = count($histogram);
        foreach($histogram as $color => $frequency) {
            // calculates the percentage of pixels having this color value
            $p = $frequency / $area;
            // A common way of representing entropy in scalar
            $value = $value + $p * log($p, 2);
        }
        // $value is always 0.0 or negative, so transform into positive scalar value
        return -$value;
    }
}

class Rect
{
	public $x1;
	public $x2;
	public $y1;
	public $y2;
	public $weight;
	
	public function __construct($x1, $x2, $y1, $y2, $weight)
	{
		$this->x1 = $x1;
		$this->x2 = $x2;
		$this->y1 = $y1;
		$this->y2 = $y2;
		$this->weight = $weight;
	}
	
	public static function fromString($text)
	{
		$tab = explode(" ", $text);
		$x1 = intval($tab[0]);
		$x2 = intval($tab[1]);
		$y1 = intval($tab[2]);
		$y2 = intval($tab[3]);
		$f = floatval($tab[4]);
		
		return new Rect($x1, $x2, $y1, $y2, $f);
	}

}


class Feature
{

	public $rects;
	public $threshold;
	public $left_val;
	public $right_val;
	public $size;
	
	public function __construct( $threshold, $left_val, $right_val, $size)
	{

		$this->rects = array();
		$this->threshold = $threshold;
		$this->left_val = $left_val;
		$this->right_val = $right_val;
		$this->size = $size;
	}


	public function add(Rect $r)
	{
		$this->rects[] = $r;
	}
	
	public function getVal($grayImage, $squares, $i, $j, $scale)
	{
		$w = (int)($scale*$this->size[0]);
		$h = (int)($scale*$this->size[1]);
		$inv_area = 1/($w*$h);

		$total_x = $grayImage[$i+$w][$j+$h] + $grayImage[$i][$j] - $grayImage[$i][$j+$h] - $grayImage[$i+$w][$j];
		$total_x2 = $squares[$i+$w][$j+$h] + $squares[$i][$j] - $squares[$i][$j+$h] - $squares[$i+$w][$j];
		
		$moy = $total_x*$inv_area;
		$vnorm = $total_x2*$inv_area-$moy*$moy;
		$vnorm = ($vnorm>1) ? sqrt($vnorm) : 1;
		
		$rect_sum = 0;
		for($k = 0; $k < count($this->rects); $k++)
		{
			$r = $this->rects[$k];
			$rx1 = $i+(int)($scale*$r->x1);
			$rx2 = $i+(int)($scale*($r->x1 + $r->y1));
			$ry1 = $j+(int)($scale*$r->x2);
			$ry2 = $j+(int)($scale*($r->x2 + $r->y2));

			$rect_sum += (int)(($grayImage[$rx2][$ry2]-$grayImage[$rx1][$ry2]-$grayImage[$rx2][$ry1]+$grayImage[$rx1][$ry1])*$r->weight);
		}

		$rect_sum2 = $rect_sum*$inv_area;
		
		return ($rect_sum2 < $this->threshold*$vnorm ? $this->left_val : $this->right_val);
	}	
	
}

class Stage
{
	public $features;
	public $threshold;
	
	public function __construct($threshold)
	{
		$this->threshold = floatval($threshold);
		$this->features = array();
	}
	
	public function pass($grayImage, $squares, $i, $j, $scale)
	{
		$sum = 0;
		foreach($this->features as $f)
		{
			$sum += $f->getVal($grayImage, $squares, $i, $j, $scale);
		}

		return $sum > $this->threshold;
	}

}