ImageSlider
===========

This is just an in-house wrapper script we use in conjunction with iosSlider / Swiper
to connect various image sources to iosSlider or Swiper in a roughly consistent
way.

Each use of ImageSlider in a project should be implemented as a subclass of the `ImageSlider` class.

Neither iosSlider or Swiper fulfil all our needs, so we're forced to pick one over the other depending on project.  ImageSlider is intended to be an abstraction layer to allow switching between the two with a minimum of rewrite.

It could really do with a substantial rewrite: allowing finer-grained override of the HTML for slides; subclassing out iosSlider vs. Swiper vs. something else; and a test suite incl. problematic HTML/CSS containers.

https://github.com/iosscripts/iosSlider
http://idangero.us/sliders/swiper/
