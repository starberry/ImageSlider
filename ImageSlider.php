<?php

# ImageSlider.php
# Copyright (C) 2013, Starberry Ltd.
#
# Tom Gidden <gid@starberry.tv>
# April 2013

namespace Starberry;

abstract class ImageSlider {

    private $imgs;              // Opaque image 'handles'... can be
                                // objects, CFTLEs, whatever.  get___URI()
                                // methods should know how to deal with
                                // them.

    abstract protected function getThumbURI($img);
    abstract protected function getSlideURI($img);
    abstract protected function getFullURI($img);

    protected function getOverlayHTML($img) {}
    protected function getClickURI($img) {}

    /*abstract*/ protected $no_image_uri;      // eg. '/ggfx_image/foo/s/1/no-image-2.svg'

    # May be NULL, if dealt with elsewhere:
    /*abstract*/ protected $slider_js_uri;  // eg. '/templates/foo/js/vendor/jquery.iosslider.min.js'
    /*abstract*/ protected $slider_css_uri; // eg. '/templates/foo/css/iosslider.css'
    /*abstract*/ protected $slider_type;    // either 'iosslider' or 'swiper'

    /*abstract*/ protected $iosslider_js_uri;     // Deprecated
    /*abstract*/ protected $iosslider_css_uri;    // Deprecated

    protected $responsive_selector; // Deprecated
    protected $responsive_slides;
    protected $responsive_container;
    protected $large_thumb_max = 8;
    protected $div_classes = 'details';
    protected $slider_class = 'slider';
    protected $slide_class = 'slide';
    protected $thumbs_class = 'thumbs';
    protected $thumb_class = 'thumb';
    protected $full_class = 'fullsize';
    protected $prev_label = FALSE;
    protected $next_label = FALSE;
    protected $full_label = FALSE;
    protected $extra_options = FALSE;
    protected $div_id;                         // If empty, a unique ID is generated.

    // Any value equatin to true will produce a background over style markup
    // a value of 2 will add the resize script so that it fills the page
    protected $background_cover = FALSE;

    ////////////////////////////////////////////////////////////////////////////

    private $jdoc;

    public function __construct($imgs)
    {
        if(class_exists('\\JFactory') or class_exists('JFactory'))
            $this->jdoc = \JFactory::getDocument();
        $this->imgs = $imgs;
        $this->buf = array();

        if(isset($this->iosslider_js_uri)) {
            $this->slider_js_uri = $this->iosslider_js_uri;
            $this->slider_css_uri = $this->iosslider_css_uri;
            $this->slider_type = 'iosslider';
        }
    }

    protected $buf;             // Output buffer

    public function getId()
    {
        // This generates a unique ID if there isn't already one.
        if(empty($this->div_id))
            $this->div_id = 'slideshow_'.dechex(crc32(rand()));
        return $this->div_id;
    }

    protected function addJS()
    {
        switch ($this->slider_type) {
        case 'Swiper':
        case 'swiper':
            return $this->addSwiperJS();

        default:
        case 'iosslider':
            return $this->addIosSliderJS();
        }
    }

    protected function addSwiperJS()
    {
        // If not set, we assume the required slider javascript is
        // already included elsewhere.
        if(!empty($this->slider_js_uri))
            $this->buf[] = '<script type="text/javascript" src="'.$this->slider_js_uri.'"></script>';

        $id = $this->getId();
        $navSlideSelector = '#'.$id.' .'.$this->thumbs_class.' .'.$this->thumb_class;

        $confOpts = array(
            'snapToChildren'=>'true',
            'desktopClickDrag'=>'true',
            'snapSlideCenter'=>'true',
            'loop'=>'true',
            'autoplay'=>'5000',
//            'autoplayDisableOnInteraction'=>'true',
            'calculateHeight'=>'true',
            'wrapperClass'=>'"slider"',
            'slideClass'=>'"slide"',
        );

        if(!empty($this->extra_options)) {
            $confOpts = array_merge($confOpts, $this->extra_options);
        }

        // Can't think of a quicker way to do this... can't use
        // json_encode, as we're encoding complex stuff.
        $confOptsBuf = array();
        foreach ($confOpts as $key=>$val) {
            $confOptsBuf[] = $key.':'.$val;
        }

        $confOpts = '{'.join(",", $confOptsBuf).'}';

        $this->buf[] = '<script type="text/javascript">(function ($) {';
        $this->buf[] = "
  'use strict';
  $('#$id').data('swiper-opts', $confOpts);
  var swiper = $('#$id').swiper($confOpts);
  $(function () {";

        // 2 is used for "full" height swipers, not including header sections
        if ( $this->background_cover === 2 )
            $this->buf[] = "
    jQuery(window).on('resize', function() {
        var headerheight = 0;
        if ( $('#header').length )
            headerheight = $('#header').height();
        else if ( $('header').length == 1 )
            headerheight = $('header').height();
        var browserheight = jQuery(window).height();
        $('#{$id}_outer, #$id, #$id .slider, #$id .slide').height(browserheight-headerheight);
    }).trigger('resize');";

        $this->buf[] = "
    $('#{$id}_prev').click(function () { return swiper.swipePrev(); });
    $('#{$id}_next').click(function () { return swiper.swipeNext(); });
  });
";
        $this->buf[] = "}(jQuery));\n</script>";
    }


