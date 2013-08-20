<?php

# ImageSlider.php
# Copyright (C) 2013, Starberry Ltd.
#
# Tom Gidden <gid@starberry.tv>
# April 2013

namespace Starberry\ImageSlider;

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
    /*abstract*/ protected $iosslider_js_uri;  // eg. '/templates/foo/js/vendor/jquery.iosslider.min.js'
    /*abstract*/ protected $iosslider_css_uri; // eg. '/templates/foo/css/iosslider.css'

    protected $responsive_selector;
    protected $large_thumb_max = 8;
    protected $div_classes = 'details';
    protected $slider_class = 'slider';
    protected $slide_class = 'slide';
    protected $thumbs_class = 'thumbs';
    protected $thumb_class = 'thumb';
    protected $full_class = 'fullsize';
    protected $prev_label = FALSE;
    protected $next_label = FALSE;
    protected $div_id;                         // If empty, a unique ID is generated.

    ////////////////////////////////////////////////////////////////////////////

    private $jdoc;

    public function __construct($imgs)
    {
        if(class_exists('\\JFactory') or class_exists('JFactory'))
            $this->jdoc =& \JFactory::getDocument();
        $this->imgs = $imgs;
        $this->buf = array();
    }

    protected $buf;             // Output buffer

    protected function getId()
    {
        // This generates a unique ID if there isn't already one.
        if(empty($this->div_id))
            $this->div_id = 'slideshow_'.dechex(crc32(rand()));
        return $this->div_id;
    }

    protected function addJS()
    {
        // If not set, we assume the required iosSlider javascript is
        // already included elsewhere.
        if(!empty($this->iosslider_js_uri))
            $this->buf[] = '<script type="text/javascript" src="'.$this->iosslider_js_uri.'"></script>';

        $id = $this->getId();
        $navSlideSelector = '#'.$id.' .'.$this->thumbs_class.' .'.$this->thumb_class;

        $confOpts = array(
            'snapToChildren:true',
            'onSlideChange:change',
            'onSlideComplete:change',
            'onSliderLoaded:change',
            'infiniteSlider:true',
            'desktopClickDrag:true',
            'autoSlide:true'
        );

        if(empty($this->responsive_selector)) {
            $confOpts[] = "responsiveSlides:false";
        }
        else {
            $confOpts[] = "responsiveSlides:true";
            $confOpts[] = "responsiveSlideContainer:obj.closest('".$this->responsive_selector."')";
        }

        $confOpts[] = "navSlideSelector:$('$navSlideSelector')";

        if(FALSE !== $this->prev_label and FALSE !== $this->next_label) {
            $confOpts[] = "navNextSelector:$('#${id}_next')";
            $confOpts[] = "navPrevSelector:$('#${id}_prev')";
        }

        $confOpts = '{'.join(",", $confOpts).'}';

        $this->buf[] = '<script type="text/javascript">';
        $this->buf[] = "
(function ($) {
  'use strict';
  var obj = $('#$id');
  var change = function (a) {
    $('.thumb',obj).removeClass('current');
    $('.thumb:eq('+(a.currentSlideNumber-1)+')',obj).addClass('current');
    $('.slide',obj).removeClass('current');
    $(a.currentSlideObject).addClass('current');
  };
  $(function () {
    obj.iosSlider($confOpts);
    if($('html').hasClass('lt-ie9')) { // I H8 IE8
       $('a.slide', obj).each(function () {
         var self = $(this);
         var href = self.prop('href');
         self.removeAttr('href')
             .bind('click', function () { window.location.href = href; return false; });
       });
    }});
}(jQuery));
</script>";
    }

    protected function addCSS()
    {
        if(!empty($this->iosslider_css_uri))
            if(!empty($this->jdoc))
                $this->jdoc->addStyleSheet($this->iosslider_css_uri);
            else
                $this->buf[] = '<link rel="stylesheet" type="text/css" href="'.$this->iosslider_css_uri.'">';
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

        // Otherwise, start with the image in an array
        $slide = array($this->imgElement($imgURI, null, $alt));

        // If there's a URI, the wrapper is an <a>, else it's a <div>
        if(!is_null($clickURI))
            array_unshift($slide, '<a href="'.$clickURI.'"');
        else
            array_unshift($slide, '<div');

        // If there's a class (there should be!) then add it to the element
        if(is_null($class))
            $slide[0] .= '>';
        else
            $slide[0] .= ' class="'.$class.'">';

        // If there's any HTML to overlay, add it
        if(!is_null($overlayHTML))
            $slide[] = $overlayHTML;

        // And close the wrapper
        if(is_null($clickURI))
            $slide[] = '</div>';
        else
            $slide[] = '</a>';

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

        return join(' ', $attrs).'>';
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

        $this->buf[] = '<div class="ImageSliderOuter '.$this->div_classes.'">';

        // We only bother using a slider if there's more than one slide.
        if ($actualCount > 1) {

            $thumbs_classes = $this->thumbs_class;
            if($actualCount > $this->large_thumb_max)
                $thumbs_classes .= ' smallThumbs';
            else
                $thumbs_classes .= ' largeThumbs';

            $this->buf[] = '<div id="'.$this->getId().'" class="ImageSlider ImageSliderSlideshow slideshow '.$this->div_classes.' startekSlider">';
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

            if(FALSE !== $this->prev_label and FALSE !== $this->next_label) {
                $this->buf[] = '<a id="'.$this->getId().'_prev" href="#" class="ImageSliderPrev ImageSliderPrevNext">'.$this->prev_label.'</a>';
                $this->buf[] = '<a id="'.$this->getId().'_next" href="#" class="ImageSliderNext ImageSliderPrevNext">'.$this->next_label.'</a>';
            }

            $this->buf[] = '</div>'; // end wrapper
        }
        else if($actualCount == 1) {
            $this->buf[] = '<div id="'.$this->getId().'" class="ImageSlider ImageSliderSlideshow slideshow '.$this->div_classes.' startekSlider">';
            $this->buf[] = '<div class="'.$this->slider_class.'">';
            $this->buf   = array_merge($this->buf, $slides);
            $this->buf[] = '</div>'; // end slider
            $this->buf[] = '</div>'; // end Slideshow
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
