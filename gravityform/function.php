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
	
    //calculate dates. Start today adding more days using arg.
    $startdt = date('Y-m-d');
    $endDt = date('Y-m-d', strtotime(date('Y-m-d H:i:s')) + (7 * 60 * 60 * 24));
	
	// call hubspot// call hubspot
	$response = wp_remote_get('https://api.hubapi.com/crm/v3/objects/contacts?limit=' . '' . $arg['limit_query'] . '' . '&archived=false&properties=' . '' . $arg['filter_properties'], 
							  array('headers' => array('authorization' => $arg['token'])));
    
	if ( is_wp_error( $response ) ) {
	   return false;
	}
	
	$body = wp_remote_retrieve_body( $response );
   	$data = json_decode( $body );
	$customers_to_update = array();
	
	if(! empty($data)){
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
				
				array_push($customers_to_update, array('id'=>$id, 'properties'=>array($arg['coupon']=>$couponnew)));
			}
		} 
		
		//batch has a limit of 10, chuck array
		$splitarraycustomer = array_chunk($customers_to_update, 9);
		$str = print_r ($splitarraycustomer, true);
    	echo $str;
		
		
		foreach( $splitarraycustomer as $update ) {
		
			$url = 'https://api.hubapi.com/crm/v3/objects/contacts/batch/update';
			$args = array(
										'timeout'     => 45,
										'redirection' => 5, 
										'headers'     => array('authorization' => $arg['token'],
														   'content-type'=> 'application/json'),
										'body'        => json_encode(array('inputs'=>$update))
									);

			//insert code in HB
			$response = wp_remote_post($url, $args);
		}
		
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
//replace ID for the id of the form that is connected with HS
add_action('gform_pre_submission_14', 'create_coupon_gf_blog', 10, 2);

function create_coupon_gf_blog($entry, $form){
	
    //define variables
	$url = 'https://api.hubapi.com/crm/v3/objects/contacts/search';
    $url_update = 'https://api.hubapi.com/crm/v3/objects/contacts/batch/update';
    $customers_to_search = array();
	$filters_properties = array();
    $customer_to_update = array();
	$myhstoken = 'Bearer pat-eu1-ddaee549-24a1-4d0c-9410-9211e41252ed';

    //calculate dates. Start today adding more days using arg.
    $startdt = date('Y-m-d');
    $endDt = date('Y-m-d', strtotime(date('Y-m-d H:i:s')) + (7 * 60 * 60 * 24));
	
	$couponnew = substr(md5(uniqid(mt_rand(), true)) , 0, 8);
	
				$meta = array(
					'gravityForm' => 24,
					'couponName' => 'test203',
					'couponCode' => $couponnew,
					'couponAmountType' => 'percentage',
					'couponAmount'=> 1,
					'startDate'   => $startdt,
					'endDate'     => $endDt,
					'usageLimit'  => '1',
					'isStackable' => false,
					'usageCount'  => 0,
				);
		
			$form_id = $meta['form_id'] ? $meta['form_id'] : 0;
			gf_coupons()->insert_feed($form_id, true, $meta);

	
<<<<<<< HEAD
	$_POST['input_10'] = $couponnew;
}
=======
	//insert code in HB
	$res = wp_remote_post($url_update, $args);
		
	// error check
	if ( is_wp_error( $res ) ) {
		$error_message = $res->get_error_message();
		echo "Something went wrong: $error_message";
	}
	
}
?>
>>>>>>> 465a6175c3231f9f043c0ca369b0ff6e90ba9f26
