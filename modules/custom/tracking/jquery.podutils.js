Drupal.behaviors.podUtils=function(){
  //attaches image rotator to lightbox
  var im = $("#imageData");
  if(im.length > 0){
    im.append("<a class='rotator' href='#'>Rotate</a>");
    $(".rotator").click(function(){
      $("#lightboxImage").rotate(45);
    });
  }
  
};