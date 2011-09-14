<?php
class process {
  public function __construct(){
    //initialize
  }
  public function process_data(){
    
      $files = $_POST['files'];
      
      //$dir = "upload/".($this->customer_id > 0 ? $this->customer_id : $this->assign_to).'/';
      
      $update = 0;
      $add = 0;
      $total = 0;
      $list = array();
      
      foreach($files as $file)
      {
      
        $dir = "upload/speedex/" . $this->assign_to . '/' ;     
        if ($this->customer_id) $dir = 'upload/' . $this->customer_id . '/';      
        $path = $dir.$file;     
        //echo $file;     
        $handle = fopen($path, "r");
        
        $x = 0;       
        $type = 0;
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) 
        {
        
          if ($x == 0)
          {
            $this->get_type($data);
          }
          else if ($x > 0)
          {
            $action = $this->customer_update($data);
              
            switch ($action)
            {
            
              case 'update': 
              
                $update++; 
                
              break;
              
              case 'add': 
              
                $add++; 
                
              break;
            
            }
            
             
             $list[] = $data;
           
             $total++;
             
          } 
        
          $x++;
        }
      
        fclose($handle);
        
      }
      
      $this->load->view('admin/upload', array('LIST' => $list, 'TOTAL' => $total, 'UPDATE' => $update, 'ADD' => $add));
      
      /*
      
      if ($this->customer_id > 1)
      {
      
        
        
        
      }
      else
      
        $this->load->view('admin/upload', array('LIST' => $list, 'TOTAL' => $total, 'UPDATE' => $update, 'ADD' => $add));
        
      */  
    
    }

}



?>