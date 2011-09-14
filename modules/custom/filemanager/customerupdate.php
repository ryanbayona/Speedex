<?php

function customer_update($data)
    {
        
      for($i=0; $i<count($this->field); $i++) $val[$this->field[$i]] = $data[$i];
      
      if (isset($val['DATE_EMAIL']))  $val['DATE_EMAIL']      = $this->process_date($val['DATE_EMAIL']);
      if (isset($val['DATE_OUT']))  $val['DATE_OUT']      = $this->process_date($val['DATE_OUT']);
      if (isset($val['ETD']))     $val['ETD']         = $this->process_date($val['ETD']);
      if (isset($val['ETA']))     $val['ETA']         = $this->process_date($val['ETA']);
      if (isset($val['ACTUAL_DATE']))   $val['ACTUAL_DATE']   = $this->process_date($val['ACTUAL_DATE']);
      
      if (isset($val['DATE_RCVD'])) $val['DATE_RCVD'] = $this->process_date($val['DATE_RCVD']);
      
      if (isset($val['CREATED_ON']))
      {   
        $val['DATE_OUT']  = $this->process_date($val['CREATED_ON']);
        
      }
      
      
      if (isset($val['WCNAME']))
      {
      
        $val['consignee'] = $val['WCNAME'];
        
        unset($val['WCNAME']);
      
      }
      
      if (isset($val['WAYBILL#'])) 
      {
        $val['AWB'] = $val['WAYBILL#'];
        
        unset($val['WAYBILL#']);
      
      }
      
      if (isset($val['WBILL']))
      {
      
        $val['AWB'] = $val['WBILL'];
        
        unset($val['WBILL']);
        
      }
      
      if (isset($val['WB#']))
      {
      
        $val['AWB'] = $val['WB#'];
        
        unset($val['WB#']);
      
      
      }
      
      if (isset($val['MATDESC']))
      {     
        $val['MAT_DESC'] = $val['MATDESC'];       
        unset($val['MATDESC']);     
      }
      
      if (isset($val['DATE_RLS']))
      {
      
        $val['DATE_OUT'] = $this->process_date($val['DATE_RLS']);
        
        unset($val['DATE_RLS']);
      
      }
      
      if (isset($val['MATERIAL']))
      {
        
        $val['MATCODE'] = $val['MATERIAL'];
        
        unset($val['MATERIAL']);
      
      }
      
      if (isset($val['DR_#']))
      {
      
        $val['GMF'] = $val['DR_#'];
        
        unset($val['DR_#']);
      
      }
      
      if (isset($val['DESCRIPTION']))
      {
      
        $val['MAT_DESC'] = $val['DESCRIPTION'];
        
        unset($val['DESCRIPTION']);
      
      
      }
      
      if (isset($val['WCNAME']))
      {
      
        $val['consignee'] = $val['WCNAME'];
        
        unset($val['WCNAME']);
      
      }
      
      
      if (isset($val['CONFIRM_QTY']))
      {
      
        $val['QTY'] = $val['CONFIRM_QTY'];
        
        unset($val['CONFIRM_QTY']);
      
      
      }
      
      if (isset($val['PURCHASE_ORDER_NO']))
      {
      
        $val['PO'] = $val['PURCHASE_ORDER_NO'];
        
        unset($val['PURCHASE_ORDER_NO']);
      
      }
      
      if (isset($val['NAME_1']))
      {
      
        $val['CONSIGNEE'] = $val['NAME_1'];
        
        unset($val['NAME_1']);
      
      }
      
      if (isset($val['SD_DOC']))
      {
        
        $val['RO'] = $val['SD_DOC'];
        
      }
      
      if (isset($val['RECEIVED_BY']))
      {
        $val['RECVD_BY'] = $val['RECEIVED_BY'];
        unset($val['RECEIVED_BY']);
      }
      
      if ($this->customer_id > 0)
      {
      
        $awb = $val['AWB'];   
        $matcode = "";
        $qty = 0;
        if(isset($val['MATCODE'])) $matcode = $val['MATCODE'];
        if (isset($val['QTY'])) $qty = $val['QTY'];
      
        $val['customer_id'] = ($this->customer_id > 0 ? $this->customer_id : $this->assign_to);
      
        if (in_array('RNSS',  $this->field)) $val['DATA_TYPE'] = 'RNSS';
        if (in_array('RSLOC',   $this->field)) $val['DATA_TYPE'] = 'WIRELESS';
        if (in_array('SD_DOC',  $this->field)) $val['DATA_TYPE'] = 'RD_PD';
      
        if (isset($val['GMF'])) $this->db->where('GMF', $val['GMF']);
        if (isset($val['RNSS'])) $this->db->where('RNSS', $val['RNSS']);
      
        $this->db->where('awb', $awb);
        $this->db->where('MATCODE', $matcode);
        $this->db->where('QTY', $qty);
      
        $query = $this->db->get('tracking');
      
        $result = $query->row();
        
      }
      else
      {
      
        $awb = $val['AWB'];
        
        $this->db->where('awb', $awb);
        
        $query = $this->db->get('tracking');
        
        $result = $query->row();
      
      
      }
      
      $action = "";
      
      if ($result)
      {
      
        if ($this->customer_id > 0)
        {
      
          $this->db->where('idno', $result->idno);
          $this->db->update('tracking', $val);      
          
          
        }
        else
        {
        
          list($val['eta'], $val['etd']) = $this->set_eta($val['DATE_OUT'], $val['AREAS']);
        
          //unset($val['AREAS']);
          
          //print_r($val);
        
          $this->db->where('awb', $awb);
          $this->db->update('tracking', $val);
        
        
        }
        
        $id = $result->idno;
        
        $action = "update";
        
        $history_action = "Process: update";
        
        $this->history_save($history_action, $id, $val);
        
      
      }
      else
      {
      
        if ($this->customer_id > 0)
        {
      
          $this->db->insert('tracking', $val);
        
          $action = "add";
        
          $id = $this->db->insert_id();
        
          $history_action = "Process: add";
          
          $this->history_save($history_action, $id, $val);
        
        }
        
        
        
      }
      
      return $action;
      
      
    
    }
?>    