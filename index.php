<?php
/*
Plugin Name: Fatora - Fatora Payment Getway
Description: Fatora - Payment Getway.
Version: 1.0
Author: Maktapp 
*/


add_action( 'plugins_loaded', 'Maktapp_init_WC_Fatora' , 0 );

 function Maktapp_init_WC_Fatora () {
     
    
     
     define('Fatora_folder', plugins_url( plugin_basename(dirname(__FILE__)) , _FILE_ ) );
     
 	if(!class_exists('WC_Payment_Gateway')) return;

	 class WC_fatora extends WC_Payment_Gateway {

	 	 /**
		 * Constructor for the gateway.
		 */
	 	

		 public function  __construct() {

		 	$this->id                   = 'fatora';
            $this->has_fields           = false;
            $this->order_button_text    = __( $button_text , 'Fatora');

            $this->method_title         =  'Fatora  Payment Gateway'  ;
            $this->method_description   = __( 'Online Payment Gateway.' , 'Fatora');
		    $this->token_auth           = $this->get_option('token_auth');
		    

           // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables.
             $this->callback = str_replace( 'https:', 'http:', home_url( '/wc-api/WC_fatora' )  );
             
            $this->enabled               = $this->get_option('enabled');
            $this->title                 = __( 'Fatora Payment Gateway .' , 'Fatora');
            $this->description           = __('Pay via Credit Card' , 'Fatora');
    
            $this->email_client_support  =  $this->get_option('email_client_support');
            $this->success_msg =  $this->get_option('success_msg');
            $this->fail_msg  =  $this->get_option('fail_msg');
            $this -> msg['message'] = "";
            $this -> msg['class']   = "";
            
         
         add_action('init', array($this, 'Maktapp_check_fatora_response'));
         
         
         add_action( 'woocommerce_api_wc_fatora' , array( &$this, 'Maktapp_check_fatora_response' ) );
         
         add_action( 'woocommerce_api_fatora' , array( &$this, 'Maktapp_check_fatora_response' ) );
         
      
			add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( &$this, 'Maktapp_check_fatora_response' ) );
			
			
			 add_filter( 'woocommerce_thankyou_order_received_text', array( &$this,'Maktapp_thank_you_title_with_payment_fatora' ) , 20, 2 );
         
         
         if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				/* 2.0.0 */
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
			} else {
				/* 1.6.6 */
				add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			}
			
            
            
            add_action('woocommerce_thankyou_fatora', array(&$this, 'Maktapp_check_fatora_response'));
		
			
			
			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				/* 2.0.0 */
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
			} else {
				/* 1.6.6 */
				add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			}
			
            
            add_action('woocommerce_receipt_fatora', array(&$this, 'Maktapp_receipt_page'));
            
            add_action('woocommerce_receipt_WC_fatora', array(&$this, 'Maktapp_receipt_page'));
            

		 }

		  function init_form_fields()
          {
            $this->form_fields = array(
                'enabled'           => array(
                    'title'   => __('Enable/Disable', 'Fatora'),
                    'type'    => 'checkbox',
                    'label'   => __('Enable Fatora Online Payment.', 'Fatora'),
                    'default' => 'no',
                ),
                'token_auth' => array(
                    'title' => __('Token:', 'Fatora'),
                    'type'  => 'text',
                ),
				
				
				'email_client_support' => array(
                    'title' => __('Email client support :', 'Fatora'),
                    'type'  => 'text',
                ),

                'success_msg' => array(
                    'title' => __('success message :', 'Fatora'),
                    'type'  => 'textarea',
                     'css'     => 'min-height: 350px !important;',
                ),

                'fail_msg' => array(
                    'title' => __('success message :', 'Fatora'),
                    'type'  => 'textarea',
                     'css'     => 'min-height: 350px !important;',
                ),

            );
          }

