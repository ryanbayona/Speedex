<?php

	class Admin extends Controller
    {
    
    	var $speedex_uid = 0;
		var $speedex_admin = 0;
		var $max_limit = 15;
		var $page = 1;
		var $search = "";
		var $customer_id = 0;
		
		var $fields = array("ID" => "ID", 
							"GMF" => "GMF/DR", 
							"RO" => "SO/RO",
							"RNSS" => "RNSS",
							"PO" => "PO",
							"SR" => "SR",
							"MATCODE" => "MATCODE",
							"MAT_DESC" => "MAT_DESC",
							"qty" => "QTY",
							"DATE_OUT" => "DATE OUT",
							"awb" => "WAYBILL",
							"sla" => "SLA",
							"ETA" => "ETA",
							"ETD" => "ETD",
							"areas" => "AREAS",
							"consignee" => "CONSIGNEE",
							"RECVD_BY" => "RECVD BY", 
							"DATE_RCVD" => "DATE RCVD",
							"DAYS" => "# DAYS DELIVERED",
							"status" => "STATUS", 
							"remarks" => "REMARKS");
		
		var $selected_field = array("ID", "GMF", "RO","RNSS","PO","SR","MATCODE","MAT_DESC","qty","DATE_OUT","awb","sla","ETA","ETD","areas","consignee","RECVD_BY", "DATE_RCVD","DAYS","status", "remarks");
		var $selected_head = array("ID", 
								   "GMF/DR", 
								   "SO/RO", 
								   "RNSS", 
								   "PO", 
								   "SR", 
								   "MATCODE", 
								   "MAT_DESC", 
								   "QTY", 
								   "DATE OUT", 
								   "WAYBILL", 
								   "SLA", 
								   "ETA", 
								   "ETD", 
								   "AREAS",
								   "CONSIGNEE", 
								   "RECVD BY", 
								   "DATE RCVD", 
								   "# DAYS DELIVERED",
								   "STATUS", 
								   "REMARKS");
		
		var $has_admin = 0;
		var $assign_to = 0;
		
		var $user = array();
    
    	function Admin()
        {
        
        	parent::Controller();
            
            
			$this->load->library('phpsession');
			$this->load->library('date_utility');
            
			
            
			$this->speedex_uid 		= $this->phpsession->get('speedex_uid');
			$this->speedex_admin 	= $this->phpsession->get('speedex_admin');
			$this->has_admin 		= $this->phpsession->get('has_admin');
			$this->customer_id 		= $this->phpsession->get('company_id');
			$this->assign_to 		= $this->phpsession->get('assign_to');
			
			//if ($this->speedex_uid > 1) $this->customer_id = $this->speedex_uid;
			
			$this->user = $this->user_details();
			
			if ($this->speedex_uid == 0) header("Location: " .base_url()."login");
			
        
        }
		
		
		function user_details($id = 0)
		{
		
			$details = array();
			
			if ($id > 0)
			
				$this->db->where('userid', $id);
				
			else
			
				$this->db->where('userid', $this->speedex_uid);
			
			$query = $this->db->get('user');
			
			$result = $query->result_array();
			
			if ($result) $details = $result[0];
			
			return $details;
		
		}
        
		
		var $MESSAGE = 0;
        
        function index()
        {
		
			$DATA['LIST'] = $this->main(true);
			
			$DATA['LEFT_NAVIGATION'] = $this->load->view('admin/left_navigation', true, true);
        
        	$this->load->view('admin/tracking', $DATA);
        
        }
		
		function main($return = false)
		{
		
			list($data, $total) = $this->tracking_list();
			
			$TRACKING['LIST'] = $data;
			
			$DATA['PAGER'] = $this->load->view('admin/pager', array('TOTAL' => $total, 'result' => count($data)), true);
			
			$TRACKING['LIST'] = $this->process_data($data);
			
			$DATA['LIST'] = $this->load->view('admin/tracking_customer_list', $TRACKING, true);
				
			if ($return)
			
				return $this->load->view('admin/awblist_customer', $DATA, true);
				
			else
			
				$this->load->view('admin/awblist_customer', $DATA);
			
		
		
		}
		
		
		function process_data($data)
		{
		
			foreach($data as $key => $val)
			{
			
				if ($val['sla'] > $val['asla'] && $val['DATE_RCVD'] == '---') $data[$key]	= $this->span('#ff0000', $val);
				
				//echo $val['status'];
				
				//if ($val['status'] == 'DELIVERED') $data[$key]['sla'] = $val['DATE_RCVD'];
			
			}
		
			return $data;
		
		}
		
		
		function span($color, $data)
		{
		
			foreach($data as $key => $val) 
			
				if ($key != 'idno' && strlen($val)) $data[$key] = "<span style='color:$color'>$val</span>";
			
			return $data;
		
		
		}
		
		
		
		function buildWhere()
		{
		
			$query = $this->db->query("desc tracking");
			
			$result = $query->result_array();
			
			$where = array();
			
			$str = explode(' ', $this->search);
			
			foreach($str as $val)
			{
			
				foreach($result as $row)
				{
				
					$where[] = "t.".$row['Field']." LIKE '%$val%'";
				
				}
			
			}
			
			return "(".implode(" OR ", $where).")";
		
		}
		
		
		function init()
		{
		
			$this->db->where('user_id', $this->speedex_uid);
			
			$query = $this->db->get('settings');
			
			$result = $query->row();
			
			if ($result)
			{
			
				$this->page = $result->page;
				$this->search = $result->search;
				$this->max_limit = $result->max_limit;
				
				if (strlen($result->fields))
				
					$this->selected_field = explode(',', $result->fields);
			
			}
		
		
		}
		
		
		
		function tracking_list()
		{
		
			$this->init();
			
			
		
			if (strlen($this->search))
			{
				$where = $this->buildWhere();
			
				$this->db->where($where);
			}
			
			if ($this->page == 1)
			
				$this->db->limit($this->max_limit);
				
			else
			
				$this->db->limit($this->max_limit, ($this->page - 1) * $this->max_limit);
				
			$this->db->orderby('awb');
			
			if ($this->customer_id > 0)	
			
				$this->db->where("customer_id", $this->customer_id);
				
			else if ($this->assign_to > 0)
			
				$this->db->where("customer_id", $this->assign_to);
				
			else
			
				$this->db->where(false);
				
				
				
			/*
			$this->db->select("t.*");	
			
			*/
			
			$field = array();
			
			
			foreach($this->selected_field as $str)
			{ 
				
				$field[] = "t.".$str;
			
			}
			
			$this->db->select(implode(',',$field));
			
			//$this->db->select("l.sla as asla");
			
			$this->db->select("IF(t.AREAS = 'NCR', 1, IF(t.AREAS = 'LUZON', 2, 4)) AS asla ");
			
			/*
			$this->db->select("if((DATE_RCVD = '0000-00-00') OR 
								  (DATE_RCVD IS NULL) OR
								  LENGTH(DATE_RCVD) = 0, DATEDIFF(NOW(), DATE_OUT), DATEDIFF(DATE_RCVD, DATE_OUT)) as SLA");
			*/
			
			$this->db->select("if((DATE_RCVD = '0000-00-00') OR 
								  (DATE_RCVD IS NULL) OR
								  LENGTH(DATE_RCVD) = 0, DATEDIFF(NOW(), DATE_OUT), DATEDIFF(DATE_RCVD, DATE_OUT)) as sla");
			
			$this->db->select("IF(DATE_RCVD = '0000-00-00' OR
								  DATE_RCVD IS NULL OR
								  LENGTH(DATE_RCVD) = 0, '---', DATE_RCVD) as DATE_RCVD");
			
			$this->db->select("IF(status = 'DELIVERED', DATEDIFF(DATE_RCVD,DATE_OUT), '---') AS DAYS");	
								  
			$this->db->select("t.idno");					  
						
								  
			//$this->db->join("locations as l", "if(data_type = 'RD_PD',l.dloc = t.SOLD_TO_PARTY ,l.dloc = t.RSLOC)", "left");
			
			$this->db->groupby("t.idno");					  
				
			$query = $this->db->get('tracking as t');
			
			//echo $this->db->last_query();
			
			$result = $query->result_array();
			
			$sql = explode('LIMIT', $this->db->last_query());
			
			$query = $this->db->query($sql[0]);
			
			$total = $query->num_rows();
			
			foreach($result as $key => $val):
			
				if ($val['DATE_RCVD'] != "---"):
				
					$result[$key]['sla'] = $this->compute_sla($val);
				
				endif;
			
			endforeach;
			
			return array($result, $total);
			
			
		
		}
		
		
		function compute_sla($data)
		{
			$sla = $data['sla'];
			
			$start = $this->date_ts($data['DATE_OUT']);
			$end = $this->date_ts($data['DATE_RCVD']);
			
			$oneday = 24 * 60 * 60;
			
			$weekend = array('Sat', 'Sun');
			
			for($i=$start; $i<=$end; $i+=$oneday)
			{
				if (in_array(date("D", $i), $weekend)) --$sla;
			}
			
			return $sla;
		}
		
		
		function date_ts($date)
		{
			list($y, $m, $d) = explode('-', $date);
			
			return mktime(0,0,0, $m, $d, $y);
		}
		
		
		function delete()
		{
		
			$id = implode(",", $_POST['idno']);
			
			$this->db->query("delete from tracking where idno in ($id)");
			
			$this->main();
		
		}
		
		function edit($id = 0, $message = "")
		{
		
			if ($id == 0)
			
				$data['val'] = $this->tracking_default();
			
			else
			
				$data['val'] = $this->get_details($id);
				
			$data['MESSAGE'] = $message;
			
			$data['RSLOC'] = $this->buildOptions($this->rsloc());
			
			switch($data['val']['DATA_TYPE'])
			{
			
				case 'RNSS':
				
					$this->load->view('admin/edit_rnss', $data);
				
				break;
				
				case 'WIRELESS':
				
					$this->load->view('admin/edit_wireless', $data);
				
				break;
				
				case 'RD_PD':
				
					$this->load->view('admin/edit_rdpd', $data);
				
				break;
			
			}
			
			
		
		}
		
		
		function get_details($id, $details = false)
		{
		
			$this->db->select('*');
			$this->db->select("date_format(pdate, '%m-%d-%Y') as pdate");
			
			$this->db->select("if(ETA = '0000-00-00', '', date_format(ETA, '%m-%d-%Y')) as ETA");					
			$this->db->select("if(ETD = '0000-00-00', '', date_format(ETD, '%m-%d-%Y')) as ETD");										
			$this->db->select("if(DATE_RCVD = '0000-00-00', '', date_format(DATE_RCVD, '%m-%d-%Y')) as DATE_RCVD");					
			$this->db->select("if(DATE_OUT = '0000-00-00', '', date_format(DATE_OUT, '%m-%d-%Y')) as DATE_OUT");					
			
			$this->db->where('idno', $id);
			
			$query = $this->db->get('tracking');
			
			$result = $query->result_array();
			
			//echo $this->db->last_query();
			
			return $result[0];
		
		}
		
		
		function sqlDate($date)
		{
		
			if (strlen($date))
			{
		
				list($m,$d,$y) = explode("-", $date);
				
				return "$y-$m-$d";
			
			}	
		
		}
		
		function save()
		{
		
			unset($_POST['cpdate'],
				  $_POST['cedate'],
				  $_POST['cddate'],
				  $_POST['ceddelivery'],
				  $_POST['rcvd_button'],
				  $_POST['submit'],
				  $_POST['cpdate1'],
				  $_POST['cpdate2'],
				  $_POST['cpdate3']);
			
			//$_POST['edate']		= $this->sqlDate($_POST['edate']);
			//$_POST['pdate']		= $this->sqlDate($_POST['pdate']);
			//$_POST['ddate']		= $this->sqlDate($_POST['ddate']);
			//$_POST['eddelivery']	= $this->sqlDate($_POST['eddelivery']);
			
			if (isset($_POST['ETA'])) $_POST['ETA'] = $this->sqlDate($_POST['ETA']);
			if (isset($_POST['ETD'])) $_POST['ETD'] = $this->sqlDate($_POST['ETD']);
			if (isset($_POST['DATE_RCVD'])) $_POST['DATE_RCVD'] = $this->sqlDate($_POST['DATE_RCVD']);
			if (isset($_POST['DATE_OUT'])) $_POST['DATE_OUT'] = $this->sqlDate($_POST['DATE_OUT']);
			
			$old = array();
		
			if ($_POST['idno'])
			{
			
				$id = $_POST['idno'];
				
				$old = $this->get_details($id);
				
				$this->db->where('idno', $id);	
				
				$this->db->update('tracking', $_POST);
				
				$message = "(Tracking details updated)";
				
				$action = 'Update';
			
			}
			else
			{
			
				$this->db->insert('tracking', $_POST);
				
				$id = $this->db->insert_id();
				
				$message = "(Tracking details added)";
				
				$action = 'Add';
			
			}
			
			if ($this->customer_id == 0)
			
				$this->update_all($id);
			
			$this->history_save($action, $id, $_POST, $old);
			
			
			$this->edit($id, $message);
		
		}
		
		
		function update_all($id)
		{
		
			$val = $this->get_details($id);
			
			$update['DATE_RCVD'] = $this->sqlDate($val['DATE_RCVD']);
			$update['ETA'] 		= $this->sqlDate($val['ETA']);
			$update['ETD'] 		= $this->sqlDate($val['ETD']);
			$update['remarks'] 	= $val['remarks'];
			$update['RECVD_BY'] = $val['RECVD_BY'];
			$update['status']	= $val['status'];
			
			$this->db->where('awb', $val['awb']);
			$this->db->update('tracking', $update);
		
		}
		
		
		function tracking_default($table = 'tracking')
		{
		
			$query = $this->db->query("desc $table");
			
			$result = $query->result_array();
			
			$default = array();
			
			foreach($result as $fld) $default[$fld['Field']] = $fld['Default'];
			
			return $default;
		
		}
		
		function pager($p)
		{
		
			$this->page = $p;
			
			$this->db->where('user_id', $this->speedex_uid);
			$query = $this->db->get('settings');
			
			$result = $query->row();
			
			if ($result)
			{
			
				$this->db->where('user_id', $this->speedex_uid);
				$this->db->update('settings', array('page' => $p));
			
			}
			else
			
				$this->db->insert('settings', array('user_id' => $this->speedex_uid,
												    'page' => $p));
			
			$this->main();
		
		}
		
		function search()
		{
		
			$this->search = $_POST['awb'];
			
			$search['user_id'] = $this->speedex_uid;
			$search['search'] = $this->search;
			$search['page'] = 1;
			
			$this->db->query("delete from settings where user_id = $this->speedex_uid limit 1");
			
			$this->db->insert("settings", $search);
			
			$this->main();
		
		}
		
		function upload()
		{
			$DATA['LIST'] = array();
			$DATA['UPDATE'] = 0;
			$DATA['TOTAL'] = 0;
			$DATA['ADD'] = 0;
			
			$this->load->view('admin/upload', $DATA);
		
		}
		
		function awbreset()
		{
		
			if ($this->customer_id > 0)
		
				$this->db->query("delete from tracking where customer_id = $this->customer_id");
			
			$this->main();
		
		}
		
		
		function delete_file()
		{
			$files = $_POST['files'];			
			foreach($files as $file)
			{
				$dir = "upload/speedex/" . $this->assign_to . '/' ;
				if ($this->customer_id) $dir = 'upload/' . $this->customer_id . '/';
				$path = $dir.$file;
				if (file_exists($path)) unlink($path);
			}
			
			$this->upload();
		}
		
		var $field = array();
		
		
		function process()
		{
		
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
		
		
		function get_type($data)
		{
			$this->field = array();	
			foreach($data as $key => $val) $this->field[] = strtoupper(str_replace(array(' ','.'),array('_',''),$val));
		}
		
		
		function process_date($date)
		{
		
			return date("Y-m-d", strtotime($date));
		
		}
		
		
		function awb_update($data)
		{
		
			
					
			$pdate 		= $this->process_date($pdate);
			$ddate 		= $this->process_date($ddate);
			
				  
			$this->db->where('awb', $awb);
			
			$query = $this->db->get('tracking');
			
			$result = $query->row();
			
			if ($result)
			{
			
				$this->db->where('idno', $result->idno);
				$this->db->update('tracking', $val);
				
				$id = $result->idno;
				
				$action = 'Process: Updated';
			
			}
			else
			{
			
				$this->db->insert('tracking', $val);
				
				$id = $this->db->insert_id();
				
				$action = 'Process: Add';
				
			}	
				
			$this->history_save($action, $id, $val);
		
		}
		
		
		function customer_update($data)
		{
		  	
			for($i=0; $i<count($this->field); $i++) $val[$this->field[$i]] = $data[$i];
			
			if (isset($val['DATE_EMAIL'])) 	$val['DATE_EMAIL'] 			= $this->process_date($val['DATE_EMAIL']);
			if (isset($val['DATE_OUT'])) 	$val['DATE_OUT'] 			= $this->process_date($val['DATE_OUT']);
			if (isset($val['ETD'])) 		$val['ETD'] 				= $this->process_date($val['ETD']);
			if (isset($val['ETA'])) 		$val['ETA'] 				= $this->process_date($val['ETA']);
			if (isset($val['ACTUAL_DATE'])) 	$val['ACTUAL_DATE'] 	= $this->process_date($val['ACTUAL_DATE']);
			
			if (isset($val['DATE_RCVD'])) $val['DATE_RCVD'] = $this->process_date($val['DATE_RCVD']);
			
			if (isset($val['CREATED_ON']))
			{ 	
				$val['DATE_OUT'] 	= $this->process_date($val['CREATED_ON']);
				
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
			
				if (in_array('RNSS', 	$this->field)) $val['DATA_TYPE'] = 'RNSS';
				if (in_array('RSLOC', 	$this->field)) $val['DATA_TYPE'] = 'WIRELESS';
				if (in_array('SD_DOC', 	$this->field)) $val['DATA_TYPE'] = 'RD_PD';
			
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
		
		
		function set_eta($date, $area)
		{
		
			list($y, $m, $d) = explode('-', $date);
			
			switch ($area)
			{
			
				case 'NCR':
				
					$d = (int)$d + 1;
				
				break;
				
				case 'LUZON':
				
					$d = (int)$d + 2;
				
				break;
				
				case 'MIN':
				case 'VIZ':
				
					$d = (int)$d + 4;
				
				break;
			
			
			}
			
			
			$eta = "$y-$m-$d";
			$etd = "$y-$m-$d";
			
			return array($eta, $etd);
			
		
		
		}
		
		function file_upload()
		{
		
			$server = 'upload/speedex/' . $this->assign_to . '/';
			
			//if ($this->speedex_uid > 1) $server .= $this->customer_id . '/';
			
			if ($this->customer_id > 0) $server = 'upload/' . $this->customer_id . '/';
			
			if (!file_exists($server))
			{
			
				if (mkdir($server, 0777)) {} else echo "error!!!";
			
			}
			else
			
				chmod($server, 0777);
	
			$filename = $_FILES['UPLOAD']['name'];
			
			
			if (!(rename($_FILES['UPLOAD']['tmp_name'], $server . $filename))) 
			{ 
				$msg = "Upload error!";	
				$success = 0;
			
			} 
			else 
			{
		
				$msg = "File upload success.";
				$success = 1;
		
			}
			
			
			
			echo "<script language='javascript' type='text/javascript'>
					window.top.window.upload('$msg');
				  </script>"; 
				  
			
		
		}
		
		
		function details($id)
		{
			
			$val = $this->get_details($id, true);
		
			$this->load->view('admin/details', array('val' => $val));
		
		}
		
		
		function logout()
		{
		
			session_destroy();
			
			header("Location: ".base_url()."login");
		
		}
		
		
		function customer()
		{
			list($list, $total) = $this->get_customer();
		
			$CUSTOMER['LIST'] = $list;
		
			$DATA['LIST'] = $this->load->view('admin/customer_list', $CUSTOMER, true);
		
			$DATA['PAGER'] = $this->load->view('admin/customer_pager', array('TOTAL' => $total, 'result' => count($list)), true);
		
			$this->load->view('admin/customer', $DATA);
		
		}
		
		
		function get_customer()
		{
		
			/*
			if (strlen($this->search))
			
				$this->db->where("awb", $this->search);
			*/	
			
			//$this->db->where("name not in ('admin')");
			
			if ($this->page == 1)
			
				$this->db->limit($this->max_limit);
				
			else
			
				$this->db->limit($this->max_limit, ($this->page - 1) * $this->max_limit);
				
			$this->db->orderby('company_name');	
		
			$query = $this->db->get('company');
			
			$result = $query->result_array();
			
			$sql = explode('LIMIT', $this->db->last_query());
			
			$query = $this->db->query($sql[0]);
			
			$total = $query->num_rows();
			
			return array($result, $total);
		
		}
		
		
		function customer_edit($id, $message = "")
		{
		
			//echo $id;
		
			if ($id)
			{
			
				$val = $this->customer_details($id);
			
			
			}
			else
			{
			
				$val = $this->tracking_default('company');
			
			}
			
			//print_r($val);
			
			$DATA['val'] = $val;
			$DATA['MESSAGE'] = $message;
			
			$this->load->view('admin/customer_edit', $DATA);
		
		}
		
		
		function customer_details($id)
		{
		
			$this->db->where('company_id', $id);
			
			$query = $this->db->get('company');
			
			$result = $query->result_array();
			
			//echo $this->db->last_query();
			
			return $result[0];
		
		
		}
		
		
		function saveCustomer()
		{
		
			if ($_POST['company_id'])
			{
			
				$this->db->where('company_id', $_POST['company_id']);
				
				$this->db->update('company', $_POST);
				
				$id = $_POST['company_id'];
				
				$msg = "(Customer details updated)";
				
			
			}
			else
			{
			
				$this->db->insert('company', $_POST);
				
				$id = $this->db->insert_id();
				
				$msg = "(Company added)";
			
			}
			
			$this->customer_edit($id, $msg);
		
		}
		
		
		function customer_delete($id)
		{
		
			$this->db->query("delete from company where company_id = $id limit 1");
			
			$this->customer();
		
		}
		
		
		function password($message = "")
		{
		
			$DATA['MESSAGE'] = $message;
		
			$this->load->view('admin/password', $DATA);
		
		}
		
		
		
		function save_password()
		{
		
			$this->db->where('userid', $this->speedex_uid);
			
			$query = $this->db->get('user');
			
			$result = $query->row();
			
			if ($result->password == $_POST['password'])
			{
			
				$this->db->where('userid', $this->speedex_uid);
				
				$this->db->update('user', array('password' => $_POST['password1']));
			
				$msg = "(SUCCESS! password changed)";
			
			}
			else
			
				$msg = "(FAILED! password is not correct)";
			
			
			
			$this->password($msg);
		
		}
		
		var $report_type = 1;
		var $week = 0;
		var $month = 0;
		var $year;
		
		function report()
		{
		
			$this->year = date("Y");
			$this->week = date("W");
			$this->month = date("m");
		
			$MAIN['TYPE'] = array('Choose One','Monthly','Weekly','Yearly','Other');
			$MAIN['MONTH'] = array('Mo', 'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
			$MAIN['YEAR'] = $this->tracking_year();
			
			for($i=0; $i<=53; $i++) $week[] = $i;
			
			$MAIN['WEEK'] = $week;
		
			$this->load->view('admin/report', $MAIN);
		
		}
		
		
		function tracking_year()
		{
		
			$this->db->where('YEAR(DATE_OUT) > 0');
		
			$this->db->select("YEAR(DATE_OUT) as year");
			
			$this->db->orderby('date_out');
			
			$this->db->groupby("YEAR(DATE_OUT)");
			
			$query = $this->db->get('tracking');
			
			$result = $query->result_array();
			
			$year[$this->year] = $this->year;
			
			foreach($result as $val) $year[$val['year']] = $val['year'];
			
			return $year;
		
		}
		
		var $default_field = array();
		
		var $field_name = array();
		
		var $field_width = array();
		
		var $report_field = array('GMF',
								  'RO',
								  'RNSS',
								  'MATCODE',
								  'MAT_DESC',
								  'QTY',
								  'REF_SERIES_NUM',
								  'REQUEST_BY',
								  'REQUEST_DEPT',
								  'TRANS_TYPE',
								  'AREAS',
								  'consignee',
								  'ADDRESS',
								  'SPC_NOTES',
								  'DATE_EMAIL',
								  'DATE_OUT',
								  'awb');
								  
		var $report_field_width = array(11,
										11,
										11,
										11,
										22,
										11,
										17,
										15,
										15,
										15,
										15,
										22,
										11,
										11,
										11,
										11);
										
								  
		function doreport()
		{
		
			//echo $params;
			
			$type = $_POST['report_type'];
			$mode = $_POST['action'];
			$month = $_POST['month'];
			$year = $_POST['year'];
			$todate = $_POST['todate'];
			$fromdate = $_POST['fromdate'];
			$rtype = $_POST['dtype'];
			
			$summary = 0;
			
			if (isset($_POST['summary'])) $summary = 1;
			
			$fields = array_keys($_POST['fields']);
			
			
			
			
			
			/*
			list($type,
				 $month,
				 $year,
				 $week,
				 $todate,
				 $fromdate,
				 $summary,	
				 $mode, 				 			 
				 $rtype) = explode('_', $params);
			
			*/
				 
			//echo "type: $type month: $month year:$year week:$week todate:$todate fromdate:$fromdate summary:$summary mode:$mode rtype:$rtype";	 
				 
			//echo $rtype;
			
			//echo $summary;
			
			//print_r($field);
			
			
			switch ($type)
			{
			
				case 1: //monthly
				
					$start = date("Y-m-d", mktime(0,0,0, $month, 1, $year));
					$end = date("Y-m-d", mktime(0,0,0,$month, date("t", mktime(0,0,0,$month,1,$year)), $year));
				
				break;
				
				case 2: //weekly
				
					$weekdays = $this->date_utility->getWeekDays_yw($year, $week);
					
					$start = $weekdays[0];
					$end = $weekdays[6];
				
				break;
				
				case 3: //year
				
					$start = date("Y-m-d", mktime(0,0,0,1,1,$year));
					$end = date("Y-m-d",mktime(0,0,0,12,31,$year));
				
				break;
				
				case 4: //other
				
					$start = $todate;
					$end = $fromdate;
				
				break;
			
			}	
			
			list($data, $performance) = $this->get_report($start, $end, $rtype, $summary, $fields);
			
			//print_r($data);
			
			
			
			if ($mode == 'screen')
			{
				$DATA['data'] = $data;
				$DATA['PERFORMANCE'] = $this->load->view('admin/performance', array('data' => $performance, 'fields' => $fields), true);
			
				$this->load->view('admin/screen_report', $DATA);
				
			}
			else
			
				$this->excel($data, $performance, $fields);	 
			
			
		
		}
		
		
		function compute_days($data)
		{
			
			$sla = $data['DAYS'];
			
			$start = $this->date_ts($data['DATE_OUT']);
			$end = $this->date_ts($data['DATE_RCVD']);
			
			$oneday = 24 * 60 * 60;
			
			$weekend = array('Sat', 'Sun');
			
			for($i=$start; $i<$end; $i+=$oneday)
			{
				if (in_array(date("D", $i), $weekend)) --$sla;
			}
			
			return $sla;
			
		}
		
		
		function get_report($start, $end, $rtype, $summary, $fields)
		{
		
			$TYPE = array(1 => 'RNSS',
						  2 => 'WIRELESS',
						  3 => 'RD_PD');
		
			$this->db->where("DATE_OUT >= '$start'");
			$this->db->where("DATE_OUT <= '$end'");
			
			$this->db->where('DATA_TYPE', $TYPE[$rtype]);
			
			//$field = implode(',', $this->field_name);
			
			$this->db->select("awb");
			
			if (in_array('GMF', $fields)) $this->db->select("GMF");
			
			if (in_array('RO',$fields)) $this->db->select("RO");
			
			if (in_array('RNSS', $fields)) $this->db->select("RNSS");
			
			if (in_array('MATCODE', $fields)) $this->db->select("MATCODE");
			
			if (in_array('MAT_DESC', $fields)) $this->db->select("MAT_DESC");
			
			if (in_array('QTY', $fields)) $this->db->select("QTY");
			
			$this->db->select("t.AREAS");			
			$this->db->select("IF(AREAS = 'NCR', 1, IF(AREAS IN ('LUZON','NL','SL','CL'), 2, 4) ) AS SLA");
			
			if (in_array('CONSIGNEE', $fields)) $this->db->select("CONSIGNEE");
			if (in_array('DATE_RLS', $fields)) $this->db->select("DATE_OUT");
			if (in_array('DATE_RCVD', $fields)) $this->db->select("IF(YEAR(DATE_RCVD) > 0, DATE_RCVD, '---') AS DATE_RCVD");
			
			$this->db->select("IF(status = 'DELIVERED', DATEDIFF(DATE_RCVD,DATE_OUT), '---') AS DAYS");
			
			if (in_array('RECVD_BY', $fields)) $this->db->select("RECVD_BY");
			if (in_array('REMARKS', $fields)) $this->db->select("REMARKS");
			if (in_array('STATUS', $fields)) $this->db->select("STATUS");
			
			if (isset($_POST['awb_group']))
			
				$this->db->groupby("awb");
			
			$query = $this->db->get('tracking as t');
			
			$result = $query->result_array();
			
			ini_set('memory_limit', '512M');			
			
			foreach($result as $key => $row)
			
				if (isset($_POST['DAYS']))
				{
					if ($row['DAYS'] != '---')
			
						$result[$key]['DAYS'] = $this->compute_days($row);
						
				}
			
			
			//echo $this->db->last_query();
			
			//$result = array();
			
			$performance = array('within' => 0,
								'ahead' => 0,
								'delayed' => 0,
								'total' => count($result));
			
			
			$awb = array();
			
			if ($summary)
			{
				
				foreach($result as $row)
				{
					if (!in_array($row['awb'], $awb))
					{
					
						if (in_array($row['AREAS'], array('NCR','LUZON','SL','NL')))
						{
							if ($row['DAYS'] < $row['SLA'])
						
								$performance['ahead']++;
							
							else if ($row['DAYS'] > $row['SLA'])	
						
								$performance['delayed']++;
						
							else if ($row['DAYS'] == $row['SLA'])
						
								$performance['within']++;
						}
						else
						{
							if ($row['DAYS'] < $row['SLA'])
						
								$performance['ahead']++;
							
							else if ($row['DAYS'] >= $row['SLA'])
						
								$performance['within']++;
						
						}
						
						$awb[] = $row['awb'];
						
					
					}
					
				}
				
				$performance['total'] = count($awb);
			
				/*
				$sql = explode('WHERE', $this->db->last_query());
				
				//WITHIN
				
				$q1 = $this->db->query("SELECT count(*) AS WITHIN FROM tracking as t WHERE ".$sql[1]." AND DATE_RCVD != '0000-00-00' AND DATE_RCVD = ADDDATE(DATE_OUT, INTERVAL IF(AREAS='NCR', 1, IF(AREAS='LUZON', 2, 4)) DAY)");
				
				$r1 = $q1->row_array();
				
				//AHEAD
				
				$q2 = $this->db->query("SELECT count(*) AS AHEAD FROM tracking as t WHERE ".$sql[1]." AND DATE_RCVD != '0000-00-00' AND DATE_RCVD < ADDDATE(DATE_OUT, INTERVAL IF(AREAS='NCR', 1, IF(AREAS='LUZON', 2, 4)) DAY)");
				
				$r2 = $q2->row_array();
				
				//DELAYED
				
				$q3 = $this->db->query("SELECT count(*) AS DELAYE FROM tracking as t WHERE " . $sql[1] . " AND (IF(DATE_RCVD = '0000-00-00', DATE(NOW()) > ADDDATE(DATE_OUT, INTERVAL IF(AREAS='NCR', 1, 2) DAY), DATE_RCVD > ADDDATE(DATE_OUT, INTERVAL IF(AREAS='NCR', 1, IF(AREAS IN ('LUZON','SL','NL','CL'), 2, 4) ) DAY)) AND AREAS IN ('NCR','LUZON'))");
				
				$q5 = $this->db->query("SELECT count(*) AS DELAYE FROM tracking as t WHERE " . $sql[1] . " AND (IF(DATE_RCVD = '0000-00-00', DATE(NOW()) > ADDDATE(DATE_OUT, INTERVAL 4 DAY), DATE_RCVD > ADDDATE(DATE_OUT, INTERVAL 4 DAY)) AND AREAS NOT IN ('NCR','LUZON'))");
				
				$r5 = $q5->row_array();
				
				$r3 = $q3->row_array();
				
				$q4 = $this->db->query("SELECT * FROM tracking WHERE " . $sql[1]);
				
				$r4 = $q4->num_rows();
				
				$performance['within'] 	= $r1['WITHIN'] + $r5['DELAYE'];
				$performance['ahead'] 	= $r2['AHEAD'];
				$performance['delayed'] = $r3['DELAYE'];
				$performance['total']	= $r4;							
				
				*/
				
			
			}
			
			
			return array($result, $performance);
		
		}
		
		
		
		
		
		function excel($data, $performance, $fields)
		{
			
		
			require_once "Spreadsheet/Excel/Writer.php";
		
			$workbook = new Spreadsheet_Excel_Writer();
			
			$workbook->setVersion(8);
			
			//$this->format_sheet();
			
			$workbook->setCustomColor(31, 195,195,195);
			$workbook->setCustomColor(32, 250,250,250);
			
			$header =& $workbook->addFormat();
			$header->setBold();
			$header->setSize(10);
			$header->setFgColor(31);
			$header->setAlign('center');
			
			
			$even =& $workbook->addFormat();
			$even->setFgColor(32);
			$even->setTextWrap();
			$even->setAlign('vcenter');
			$even->setAlign('center');
			
			$odd =& $workbook->addFormat();
			$odd->setTextWrap();
			$odd->setAlign('vcenter');
			$odd->setAlign('center');
			
			$normal =& $workbook->addFormat();
			$normal->setTextWrap();
			$normal->setAlign('vcenter');
			
			$format_head =& $workbook->addFormat();
			$format_head->setAlign('vcenter');			
			$format_head->setBold(700);
			
			$worksheet =& $workbook->addWorksheet('SPEEDEX Report');
			
			$worksheet->setColumn(0,0, 1);
			
			$worksheet->setLandscape();
			
			
			$y = 1;
			
			$headers = array('AWB'		=> 'AWB',
							 'GMF' 		 => 'GMF',
							 'RO' 		 => 'RO',
							 'RNSS' 	 => 'RNSS',
							 'MATCODE' 	 => 'MATCODE',
							 'MAT_DESC'  => 'MAT_DESC',
							 'QTY' 		 => 'QTY',
							 'AREAS'	 => 'AREAS',
							 'SLA'		 => 'SLA',
							 'CONSIGNEE' => 'CONSIGNEE',
							 'DATE_RLS'		  => 'DATE RLS',
							 'DATE_RCVD'	  => 'DATE RCVD',
							 'DAYS' 		  => '#DAYS DELIVERED',
							 'RECVD_BY'		  => 'RECVD BY',
							 'REMARKS'		  => 'REMARKS',
							 'STATUS'		  => 'STATUS');
			
			$widths = array('AWB'		=> 15,
							'GMF' 		 => 15,
					 	   'RO' 		 => 15,
						   'RNSS' 	 => 15,
						    'MATCODE' 	 => 15,
							 'MAT_DESC'  => 30,
							 'QTY' 		 => 5,
							 'AREAS'	 => 5,
							 'SLA'		 => 5,
							 'CONSIGNEE' => 22,
							 'DATE_RLS'		  => 12,
							 'DATE_RCVD'	  => 12,
							 'DAYS' 		  => 12,
							 'RECVD_BY'		  => 22,
							 'REMARKS'		  => 12,
							 'STATUS'		  => 12);
		
		
				
			foreach($fields as $str)
			{
				$this->field_name[] = $headers[$str];
				$this->field_width[] = $widths[$str];
			}
			
			//print_r($this->field_name);
			
			//$this->field_name =  array('GMF','RO','RNSS','MATCODE','MAT_DESC','QTY','AREAS','SLA','CONSIGNEE','DATE RLS','DATE RCVD','#DAYS DELIVRED','RCVD BY', 'REMARKS');
			
			//$this->field_width = array(15,    15,  15,    15,       30,       5,   5,       5, 22,          12,       12,            12, 22,            12);
			
			for($i=0; $i<count($this->field_name); $i++)
			{
				$worksheet->setColumn($i+1, $i+1, $this->field_width[$i]);
				$worksheet->write($y, $i+1, strtoupper($this->field_name[$i]), $header);
			
			}	
				
			$y++;
			
			$n = 1;
				
			foreach($data as $row)
			{
				$format = $odd;
				
				if (($n++ % 2) == 0) $format = $even;
				
				$xx = 1;
				
				//$worksheet->write($y, $xx++, $row['DATE_OUT'], $format);
				//$worksheet->write($y, $xx++, $row['awb'], $format);
				//$worksheet->write($y, $xx++, $row['CONSIGNEE'], $format);
				//$worksheet->write($y, $xx++, $row['RECVD_BY'], $format);
				//$worksheet->write($y, $xx++, $row['DATE_RCVD'], $format);
				//$worksheet->write($y, $xx++, $row['STATUS'], $format);
				
				if (!in_array('AWB', $this->field_name)) unset($row['awb']);
				if (!in_array('#DAYS DELIVERED', $this->field_name)) unset($row['DAYS']);
				if (!in_array('AREAS', $this->field_name)) unset($row['AREAS']);
				if (!in_array('SLA', $this->field_name)) unset($row['SLA']);
				
				foreach($row as $val) $worksheet->write($y, $xx++, $val, $format);
				
				
				
				$y++;
				
			
			}	
			
			$format = $normal;
			
			$yy = $y + 2;
			
			if ($performance)
			{
				
				$worksheet->write($yy, 1, "PERFORMANCE SUMMARY", $format_head);
				$worksheet->writeBlank($yy, 2, $format);
				$worksheet->mergeCells($yy, 1, $yy, 2);
				
				
				$worksheet->write(++$yy, 1, "TOTAL", $format);
				$worksheet->writeBlank($yy, 2, $format);
				$worksheet->write($yy, 3, number_format($performance['total'],0,'.',','));
				$worksheet->mergeCells($yy, 1, $yy, 2);
				
				$worksheet->write(++$yy, 1, "% OF AHEAD OF SLA", $format);
				$worksheet->writeBlank($yy, 2, $format);
				$worksheet->write($yy, 3, number_format($performance['total'] > 0 ? ($performance['ahead']/$performance['total'] * 100) : 0,2,'.',','). " (".number_format($performance['ahead'], 0, '.',',').") ");
				$worksheet->mergeCells($yy, 1, $yy, 2);
				
				$worksheet->write(++$yy, 1, "% OF WITHIN SLA", $format);
				$worksheet->writeBlank($y, 2, $format);
				$worksheet->write($yy, 3, number_format($performance['total'] > 0 ? ($performance['within']/$performance['total'] * 100) : 0,2,'.',','). " (".number_format($performance['within'], 0, '.',',').") ");
				$worksheet->mergeCells($yy, 1, $yy, 2);
				
				$worksheet->write(++$yy, 1, "% OF DELAYED IN SLA", $format);
				$worksheet->writeBlank($y, 2, $format);
				$worksheet->write($yy, 3, number_format($performance['total'] > 0 ? ($performance['delayed']/$performance['total'] * 100) : 0,2,'.',','). " (".number_format($performance['delayed'], 0, '.',',').") ");
				$worksheet->mergeCells($yy, 1, $yy, 2);
				
				
			
			}
			
			
			$workbook->send('speedex_'.mktime().'.xls');
			$workbook->close();
		
		}
		
		
		function buildOptions($result,$prep = false) 
		{	
	
			$opts[0] = '';
			foreach($result as $key => $val){
				$keys = array_keys($val);
				$optVal = $val[$keys[0]];
				$optText = $val[$keys[1]];
				$opts[$optVal] = $optText;
			}
		
			if (!$prep) {
				$opts[0] = 'Choose One';  
			} else {
				$opts[0] = $prep;
			}
	
			if (!empty($opts))	{
			//ksort($opts); 
			}
				else if(empty($opts))
		 	{
				$opts[0][0] = ''; 
			}
			
			return $opts;
		
		}
		
		
		function rsloc()
		{
		
			$this->db->where('dtype', 'sloc');
		
			$this->db->select('dloc, dname');
			
			$this->db->orderby('dname');
			
			$query = $this->db->get('locations');
			
			$result = $query->result_array();
			
			return $result;
		
		}
		
		
		function user()
		{
		
			list($list, $total) = $this->get_user();
		
			$CUSTOMER['LIST'] = $list;
		
			$DATA['LIST'] = $this->load->view('admin/user_list', $CUSTOMER, true);
		
			$DATA['PAGER'] = $this->load->view('admin/user_pager', array('TOTAL' => $total, 'result' => count($list)), true);
		
			$this->load->view('admin/user', $DATA);
		
		}
		
		
		function get_user()
		{
		
			$this->db->where('userid > 1');
			
			if ($this->page == 1)
			
				$this->db->limit($this->max_limit);
				
			else
			
				$this->db->limit($this->max_limit, ($this->page - 1) * $this->max_limit);
				
			$this->db->orderby('name');	
			
			$this->db->select("u.*");
			
			$this->db->select("if(c.company_id > 0, c.company_name, 'Speedex') as company_id");
			
			$this->db->join('company as c', 'c.company_id = u.company_id','left');
		
			$query = $this->db->get('user as u');
			
			$result = $query->result_array();
			
			$sql = explode('LIMIT', $this->db->last_query());
			
			$query = $this->db->query($sql[0]);
			
			$total = $query->num_rows();
			
			return array($result, $total);
			
		
		}
		
		
		function user_edit($id, $message = "")
		{
		
			if ($id)
			{
			
				$val = $this->user_details($id);
			
			
			}
			else
			{
			
				$val = $this->tracking_default('user');
			
			}
			
			//print_r($val);
			
			$DATA['val'] = $val;
			$DATA['MESSAGE'] = $message;
			$DATA['COMPANY'] = $this->buildOptions($this->company());
			
			$this->load->view('admin/user_edit', $DATA);
		
		}
		
		
		function company()
		{
		
			$this->db->select('company_id, company_name');
			
			$this->db->orderby('company_name');
			
			$query = $this->db->get('company');
			
			$result = $query->result_array();
			
			return $result;
		
		}
		
		
		function saveUser()
		{
		
			if ($_POST['userid'])
			{
			
				$this->db->where('userid', $_POST['userid']);
				
				$this->db->update('user', $_POST);
				
				$id = $_POST['userid'];
				
				$msg = "(User details updated)";
				
			
			}
			else
			{
			
				$this->db->insert('user', $_POST);
				
				$id = $this->db->insert_id();
				
				$msg = "(User added)";
			
			}
			
			$this->user_edit($id, $msg);
		
		}
		
		
		function history($id)
		{
		
			$LIST['history'] = $this->get_history($id);
		
			$data['LIST'] = $this->load->view('admin/history', $LIST, true);
			
			$data['FORM'] = 'History';
			$data['UPDATE'] = "";
		
			$this->load->view('admin/popup', $data);
		
		}
		
		
		function get_history($id)
		{
		
			$this->db->where('tracking_id', $id);
			
			$query = $this->db->get('history');
			
			$result = $query->result_array();
			
			return $result;		
		
		}
		
		function history_save($action, $id, $data, $olddata = array())
		{
		
			$name = $this->get_user_name();
			
			$details = array();
			
			switch ($action)
			{
			
				case 'Update':
				
					foreach($data as $key => $val)
					{
					
						if ($data[$key] != $olddata[$key]) $details[] = "<strong>$key:</strong> $val";
					
					}
				
				
				break;
				
				default:
				
					unset($data['customer_id']);
				
					foreach($data as $key => $val)
					
						$details[] = "<strong>$key:</strong> $val";
			
			
			}
			
			$his['date'] = date("Y-m-d");
			$his['update_by'] = $name;
			$his['action'] = $action;
			$his['tracking_id'] = $id;
			$his['details'] = ($details)?implode("<br>", $details):"No update.";
			
			$this->db->insert('history', $his);
		
		}
		
		
		function get_user_name()
		{
		
			$this->db->where('userid', $this->speedex_uid);
			
			$query = $this->db->get('user');
			
			$result = $query->row();
			
			return $result->company_name;
		
		}
		
		
		function printlist()
		{
			list($data, $total) = $this->tracking_list();
			
			$TRACKING['LIST'] = $data;
			
			$DATA['PAGER'] = $this->load->view('admin/pager', array('TOTAL' => $total, 'result' => count($data)), true);
			
			$TRACKING['LIST'] = $this->process_data($data);
			
			//$DATA['LIST'] = $this->load->view('admin/tracking_customer_list', $TRACKING, true);
			
			$this->load->view('admin/printlist', $TRACKING);
			
		
		}
		
		
		function excellist()
		{
		
			list($data, $total) = $this->tracking_list();
			
			$this->excel2($data);
			
		
		}
		
		function excel2($data)
		{
		
			require_once "Spreadsheet/Excel/Writer.php";
		
			$workbook = new Spreadsheet_Excel_Writer();
			
			$workbook->setVersion(8);
			
			//$this->format_sheet();
			
			$workbook->setCustomColor(31, 195,195,195);
			$workbook->setCustomColor(32, 250,250,250);
			
			$header =& $workbook->addFormat();
			$header->setBold();
			$header->setSize(10);
			$header->setFgColor(31);
			$header->setAlign('center');
			
			
			$even =& $workbook->addFormat();
			$even->setFgColor(32);
			$even->setTextWrap();
			$even->setAlign('vcenter');
			$even->setAlign('center');
			
			$even_red =& $workbook->addFormat();
			$even_red->setFgColor(32);
			$even_red->setTextWrap();
			$even_red->setAlign('vcenter');
			$even_red->setAlign('center');
			$even_red->setColor('red');
			
			$odd =& $workbook->addFormat();
			$odd->setTextWrap();
			$odd->setAlign('vcenter');
			$odd->setAlign('center');
			
			$odd_red =& $workbook->addFormat();
			$odd_red->setTextWrap();
			$odd_red->setAlign('vcenter');
			$odd_red->setAlign('center');
			$odd_red->setColor('red');
			
			$worksheet =& $workbook->addWorksheet('SPEEDEX Report');
			
			$worksheet->setColumn(0,0, 1);
			
			$worksheet->setLandscape();
			
			
			$y = 1;
			
			$this->field_name =  $this->selected_field;
			
			
			$this->field_width = array( 'GMF/DR'     => 15,    
										'SO/RO'     => 15, 
										'ID' 		=> 15,
										'PO'		=> 15,
										'SR'		=> 15,
										'RNSS' 	    => 15,    
										'MATCODE'   => 15,       
										'MAT_DESC'  => 30,       
										'QTY'		=> 5,  
										'CONSIGNEE' => 22,
										'DATE OUT'  => 12,
										'DATE RCVD' =>  12,
										'RCVD BY'   =>  22,  
										'REMARKS'   =>  12,
										'STATUS'	=> 12,
										'WAYBILL'	=> 12,
										'ETA'		=> 12,
										'ETD'		=> 12,
										'SLA'		=> 5,
										'# DAYS DELIVERED' => 12,
										'SLA'	   => 12,
										'AREAS'	    => 12,
										'RECVD BY'	=> 22);
			
			for($i=0; $i<count($this->selected_field); $i++)
			{
				$worksheet->setColumn($i+1, $i+1, $this->field_width[$this->fields[$this->selected_field[$i]]]);
				$worksheet->write($y, $i+1, strtoupper($this->field_name[$i]), $header);
			
			}	
				
			$y++;
			
			$n = 1;
				
			foreach($data as $row)
			{
				$isOdd = true;
				$sla = false;
								
				if (($n++ % 2) == 0) $isOdd = false;
				
				if ($row['sla'] > $row['asla'] && $row['DATE_RCVD'] == '---') $sla = true;
				
				$xx = 1;
				
				if ($isOdd)
				{
				
					if ($sla) $format = $odd_red; else $format = $odd;
				
				}
				else
				{
				
					if ($sla) $format = $even_red; else $format = $even;
				
				}
				
				foreach($this->selected_field as $val) $worksheet->write($y, $xx++, $row[$val], $format);
				
				$y++;
				
			
			}	
			
			
			$workbook->send('speedex_'.mktime().'.xls');
			$workbook->close();
		
		}
		
		
		function settings()
		{
			$this->db->where('user_id', $this->speedex_uid);
			
			$query = $this->db->get('settings');
			
			$result = $query->row();
			
			$FIELDS = $this->fields;
			$val['max_limit'] = $this->max_limit;
			
			if ($result)
			{
				$val['max_limit'] = $result->max_limit;
				
				if (strlen($result->fields))
				{
					
					$fields = explode(',', $result->fields);
					
					$FIELDS = array();
					
					foreach($fields as $str)
					
						$FIELDS[$str] = "";
					
				}
				
			}
			
			$SETTINGS['val'] = $val;
			$SETTINGS['FIELDS'] = $FIELDS;
		
			$data['LIST'] = $this->load->view('admin/settings', $SETTINGS, true);
			
			$data['FORM'] = 'Settings';
			$data['UPDATE'] = "<input type='button' value='Update' onclick='save_settings()' class='update'>";
		
			$this->load->view('admin/popup', $data);
		
		}
		
		
		function save_settings()
		{
			//$this->db->query("DELETE FROM settings WHERE user_id = $this->speedex_uid");
			
			$this->db->where('user_id', $this->speedex_uid);
			
			$query = $this->db->get('settings');
			
			$result = $query->row();
			
			if ($result)
			{
				
				$this->db->where('user_id', $this->speedex_uid);
				$this->db->update("settings", array('user_id' => $this->speedex_uid,
													'max_limit' => $_POST['max_limit'],
													'fields' => implode(',', $_POST['fields'])));
			
			}
			else
			
				$this->db->insert("settings", array('user_id' => $this->speedex_uid,
													'max_limit' => $_POST['max_limit'],
													'fields' => implode(',', $_POST['fields'])));
			
			$this->main();
			
		}
		
		function daily()
		{
			list($list, $TOTAL) = $this->get_daily();
			
			$forPager['TOTAL'] = $TOTAL;
			$forPager['result'] = count($list);
			
			$data['list'] = $list;
			
			$data['PAGER'] = $this->load->view('admin/daily_pager', $forPager, true);
			
			$this->load->view('admin/daily', $data);
		}
		
		
		function daily_pager($p)
		{
			$this->page = $p;
			$this->daily();
		}
		
		
		function get_daily()
		{
			$this->db->select("*");
			$this->db->select("concat_ws(' ', date_format(Date, '%M %d, %Y %h:%i'), IF(HOUR(Date) > 12, 'PM', 'AM')) as Date");
			
			$this->db->orderby('Date DESC');
			
			if ($this->page == 1)
			
				$this->db->limit($this->max_limit);
				
			else 
			
				$this->db->limit($this->max_limit, ($this->page - 1) * $this->max_limit);
			
			$query = $this->db->get('daily');
			
			$result = $query->result_array();
			
			$sql = explode('LIMIT', $this->db->last_query());
			
			$query = $this->db->query($sql[0]);
			
			$total = $query->num_rows();
			
			return array($result, $total);
		}
    
    }


?>