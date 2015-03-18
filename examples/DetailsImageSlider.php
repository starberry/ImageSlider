<?php

require_once('../ImageSlider.php');

class DetailsImageSlider extends \Starberry\ImageSlider
{
    // It'd be better if we could use GGFX async to get the images in a
    // block, but for now, this'll do.  All of these settings should
    // really be abstracted out to a config file.

    protected function getThumbURI($img) { return $img->getUrl('90lb70'); }
    protected function getSlideURI($img) { return $img->getUrl('870lb650'); }
    protected function getFullURI($img)  { return $img->getUrl('x'); }

    protected $no_image_uri = '/ggfx_image/foo/s/1/no-image-2.svg';
    protected $slider_js_uri = '/templates/foo/js/vendor/jquery.iosslider.js';
    protected $slider_css_uri = '/templates/foo/css/iosslider.css';
    protected $slider_type = 'iosslider';
}

// Prepare images 
$jDocument =& JFactory::getDocument();
$images = $this->item->getImages();
$countImages = count($images);

// Prepare the images for input into the image slider class.
for($i=0; $i<$countImages; $i++) {
    $image = JTable::getInstance('image', 'StarTekTable');
    $image->load($images[$i]);
    $images[$i] = $image;
}

$slider = new DetailsImageSlider($images);
?>
...
<div id="tab1" class="tab-pane active photos-pane">
  <?= $slider->html("\n") ?>
</div>