/**
        * Receipt Page
        **/
        function Maktapp_receipt_page($order){
            
            echo '<p>'.__('Thank you for your order, please click the button below to pay with Fatora.', 'Fatora').'</p>';
           echo $this->Maktapp_generate_fatora_url($order);
        }
        /**
        * Generate tap button link
        **/
        function Maktapp_generate_fatora_url($order_id){
            global $woocommerce;
            $orderID = $order_id;
            $order = new WC_Order( $order_id );
            $txnid = $order_id.'_'.date("ymds");
            $total = $order->order_total;
            $customerEmail = $order->get_billing_email();
            $customerCountry = $order->get_billing_country();
            $currency = $order->get_currency();
            $customerName = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ; 
            $customerPhone = $order->get_billing_phone() ;
            
            if ( $this->redirect_page_id == "" || $this->redirect_page_id == 0 )
            {
                $redirect_url = $order->get_checkout_order_received_url();
            } else {
                $redirect_url = get_permalink($this->redirect_page_id);
            }

            //For wooCoomerce 2.0
            if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                $redirect_url = add_query_arg( 'wc-api', get_class( $this ), $redirect_url );
            }

       
          
           $note  =    '' ; 
           $lang = 'ar' ; 
           
           
             
            
            
            $validationCode = $orderID.'-'.uniqid() ;
            update_post_meta( $orderID , 'validation-code-text' , $validationCode );
             
             
             $parameter = array(
                        'body'          => array(
                            'cmd' => '_notify-synch',
                            'token'=> $this->token_auth ,
                            'amount'  => $total  , 
                            'currencyCode' => $currency ,
                            'orderId' => $order_id , 
                            'note' => $note ,  
                            'isRecurring' => false ,
                            'customerName' => $customerName , 
                            'customerPhone' => $customerPhone,
                            'lang' => $lang ,
                            'customerEmail' => $customerEmail   ,
                            'customerCountry' => $customerCountry ,
                        ),
                        'timeout'       => 60,
                        'httpversion'   => '1.1', 
                        'user-agent'    => '1.0' ,
                    );
             
            
                $json_url = 'https://maktapp.credit/v3/AddTransaction' ; 
                    
                $response = wp_safe_remote_post( $json_url , $parameter );
                
                $pay_post_result =  array_map( 'urldecode', explode( "\n", $response['body'] ) ) ;
                $pay_post_results = array();
              
            
                $data_json_decode = json_decode($response['body']);
            
           
           
            
            if ($data_json_decode->{'result'} != -1) {
                $url = $data_json_decode->{'result'} ; 
            }
            
             return '
					<script type="text/javascript">
				
					window.location.href="'.$url.'"
					</script>';
        }

    // This function executed to display payment gateway details in checkout page
    
        function payment_fields()
            {
                if ($this->description) {
                    echo "<img src='" . Fatora_folder ."/assets/img/payments-1.png' width='120px'/>" . wpautop(wptexturize($this->description));
                }
            }

        /**
        * Process the payment and return the result
        **/
        function process_payment($order_id){
            global $woocommerce;
            $order = new WC_Order( $order_id );
                
           $order_key = $order->order_key;
            
            if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>=' ) ) {
                /* 2.1.0 */
                $checkout_payment_url = $order->get_checkout_payment_url( true );
            } else {
                /* 2.0.0 */
                $checkout_payment_url = get_permalink( get_option ( 'woocommerce_pay_page_id' ) );
            }
            
 
        
        $return_url = $woocommerce->cart->get_checkout_url()."/order-received/";
        
          $par = array(
                        'body'          => array(
                            'Token' => $this->token_auth ,
                            'SuccessURL'=> $return_url.$order_id.'/?key='.$order_key .'&' ,
                            'FailureURL'  => $return_url.$order_id.'/?key='.$order_key .'&'  , 
                        
                        ),
                      
                    );
        
    
                    
                $response = wp_safe_remote_post( 'https://maktapp.credit/v3/SetGatewayURL' , $par );
                
                $pay_post_result =  array_map( 'urldecode', explode( "\n", $response['body'] ) ) ;
              
                
        
        
        

            return array(
                'result' => 'success', 
                'redirect' => add_query_arg(
                    'order', 
                    $order->id, 
                    add_query_arg(
                        'key', 
                        $order->order_key, 
                        $checkout_payment_url                       
                    )
                )
            );
        }

		 	

       /**
		* Check for valid Fatora server callback
		**/

  public function Maktapp_check_fatora_response(){
     
    global $woocommerce;
    
    $msg['class']   = 'error';
    $msg['message'] = $this->get_option( 'fail_msg' );
  
   

    if (  isset($_REQUEST['orderId']) ){
         $OrderID = sanitize_text_field($_REQUEST['orderId']) ; 
        $order  = new WC_Order($OrderID);
       

    if ( isset($_REQUEST['transid']) || isset($_REQUEST['paymentId']) ) {

        $transid = sanitize_text_field($_REQUEST['transid']) ; 
        $paymentId = sanitize_text_field($_REQUEST['paymentId']) ; 
        $url = 'https://maktappcredit.azurewebsites.net/v3/ValidateTransaction';
        $data = array('transactionID' => $transid);

        // use key 'http' even if you send the request to https://...
        $pdt = array(
                    'body'          => array(
                        'cmd' => '_notify-synch',
                        'transactionID'  => $transid,
                        'orderId' =>  $OrderID , 
                        'token' =>  $this->token_auth , 
                        
                    ),
                    'timeout'       => 60,
                    'httpversion'   => '1.1',
                    'user-agent'    => '1.0' ,
        );
        
       $response = wp_safe_remote_post( 'https://maktapp.credit/v3/ValidatePayment?transactionID='.$transid , $pdt );

		
      $obj_response = json_decode($response['body']);
      

      $payment = $obj_response->{'payment'} ;
        $stringsmall  = mb_convert_case($payment->{'paymentstate'}, MB_CASE_LOWER, "UTF-8");
       
         $json_order_id = $OrderID ;
         
        
         
        if (  $stringsmall == "successful" || $stringsmall == "success" || $stringsmall == "SUCCESS"  ) {
            
          
            
   
            $order = new WC_Order($json_order_id);

              update_post_meta($json_order_id, 'transid', $transid);
              update_post_meta($json_order_id, 'paymentId', $paymentId); 
              $order->add_order_note($ReasonCodeDesc);
              $order->payment_complete($transid);
              $woocommerce -> cart -> empty_cart();
              $msg['message'] = $this->get_option('success_msg');
              $msg['class'] = 'success';
              
        }
        else {

              update_post_meta($json_order_id, 'transid', $transid);
              update_post_meta($json_order_id, 'paymentId', $paymentId); 
              $msg['class'] = 'error';
              $msg['message'] = $this->get_option('fail_msg');
        }


    }
   
   if (isset($_REQUEST['Failerdescription'])) {
       
       $Failerdescription = sanitize_text_field($_REQUEST['Failerdescription']) ; 

                $order -> update_status('failed');
                $order -> add_order_note($Failerdescription);
                update_post_meta($order->id, 'transid', $transid);
                update_post_meta($order->id, 'paymentId', $paymentId); 
                update_post_meta($order->id, 'respons_fail', __('proccess payment fail' , 'MaktappCreditCard'));
                update_post_meta($order->id, 'Failerdescription', __($Failerdescription , 'MaktappCreditCard'));
                $msg['class'] = 'error';
                $msg['message'] = $this->get_option('fail_msg');

   }


                if ( function_exists( 'wc_add_notice' ) )
                    {
                        wc_add_notice( $msg['message'], $msg['class'] );
                
                    }
                else 
                {
                    if($msg['class']=='success'){
                        $woocommerce->add_message( $msg['message']);
                    }else{
                        $woocommerce->add_error( $msg['message'] );
            
                    }
                    $woocommerce->set_messages();
                }


            
            }
            
     
            

 }
 
       /**
		* Fatora title in thank you page
		**/

 
