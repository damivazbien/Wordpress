<!-- here you will find two function that read HubSpot API and create coupons to link customers -->

<?php

//read hubspot, find customer in lifecycle == oportunity, create coupon and update
add_action('create_coupon_from_hs', 'create_coupon_gf', 10, 2);

//generate coupon code
function generateRandomString($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrs092u3tuvwxyzaskdhfhf9882323ABCDEFGHIJKLMNksadf9044OPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// create coupon for customer in x life cycle.
function create_coupon_gf($arg=array()){
	
	// call hubspot
	$response = wp_remote_get('https://api.hubapi.com/crm/v3/objects/contacts?limit=' . '' . $arg['limit_query'] . '' . '&archived=false&properties=' . '' . $arg['filter_properties'], 
							  array('headers' => array('authorization' => $arg['token'])));
    
	if ( is_wp_error( $response ) ) {
	   return false;
	}
	
	$body = wp_remote_retrieve_body( $response );
   	$data = json_decode( $body );
	$customers_to_update = array();
	
	if(! empty($data)){
		
		//calculate dates. Start today adding more days using arg.
		$startdt = date('d/m/y');
		$endDt = date('d/m/y', strtotime(date('Y-m-d H:i:s')) + ($arg['enddate'] * 60 * 60 * 24));
		
		
		foreach( $data->results as $contact ) {
			$id = $contact->id;
			if($contact->properties->lifecyclestage == $arg['lifecycle_stage']){
				$couponnew = substr(md5(uniqid(mt_rand(), true)) , 0, 8);
				$meta = array(
					'gravityForm' => $arg['gfnumber'],
					'couponName' => $arg['couponname'],
					'couponCode' => $couponnew,
					'couponAmountType' => $arg['couponAmountType'],
					'couponAmount'=> $arg['couponamount'],
					'startDate'   => $startdt,
					'endDate'     => $endDt,
					'usageLimit'  => $arg['usagelimit'],
					'isStackable' => $arg['isstackable'],
					'usageCount'  => $arg['usagecount'],
					);
				
				$form_id = $meta['form_id'] ? $meta['form_id'] : 0;
				gf_coupons()->insert_feed($form_id, true, $meta);
				
				array_push($customers_to_update, array('id'=>$id, 'properties'=>array($arg['cupon']=>$couponnew)));
			}
		} 
		
		$url = 'https://api.hubapi.com/crm/v3/objects/contacts/batch/update';
		$args = array(
								    'timeout'     => 45,
									'redirection' => 5, 
								    'headers'     => array('authorization' => $arg['token'],
													   'content-type'=> 'application/json'),
								    'body'        => json_encode(array('inputs'=>$customers_to_update))
								);
		
		//insert code in HB
		$response = wp_remote_post($url, $args);
		
		// error check
		if ( is_wp_error( $response ) ) {
		   $error_message = $response->get_error_message();
		   echo "Something went wrong: $error_message";
		}
		else {
		   echo 'Response: <pre>';
		   print_r( $response );
		   echo '</pre>';
		}
	}	
}

//after send a form, create coupon
add_action('gform_after_submission', 'create_coupon_gf_blog', 10, 2);

function create_coupon_gf_blog($entry, $form){
	//define variables
	$url = 'https://api.hubapi.com/crm/v3/objects/contacts/search';
    $url_update = 'https://api.hubapi.com/crm/v3/objects/contacts/batch/update';
    $customers_to_search = array();
	$filters_properties = array();
    $customer_to_update = array();
	$myhstoken = 'Bearer pat-eu1-ddaee549-24a1-4d0c-9410-9211e41252ed';
    //calculate dates. Start today adding more days using arg.
    $startdt = date('d/m/y');
    $endDt = date('d/m/y', strtotime(date('Y-m-d H:i:s')) + (7 * 60 * 60 * 24));

    array_push($filters_properties, array('propertyName'=>'email', 'operator'=>'EQ', 'value'=>rgar($entry,'1')));
	array_push($customers_to_search, array('filters'=>$filters_properties));
	
	
	
	$args = array('timeout'=> 45,'redirection' => 5, 
								    'headers'=> array('authorization' => $myhstoken,
													   'content-type'=> 'application/json'),
								    'body'=> json_encode(array('filterGroups'=>$customers_to_search))
								);
		
	//insert code in HB
	$response = wp_remote_post($url, $args);
    
	
	$body = wp_remote_retrieve_body( $response );
   	$data = json_decode( $body );
	
	if($data->total > 0){
		
		foreach( $data->results as $contact ) {
			
			$id = $contact->id;
			echo $id;
			
			$couponnew = substr(md5(uniqid(mt_rand(), true)) , 0, 8);
		
			$meta = array(
					'gravityForm' => 24,
					'couponName' => 'test101',
					'couponCode' => $couponnew,
					'couponAmountType' => 'percentage',
					'couponAmount'=> 1,
					'startDate'   => $startdt,
					'endDate'     => $endDt,
					'usageLimit'  => '1',
					'isStackable' => false,
					'usageCount'  => 1,
				);
		
			$form_id = $meta['form_id'] ? $meta['form_id'] : 0;
			gf_coupons()->insert_feed($form_id, true, $meta);
		
			array_push($customer_to_update, array('id'=>$id, 'properties'=>array('coupon'=>$couponnew)));

			
		}
	}
	
	$args = array('timeout'=> 45,'redirection' => 5, 
								    'headers'     => array('authorization' => 'Bearer pat-eu1-ddaee549-24a1-4d0c-9410-9211e41252ed',
													   'content-type'=> 'application/json'),
								    'body'        => json_encode(array('inputs'=>$customer_to_update))
								);
	
	//insert code in HB
	$res = wp_remote_post($url_update, $args);
		
	// error check
	if ( is_wp_error( $res ) ) {
		$error_message = $res->get_error_message();
		echo "Something went wrong: $error_message";
	}
	
}
?>