    protected function addIosSliderJS()
    {
        // If not set, we assume the required slider javascript is
        // already included elsewhere.
        if(!empty($this->slider_js_uri))
            $this->buf[] = '<script type="text/javascript" src="'.$this->slider_js_uri.'"></script>';

        $id = $this->getId();
        $navSlideSelector = '#'.$id.' .'.$this->thumbs_class.' .'.$this->thumb_class;

        $confOpts = array(
            'snapToChildren'=>'true',
            'onSlideChange'=>'change',
//            'onSlideComplete'=>'complete',
            'onSliderLoaded'=>'change',
            'onSliderResize'=>'change',
            'infiniteSlider'=>'true',
            'desktopClickDrag'=>'true',
            'snapSlideCenter'=>'true',
            'autoSlide'=>'true'
        );

        if(isset($this->responsive_selector)) {
            error_log("ImageSlider::responsive_selector is deprecated. ".__FILE__);
            if($this->responsive_selector) {
                $this->responsive_container = true;
                $this->responsive_slides = true;
            }
        }

        if(!$this->responsive_container and !$this->responsive_slides) {
            $confOpts["responsiveSlides"] = "false";
        }
        else {
            $confOpts["responsiveSlides"] = ($this->responsive_slides ? 'true':'false');
            $confOpts["responsiveSlideContainer"] = ($this->responsive_container ? 'true':'false');
        }

        $confOpts["navSlideSelector"] = "$('$navSlideSelector')";

        if(FALSE !== $this->prev_label and FALSE !== $this->next_label) {
            $confOpts["navNextSelector"] = "$('#${id}_next')";
            $confOpts["navPrevSelector"] = "$('#${id}_prev')";
        }

        if(!empty($this->extra_options)) {
            $confOpts = array_merge($confOpts, $this->extra_options);
        }

        // Can't think of a quicker way to do this... can't use
        // json_encode, as we're encoding complex stuff.
        $confOptsBuf = array();
        foreach ($confOpts as $key=>$val) {
            $confOptsBuf[] = $key.':'.$val;
        }

        $confOpts = '{'.join(",", $confOptsBuf).'}';

        $this->buf[] = '<script type="text/javascript">';
        $this->buf[] = "
(function ($) {
  'use strict';
  var obj = $('#$id');
  var change = function (a) {
    var curThumb = $('.thumb:eq('+(a.currentSlideNumber-1)+')',obj);
    var curSlide = $(a.currentSlideObject);

    $('.thumb',obj).not(curThumb).removeClass('current');
    curThumb.addClass('current');

    $('.slide',obj).not(curSlide).removeClass('current');
    curSlide.addClass('current');
  };
  var complete = change;
  $(function () {
    obj.iosSlider($confOpts).addClass('loaded');;
    if($('html').hasClass('lt-ie9')) { // I H8 IE8
       $('.slide > a, a.slide', obj).each(function () {
         var self = $(this);
         var href = self.prop('href');
         self.removeAttr('href')
             .bind('click', function () { window.location.href = href; return false; });
       });
    }";

        if(FALSE !== $this->full_label) {
            $this->buf[] = "
    var body = $('body');
    var toggle = function() {
      body.toggleClass('fullsize');
      obj.iosSlider(body.hasClass('fullsize') ? 'autoSlidePause' : 'autoSlidePlay');
      return false;
    };
    $('#${id}_full').click(toggle);
    $('.slider',obj).click(function() {
      if(body.hasClass('fullsize')) {
        toggle();
        return false;
      }
    });";
        }

        $this->buf[] = "});";
        $this->buf[] = "}(jQuery));\n</script>";
    }

    protected function addCSS()
    {
        if(!empty($this->slider_css_uri))
            if(!empty($this->jdoc))
                $this->jdoc->addStyleSheet($this->slider_css_uri);
            else
                $this->buf[] = '<link rel="stylesheet" type="text/css" href="'.$this->slider_css_uri.'">';
    }

    protected function addCustomTag($tag)
    {
        // If no meta tags are desired, this method can be overridden with
        // an empty one.
        if(!empty($this->jdoc))
            $this->jdoc->addCustomTag($tag);
    }

    protected function slideElement($imgURI, $overlayHTML=null, $clickURI=null, $class=null, $alt=null)
    {
        // This handles creation of a slide element, either as an isolated
        // <img>, or as a group element (either a <div> or an <a>
        // depending on whether $clickURI is set), and child elements (the
        // <img> and any additional $overlayHTML)

        // If it's just a bare image...
        if(is_null($overlayHTML) and is_null($clickURI))
            return array($this->imgElement($imgURI, $class, $alt));

        // Otherwise, start with an empty array
        $slide = array();

        // If there's a URI, use an <a> as the main slide wrapper. Else, a <div>
        if(!is_null($clickURI))
            $slide[] = '<a href="'.$clickURI.'"';
        else
            $slide[] = '<div';

        if ( $this->background_cover )
            $slide[] = " style=\"background-image: url($imgURI);\"";

        // If there's a class (there should be!) then add it to the
        // wrapper and close the wrapper's opening tag.
        if(is_null($class))
            $slide[] = '>';
        else
            $slide[] = ' class="'.$class.'">';

        // Add the image
        if ( !$this->background_cover )
            $slide[] = $this->imgElement($imgURI, null, $alt);

        // If there's any HTML to overlay, add it
        if(!is_null($overlayHTML))
            $slide[] = '  '.$overlayHTML;

        // And close the wrapper
        if(!is_null($clickURI))
            $slide[] = '</a>';
        else
            $slide[] = '</div>';

        // Return as an array, so it needs to be join()'ed or
        // array_merge()'d into the buffer.
        return $slide;
    }

    protected function imgElement($src, $class=null, $alt=null)
    {
        $attrs = array('<img');
        $attrs[] = 'src="'.$src.'"';

        if(!is_null($class))
            $attrs[] = 'class="'.$class.'"';

        if(!is_null($alt))
            $attrs[] = 'alt="'.htmlspecialchars($alt).'"';

        return join(' ', $attrs).' />';
    }

    public function html($sep="\n")
    {
        $count = count($this->imgs);
        $slides = array();      // Temporary buffer of slide HTML
        $thumbs = array();      // Temporary buffer of thumb HTML
        $fulls = array();       // Temporary buffer of full-size HTML
        $noImages = 0;

        $this->addCSS();

        // For each image handle...
        for ($i=0; $i < $count; $i++) {
            $obj =& $this->imgs[$i];
            try {
                // Get the URIs.  This might be done more efficiently with
                // queueing, etc.
                $thumbURI = $this->getThumbURI($obj);
                $slideURI = $this->getSlideURI($obj);
                $fullURI = $this->getFullURI($obj);
                $overlayHTML = $this->getOverlayHTML($obj);
                $clickURI = $this->getClickURI($obj);
            }
            catch (Exception $e) {
                $buf[] = '<!-- '.$e->getMessage().' -->';
            }

            // If StarTek already put in a 'no-image' or similar, then replace it with
            // a CDN'ed image URI.
            if(!empty($this->no_image_uri) and ( empty($slideURI) or
                                                FALSE !== strpos($slideURI, 'error-image') or
                                                FALSE !== strpos($slideURI, 'no-image'))) {

                unset($fullURI);                    // We don't want the meta tag
                $noImages ++;

                if($noImages > 1) {
                    unset($thumbURI);
                    unset($slideURI);
                }
                else {
                    if(!empty($thumbURI)) $thumbURI = $this->no_image_uri;
                    $slideURI = $this->no_image_uri;
                }
            }

            // The slide element might be complex, so it arrives as an
            // array, rather than a string.
            if(!empty($slideURI)) {
                $slide = $this->slideElement($slideURI, $overlayHTML, $clickURI, $this->slide_class);
                $slides[] = is_array($slide) ? join($sep, $slide) : $slide;

                // However, the thumbs and fulls are (for now) just <img>s.
                if(!empty($thumbURI)) {
                    $thumb = $this->imgElement($thumbURI, $this->thumb_class);
                    $thumbs[] = is_array($thumb) ? join($sep, $thumb) : $thumb;
                }

                // If we got a full-size image, then we can add it to the page
                // header for SEO and social. $fulls is there for future support of zooming.
                if(!empty($fullURI)) {
                    $full = $this->imgElement($fullURI, $this->full_class);
                    $fulls[] = is_array($full) ? join($sep, $full) : $full;
                    $this->addCustomTag('<meta property="og:image" content="'.$fullURI.'">');
                }
            }
        }

        // Count up the actual number of slides to show.
        $actualCount = count($slides);

        // Check there's at least one image
        if($actualCount < 1 and !empty($this->no_image_uri)) {
            // If there was no (valid) image, and we've got a placeholder image, use it.
            $slide = $this->slideElement($this->no_image_uri, null, null, $this->slide_class);
            $slides[] = is_array($slide) ? join($sep, $slide) : $slide;
            $actualCount = 1;
        }

        $id = $this->getId();

        $this->buf[] = '<div id="'.$id.'-outer" class="ImageSliderOuter '.$this->div_classes.'">';

        // We only bother using a slider if there's more than one slide.
        if ($actualCount > 1) {

            $thumbs_classes = $this->thumbs_class;
            if($actualCount > $this->large_thumb_max)
                $thumbs_classes .= ' smallThumbs';
            else
                $thumbs_classes .= ' largeThumbs';

            $this->buf[] = '<div id="'.$id.'" class="ImageSlider ImageSliderSlideshow slideshow '.$this->div_classes.' startekSlider">';
            $this->buf[] = '<div class="'.$this->slider_class.'">';
            $this->buf   = array_merge($this->buf, $slides);
            $this->buf[] = '</div>'; // end slider
            if(!empty($thumbs)) {
                $this->buf[] = '<div class="'.$thumbs_classes.'">';
                $this->buf   = array_merge($this->buf, $thumbs);
                $this->buf[] = '</div>'; // end thumbs
            }

            $this->buf[] = '</div>'; // end Slideshow
            $this->addJS();

            if((FALSE !== $this->prev_label and FALSE !== $this->next_label) or (FALSE !== $this->full_label)) {
                $this->buf[] = '<div class="ImageSliderControls">';
                $this->buf[] = '<span>';

                if(FALSE !== $this->prev_label and FALSE !== $this->next_label) {
                    $this->buf[] = '<a id="'.$this->getId().'_prev" href="javascript:void(0)" class="ImageSliderPrev ImageSliderPrevNext"><span>'.$this->prev_label.'</span></a>';
                    $this->buf[] = '<a id="'.$this->getId().'_next" href="javascript:void(0)" class="ImageSliderNext ImageSliderPrevNext"><span>'.$this->next_label.'</span></a>';
                }

                if(FALSE !== $this->full_label) {
                    $this->buf[] = '<a id="'.$this->getId().'_full" href="javascript:void(0)" class="ImageSliderFull"><span>'.$this->full_label.'</span></a>';
                }

                $this->buf[] = '</span>'; // end controls inner wrapper
                $this->buf[] = '</div>'; // end controls wrapper
            }

            $this->buf[] = '</div>'; // end wrapper
        }
        else if($actualCount == 1) {
            $this->buf[] = '<div id="'.$this->getId().'" class="ImageSlider ImageSliderSlideshow slideshow '.$this->div_classes.' startekSlider">';
            $this->buf[] = '<div class="'.$this->slider_class.'">';
            $this->buf   = array_merge($this->buf, $slides);
            $this->buf[] = '</div>'; // end slider
            $this->buf[] = '</div>'; // end Slideshow

            if(FALSE !== $this->full_label) {
                $this->buf[] = '<div class="ImageSliderControls">';
                $this->buf[] = '<span>';
                $this->buf[] = '<a id="'.$this->getId().'_full" href="javascript:void(0)" class="ImageSliderFull"><span>'.$this->full_label.'</span></a>';
                $this->buf[] = '</span>'; // end controls inner wrapper
                $this->buf[] = '</div>'; // end controls wrapper
            }

            $this->buf[] = '</div>'; // end wrapper
        }
        else {
            // Should only happen if there were no images and no placeholder set
            //            $this->buf[] = '<div id="'.$this->div_id.'" class="'.$this->div_classes.'">';
            //            $this->buf[] = '</div>'; // end Slideshow
        }


        return join($sep, $this->buf);
    }
};
