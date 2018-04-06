<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/wp-config.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/wp-includes/wp-db.php';


function saved_search_cron()
{   
	global $wpdb;
	require_once( 'flexmls_connect.php' );
	include ("lib/Mandrill.php");
	$mandrill = new Mandrill('hMuUQAqpVG9JiVjZ8ObXgQ');
	$FlexMLS_IDX = new FlexMLS_IDX();
	$search_arr = array();
	$start_date = date('Y-m-d', strtotime("-1 days"));
	$stop_date = date('Y-m-d');			
	$search_arr['MinMajorChangeTimestamp'] =  $start_date.'T12:00:00';
	$search_arr['MaxMajorChangeTimestamp'] =  $stop_date.'T12:00:00';

	$sql = "SELECT * FROM `saved_search` WHERE `active` = 1";
	$results = $wpdb->get_results($sql);
	if(!empty($results))
	{
		foreach( $results as $result ) 
		{
			$user_info = get_user_by('id',$result->user_id);		
			

			$search_arr = array();
			if(!empty($result->search_data))
			{
				$search_data = json_decode($result->search_data, true);
				if(!empty($search_data['Baths']))
				{
					$search_arr['MaxBaths'] = $search_data['Baths'];
        			$search_arr['MinBaths'] = $search_data['Baths'];
				}

				if(!empty($search_data['Beds']))
				{
					$search_arr['MaxBeds'] = $search_data['Beds'];
        			$search_arr['MinBeds'] = $search_data['Beds'];

				} 

				if(!empty($search_data['Price From']))
				{
					$search_arr['MinPrice'] = $search_data['Price From'];
				}

				if(!empty($search_data['Price To']))
				{
					$search_arr['MaxPrice'] = $search_data['Price To'];
				}

				if(!empty($search_data['Property Type']))
				{
					$search_arr['PropertyType'] = $search_data['Property Type'];
				}

				if(!empty($search_data['Min Baths']))
				{
					$search_arr['MinBaths'] = $search_data['Min Baths'];
				}

				if(!empty($search_data['Max Baths']))
				{
					$search_arr['MaxBaths'] = $search_data['Max Baths'];
				}

				if(!empty($search_data['Min Year']))
				{
					$search_arr['MinYear'] = $search_data['Min Year'];
				}

				if(!empty($search_data['Max Year']))
				{
					$search_arr['Max Year'] = $search_data['Max Year'];
				}

				if(!empty($search_data['Min SqFt']))
				{
					$search_arr['MinSqFt'] = $search_data['Min SqFt'];
				}

				if(!empty($search_data['Max SqFt']))
				{
					$search_arr['MaxSqFt'] = $search_data['Max SqFt'];
				}

				if(!empty($search_data['Min Price']))
				{
					$search_arr['MinPrice'] = $search_data['Min Price'];
				}

				if(!empty($search_data['Max Price']))
				{
					$search_arr['MaxPrice'] = $search_data['Max Price'];
				}

				if(!empty($search_data['Order By']))
				{
					if (strpos($search_data['Order By'], '$') == false) {
					    $search_arr['OrderBy'] = $search_data['Order By'];
					}        
				}

				if(!empty($search_data['Standard Status']))
				{
					$search_arr['StandardStatus'] = $search_data['Standard Status'];
				}

				if(!empty($search_data['Garage Spaces']))
				{
					$search_arr['GarageSpaces'] = $search_data['Garage Spaces'];
				}

				if(!empty($search_data['Saved Search']))
				{
					$search_arr['SavedSearch'] = $search_data['Saved Search'];
				}				

				if(!empty($search_data['City']))
				{
					$search_arr['City'] = $search_data['City'];
				}				

			}	
			$MajorChangeType = array('Status Change','New Listing','Price Change');
			
			foreach ($MajorChangeType as  $type) {		

				if($result->status_change == 1 && $type == 'Status Change')
				{
					
					$search_arr['MajorChangeType'] =  'Status Change';
					$get_data = $FlexMLS_IDX->get_MajorChangeType_saved_search($search_arr,$type,$search_data['url']);
					//echo $get_data; die;
					if(!empty($get_data))
					{  //echo 1;
						$sen_name = "Alaska Dream Makers Info";
						$sen_email = "info@alaskaland.info";
						$rec_name = $user_info->data->display_name;
						$rec_email = $user_info->data->user_email;
						$email_type = "to";
						$email_sub = "Listing Status Changed at alaskadreammakers.info";
						$msg_type = "html";					
						$box_msg = "This property has been found to match your request for Status Change.<br />";
						$box_msg .= $get_data;
						$message = array();
						$to = array();
						$to[] = array(
						'email' => $rec_email,
						'name' => $rec_name,
						'type' => $email_type
						);
						$message['subject'] = $email_sub;
						$message[$msg_type] = $box_msg;
						$message['from_email'] = $sen_email;
						$message['from_name'] = $sen_name;
						$message['to'] = $to;
						
						$email_result = $mandrill->messages->send($message);
						//print_r($email_result); echo "<br>";
					}
					
			          
				}
				else if($result->new_listing == 1 && $type == 'New Listing')
				{
					
					$search_arr['MajorChangeType'] =  'New Listing';
					$get_data = $FlexMLS_IDX->get_MajorChangeType_saved_search($search_arr,$type,$search_data['url']);
					
					if(!empty($get_data))
					{ //echo 2;
						$sen_name = "Alaska Dream Makers Info";
						$sen_email = "info@alaskaland.info";
						$rec_name = $user_info->data->display_name;
						$rec_email = $user_info->data->user_email;
						$email_type = "to";
						$email_sub = "New Listing at alaskadreammakers.info";
						$msg_type = "html";					
						$box_msg = "This property has been found to match your request for New Listing.<br />";
						$box_msg .= $get_data;
						$message = array();
						$to = array();
						$to[] = array(
						'email' => $rec_email,
						'name' => $rec_name,
						'type' => $email_type
						);
						$message['subject'] = $email_sub;
						$message[$msg_type] = $box_msg;
						$message['from_email'] = $sen_email;
						$message['from_name'] = $sen_name;
						$message['to'] = $to;
						
						$email_result = $mandrill->messages->send($message);
						//print_r($email_result); echo "<br>";
					}
				}
				else if($result->price_change == 1 && $type == 'Price Change')
				{
					
					$search_arr['MajorChangeType'] =  'Price Change';
					$get_data = $FlexMLS_IDX->get_MajorChangeType_saved_search($search_arr,$type,$search_data['url']);
					//echo $get_data;
					if(!empty($get_data))
					{	//echo 3;
						$sen_name = "Alaska Dream Makers Info";
						$sen_email = "info@alaskaland.info";
						$rec_name = $user_info->data->display_name;
						$rec_email = $user_info->data->user_email;
						$email_type = "to";
						$email_sub = "Listing Price Changed at alaskadreammakers.info";
						$msg_type = "html";					
						$box_msg = "This property has been found to match your request for Price Change.<br />";
						$box_msg .= $get_data;
						$message = array();
						$to = array();
						$to[] = array(
						'email' => $rec_email,
						'name' => $rec_name,
						'type' => $email_type
						);
						$message['subject'] = $email_sub;
						$message[$msg_type] = $box_msg;
						$message['from_email'] = $sen_email;
						$message['from_name'] = $sen_name;
						$message['to'] = $to;
						
						$email_result = $mandrill->messages->send($message);
						//print_r($email_result); echo "<br>";
					}
				}
			}	
			
		}
		
	}
}


