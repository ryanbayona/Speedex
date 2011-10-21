Drupal.behaviors.podUtils=function(){
  $("#lightbox").unbind('click');
  $(".rotator").click(function(){
     var rotate = $(this).data('rotate');
     if(rotate){
       rotate += 90 ;
     }else{
       rotate = 90;
     }
     $("#lightboxImage").rotate(rotate);
     $(this).data('rotate',rotate)
  });  
};
