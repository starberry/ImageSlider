<?php ini_set('display_errors', true); ?>
<!DOCTYPE html>
<html>
<head>
  <!-- This should be replaced with customised CSS, or the main site's CSS should include it. -->
  <link rel="stylesheet/less" type="text/css" href="less/SimpleImageSlider.less">

  <!-- Better to use a copy of LESS and jQuery on our own CDN, but for example: -->
  <script src="http://cdnjs.cloudflare.com/ajax/libs/less.js/1.3.3/less.min.js" type="text/javascript"></script>
  <script src="http://cdnjs.cloudflare.com/ajax/libs/jquery/1.10.1/jquery.min.js" type="text/javascript"></script>
</head>
<body>
<div id="simpleImageSlider">
<?php
// Note, usually you shouldn't mix HTML and PHP in the same file,
// but just to make this example simpler, we'll overlook it!

require_once('../ImageSlider.php');


// In addition to this class, you'll also need to implement the CSS, which
// you can do in LESS by importing less/ImageSlider.less and customising.

////////////////////////////////////////////////////////////////////////////

// ImageSlider on its own isn't useful: you have to define a subclass to
// interface the slide data you've got (which might come from a database,
// file, CMS or static array) to the ImageSlider class.
//
// SimpleImageSlider below is a class that implements this just for some
// static slides.

class SimpleImageSlider extends \Starberry\ImageSlider
{
    protected function getThumbURI($i) {
        // This example doesn't want thumbnails, so we return NULL to
        // indicate that there's no thumbnail.
        return NULL;
    }

    protected function getSlideURI($i) {
        // The first element of the array is the image URI
        return $i[0];
    }

    protected function getFullURI($i) {
        // For the sake of this example, we use the same URI for the
        // full-size image.
        return $i[0];
    }

    protected function getOverlayHTML($i) {
        // This method returns the HTML to overlay over the image, if
        // there is one. If no caption, return NULL.

        if(isset($i[1])) {
            // If there's a caption...

            if(isset($i[2]))
                // If there's a third element of the array, then it's the URL
                // to link the slide to.
                return '<a class="caption anim" href="'.$i[2].'">'.$i[1].'</a>';
            else
                // Else, just put unlinked content.
                return '<div class="caption anim">'.$i[1].'</div>';
        }
        else
            // No caption, so return NULL.
            return NULL;
    }

    // This needs to resolve correctly to the Javascript for iosSlider. It
    // should ideally be on the GGFX CDN.
    public $slider_js_uri = '../js/jquery.iosslider.js';

    // The CSS for the slider, which should include
    // ImageSlider.less. However, it's better to have this as part of the
    // main site CSS and to set this to NULL.
    public $slider_css_uri = NULL;

    protected $slider_type = 'iosslider';

    // DOM classes to add to the slider so it can be custom-styled.
    public $div_classes = 'slideshow simple';

    // If set, the DOM class to monitor for size changes to make it
    // responsive. This selector should be an ancestor (eg. parent) of the
    // slider class.
    protected $responsive_selector = NULL;
}

////////////////////////////////////////////////////////////////////////////

// Define some sample images
$slides = array(
    // array(<image url> [, <caption html> [, <page to link to>]])
    array("images/auction-slide01.jpg", '<h2>Catalogue Available</h2>', '/page1.html'),
    array("images/auction-slide02.jpg", '<h2>Next Auction on Tuesday 4th June 2013</h2><h3>at the Grand Connaught Rooms</h3>'),
    array("images/auction-slide03.jpg", '<h2>&pound;11,393,000 Raised in our last auction</h2><h3>Next Auction on Tuesday 4th June 2013</h3>'),
);

// and feed them into the example class
$slider = new SimpleImageSlider($slides);
echo $slider->html("\n");

?>
</div>
</body>
</html>