function saved_listing_Cron(){
	
    global $wpdb;
	include ("lib/Mandrill.php");

	
		$mandrill = new Mandrill('hMuUQAqpVG9JiVjZ8ObXgQ');
		$FlexMLS_IDX = new FlexMLS_IDX();
		$search_arr = array();
        $start_date = date('Y-m-d', strtotime("-1 days"));
		$stop_date = date('Y-m-d');	
		$search_arr['MinMajorChangeTimestamp'] =  $start_date.'T12:00:00';
		$search_arr['MaxMajorChangeTimestamp'] =  $stop_date.'T12:00:00';        

	        $sql = "SELECT * FROM `saved_listing`";
			$results = $wpdb->get_results($sql);			
				
					
				if(!empty($results))
				{ 
					foreach( $results as $result ) {	

						$listing_data =  json_decode($result->listing_data);			
				        $user_info = get_user_by('id',$result->user_id);


				        $MajorChangeType = array('Status Change','New Listing','Price Change');
						

						foreach ($MajorChangeType as  $type) {		

							if($type == 'Status Change')
							{								
								$search_arr['MajorChangeType'] =  'Status Change';
								
								$get_data = $FlexMLS_IDX->get_MajorChangeType_saved_listing($listing_data->ListingTag,$result->id,$result->user_id,$result->date,$search_arr['MajorChangeType'],$search_arr['MinMajorChangeTimestamp'],$search_arr['MaxMajorChangeTimestamp']);
								
								if(!empty($get_data))
								{  //echo 1;
									$sen_name = "Alaska Dream Makers Info";
									$sen_email = "info@alaskaland.info";
									$rec_name = $user_info->data->display_name;
									$rec_email = $user_info->data->user_email;
									$email_type = "to";
									$email_sub = "Saved Listing Status Changed at alaskadreammakers.info";
									$msg_type = "html";					
									$box_msg = "This property has been found to match your request for Status Change.<br />";
									$box_msg .= $get_data;
									$message = array();
									$to = array();
									$to[] = array(
									'email' => $rec_email,
									'name' => $rec_name,
									'type' => $email_type
									);
									$message['subject'] = $email_sub;
									$message[$msg_type] = $box_msg;
									$message['from_email'] = $sen_email;
									$message['from_name'] = $sen_name;
									$message['to'] = $to;
									
									$email_result = $mandrill->messages->send($message);
									//print_r($email_result); echo "<br>";
								}
								
						          
							}
							else if($type == 'New Listing')
							{
								// $search_arr['MinMajorChangeTimestamp'] =  $start_date.'T12:00:00';
			  					//$search_arr['MaxMajorChangeTimestamp'] =  $stop_date.'T12:00:00';
								// $search_arr['MajorChangeType'] =  'New Listing';
								// $get_data = $FlexMLS_IDX->get_MajorChangeType_saved_listing($listing_data->ListingTag,$result->id,$result->user_id,$result->date,$search_arr['MajorChangeType'],$search_arr['MinMajorChangeTimestamp'],$search_arr['MaxMajorChangeTimestamp']);
								
								// if(!empty($get_data))
								// { echo 2;
								// 	$sen_name = "Larry";
								// 	$sen_email = "info@alaskaland.info";
								// 	$rec_name = $user_info->data->display_name;
								// 	$rec_email = $user_info->data->user_email;
								// 	$email_type = "to";
								// 	$email_sub = "Listing Status Changed at alaskadreammakers.info";
								// 	$msg_type = "html";					
								// 	$box_msg = "This property has been found to match your request for New Listing.<br />";
								// 	$box_msg .= $get_data;
								// 	$message = array();
								// 	$to = array();
								// 	$to[] = array(
								// 	'email' => $rec_email,
								// 	'name' => $rec_name,
								// 	'type' => $email_type
								// 	);
								// 	$message['subject'] = $email_sub;
								// 	$message[$msg_type] = $box_msg;
								// 	$message['from_email'] = $sen_email;
								// 	$message['from_name'] = $sen_name;
								// 	$message['to'] = $to;
									
								// 	$email_result = $mandrill->messages->send($message);
								// 	print_r($email_result); echo "<br>";
								//}
							}
							else if($type == 'Price Change')
							{								
								$search_arr['MajorChangeType'] =  'Price Change';
								$get_data = $FlexMLS_IDX->get_MajorChangeType_saved_listing($listing_data->ListingTag,$result->id,$result->user_id,$result->date,$search_arr['MajorChangeType'],$search_arr['MinMajorChangeTimestamp'],$search_arr['MaxMajorChangeTimestamp']);
								//echo $get_data; 
								if(!empty($get_data))
								{	//echo "<br>"; echo 3;
									$sen_name = "Alaska Dream Makers Info";
									$sen_email = "info@alaskaland.info";
									$rec_name = $user_info->data->display_name;
									$rec_email = $user_info->data->user_email;
									$email_type = "to";
									$email_sub = "Saved Listing Price Changed at alaskadreammakers.info";
									$msg_type = "html";					
									$box_msg = "This property has been found to match your request for Price Change.<br />";
									$box_msg .= $get_data;
									$message = array();
									$to = array();
									$to[] = array(
									'email' => $rec_email,
									'name' => $rec_name,
									'type' => $email_type
									);
									$message['subject'] = $email_sub;
									$message[$msg_type] = $box_msg;
									$message['from_email'] = $sen_email;
									$message['from_name'] = $sen_name;
									$message['to'] = $to;
									
									$email_result = $mandrill->messages->send($message);
									//print_r($email_result); echo "<br>";
								}
							}
						}	
					}
				}
			       
		
}

saved_search_cron();
saved_listing_Cron();