


/*====================================
 =            SWIPPER             =
 ====================================*/
var swiper = new Swiper('.swiper-container', {
  pagination: '.swiper-pagination',
  direction: 'horizontal',
  slidesPerView: 1,
  paginationClickable: true,
  spaceBetween: 30,
  //mousewheelControl: true
});


/*====================================
 =            ON DOM READY            =
 ====================================*/
(function($){

  $(function() {
    $('.toggle-nav').click(function() {
      // Calling a function in case you want to expand upon this.
      toggleNav();
    });
  });


  /*========================================
   =            CUSTOM FUNCTIONS            =
   ========================================*/
  function toggleNav() {
    if ($('#site-wrapper').hasClass('show-nav')) {
      // Do things on Nav Close
      $('#site-wrapper').removeClass('show-nav');
    } else {
      // Do things on Nav Open
      $('#site-wrapper').addClass('show-nav');
    }

  //$('#site-wrapper').toggleClass('show-nav');
  }

})(jQuery);

