Drupal.behaviors.addEvents = function(){
  


  var buttons = $('.fm-buttons');  
  if(buttons.length > 0 ){
    buttons.click(function(){
      var action_type = $(this).attr('class').split(" ")[1];
      var buttons_checked = $(".view-file-manager :checked");
      if(buttons_checked.length > 0){
        var post_data = []
        switch(action_type){
          case "fm-process":
            post_data.push("type=process");
            
          break;
          case "fm-delete":
            post_data.push("type=delete");
          break;
        }
        var proceed = true;
        if(post_data[0].match("type=delete")){
            if(confirm("Are you sure you want to delete selected file(s)?")== true){
               proceed = true;
            }else{
              proceed = false;
            }
        }
        if(proceed){
          $("#indicator").show();
          buttons_checked.each(function(index,el){
            post_data.push($(el).attr("name") + "=" + $(el).val());
          });
          
          $.ajax({
            url : "/admin/file/actions",
            type: "POST",
            data: post_data.join("&"),
            dataType:"json",
            success: function(data, textStatus, jqXHR){
                alert(data.message);
                
                window.location.reload();
            }
          });
        
        }
        
        
      }else{
        alert('No file selected.Please select a file by ticking the checkbox.');
      }
      
    });
  }

};