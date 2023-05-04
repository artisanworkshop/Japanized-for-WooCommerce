/* eslint-disable unicorn/no-abusive-eslint-disable */
/* eslint-disable */

(function( $ ) {
	'use strict';

	/**
    Slider - 1.0 (using owl-slider)
	 */

$(document).ready(function() {
  var owl = $("#woorelatedproducts");
  owl.owlCarousel({
      items : 3,
      itemsDesktop : [1000,3],
      itemsDesktopSmall : [900,3],
      itemsTablet: [600,2],
      autoPlay: 3500,
      itemsMobile : false,
  });
 
  // Custom Navigation Events
  $(".next").click(function(){
    owl.trigger('owl.next');
  })
  $(".prev").click(function(){
    owl.trigger('owl.prev');
  })
 
});

})( jQuery );