public function Maktapp_thank_you_title_with_payment_fatora ( $thank_you_title, $order ){
    
    
    $msg = $this->get_option('success_msg'); 
    
 
    
    if(!isset($_REQUEST['Failerdescription'])){
        	return _e('Oh ' , 'Fatora' ) . $order->get_billing_first_name() . ', ' .wp_kses_post($msg  );
        	
    }
    else {
        	return _e('Oh sorry' , 'Fatora' ) . $order->get_billing_first_name() . ', ' . $this->get_option('fail_msg') . '<br>' . 
        	 _e('you can contact us with this email' , 'Fatora') .esc_html( $this->get_option('email_client_support')) ;
    }
 

 
}

 
	 }


 

/**
 * Add the Gateway to WooCommerce
 **/
 
     
     function Maktapp_add_WC_Gateway_Fatora( $methods ) {
        $methods[] = 'WC_fatora'; 
        return $methods;
    }
     
     
     

add_filter( 'woocommerce_payment_gateways', 'Maktapp_add_WC_Gateway_Fatora' );



// add  Settings linke 
function Maktapp_settings_link( $links ) {
    
    $links[] = '<a href="' .
    admin_url( 'admin.php?page=wc-settings&tab=checkout&section=fatora' ) .
        '">' . __('Settings') . '</a>';
    return $links;
}

add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'Maktapp_settings_link');


}


