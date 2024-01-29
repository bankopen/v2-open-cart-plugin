<?php
class ControllerExtensionPaymentLayerpayment extends Controller {
	
	const BASE_URL_SANDBOX = "https://sandbox-icp-api.bankopen.co/api";
    const BASE_URL_UAT = "https://icp-api.bankopen.co/api";
	
	private $payment_mode='';
	private $apikey='';
	private $secretkey='';
	
	
	public function index()
	{
		$this->load->model('checkout/order');	
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		//$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('config_order_status_id'), "Default order status before payment.", false);
		
		$data=$this->process_layer();
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . 'extension/payment/layerpayment')) {
			return $this->load->view($this->config->get('config_template') . 'extension/payment/layerpayment', $data);	
		} else {
			return $this->load->view('extension/payment/layerpayment', $data);
		}		
		
	}
	
	public function init()
	{
		$this->payment_mode = $this->config->get('payment_layerpayment_mode');
		$this->apikey = $this->config->get('payment_layerpayment_apikey');
		$this->secretkey = $this->config->get('payment_layerpayment_secretkey');
	}

	private function process_layer() {	
    	
		$this->load->model('checkout/order');
		$this->language->load('extension/payment/layerpayment');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		/////////////////////////////////////Start Layer Vital  Information /////////////////////////////////
		$this->init();
		if($this->payment_mode=='live') {
			$remote_script = 'https://payments.open.money/layer';
			//$remote_script = '<script id="layer" src="https://payments.open.money/layer"></script>';
		}
		else
		{
			$remote_script = 'https://sandbox-payments.open.money/layer';			
		    //$remote_script = '<script id="layer" src="https://sandbox-payments.open.money/layer"></script>';			
		}
		
		$txnid = $this->session->data['order_id'];

		$surl = $this->url->link('extension/payment/layerpayment/callback');
		
		$layer_payment_token_data = $this->create_payment_token([
                'amount' => (int)$order_info['total'],
                'currency' => $order_info['currency_code'],
                'name'  => $order_info['payment_firstname'].' '.$order_info['payment_lastname'],
                'email_id' => $order_info['email'],
                'contact_number' => $order_info['telephone']                
            ]);
		
		$error="";
		$payment_token_data = "";
		
		if(empty($error) && isset($layer_payment_token_data['error'])){
			$error = 'E55 Payment error. ' . $layer_payment_token_data['error'];          
		}

		if(empty($error) && (!isset($layer_payment_token_data["id"]) || empty($layer_payment_token_data["id"]))){				
			$error = 'Payment error. ' . 'Layer token ID cannot be empty';        
		}   
    
		if(empty($error))
			$payment_token_data = $this->get_payment_token($layer_payment_token_data["id"]);
    
		if(empty($error) && empty($payment_token_data))
			$error = 'Layer token data is empty...';
		
		if(empty($error) && isset($payment_token_data['error'])){
            $error = 'E56 Payment error. ' . $payment_token_data['error'];            
        }

        if(empty($error) && $payment_token_data['status'] == "paid"){
            $error = "Layer: this order has already been paid";            
        }

        if(empty($error) && $payment_token_data['amount'] != (int)$order_info['total']){
            $error = "Layer: an amount mismatch occurred";
        }
		
    
		if(empty($error) && !empty($payment_token_data)){		
        
			$hash = $this->create_hash(array(
				'layer_pay_token_id'    => $payment_token_data['id'],
				'layer_order_amount'    => $payment_token_data['amount'],
				'woo_order_id'    => $txnid,
				));
				
			$html =  "<form action='".$surl."' method='post' style='display: none' name='layer_payment_int_form'>
            <input type='hidden' name='layer_pay_token_id' value='".$payment_token_data['id']."'>
            <input type='hidden' name='woo_order_id' value='".$txnid."'>
            <input type='hidden' name='layer_order_amount' value='".$payment_token_data['amount']."'>
            <input type='hidden' id='layer_payment_id' name='layer_payment_id' value=''>
            <input type='hidden' id='fallback_url' name='fallback_url' value=''>
            <input type='hidden' name='hash' value='".$hash."'>
            </form>";
			$html .= "<div class='buttons'>
						<div class='pull-right'><input type='submit' 
							value='".$this->language->get('button_confirm')."' class='btn btn-primary' onclick='triggerLayer(); return false;' /></div>
					</div>";
						
			$html .= "<script>";		
			$html .= "var script = document.createElement('script');
					  script.setAttribute('src', '".$remote_script."');
					  document.body.appendChild(script);";	
			
			$html .= "function triggerLayer() {							 							
							Layer.checkout(
							{
								token: '".$payment_token_data['id']."',
								accesskey: '".$this->apikey."'
							},
							function (response) {
								console.log(response)
								if(response !== null || response.length > 0 ){
									if(response.payment_id !== undefined && response.status !== 'cancelled'){
										document.getElementById('layer_payment_id').value = response.payment_id;
										document.layer_payment_int_form.submit();
									}else if(response.payment_id !== undefined && response.status == 'cancelled'){
										Layer.cancel;
									}
								}
								
							},
							function (err) {
								//alert(err.message);
							});	
						}
				</script>";
			return [
				'error' => '',
				'data'=> $html
            ];
		}
		else
			return [
                'error' => $error,
				'data'=> ''
            ];
	}
	
	public function callback() {
		$this->init();
		if (isset($this->request->post['layer_payment_id']) || !empty($this->request->post['layer_payment_id'])) {
			$this->language->load('extension/payment/layerpayment');
			$this->load->model('checkout/order');
			
			$pdata = array(
                    'layer_pay_token_id'    => $this->request->post['layer_pay_token_id'],
                    'layer_order_amount'    => $this->request->post['layer_order_amount'],
                    'woo_order_id'     		=> $this->request->post['woo_order_id'],
                );
			
			$layer_payment_id = $this->request->post['layer_payment_id'];	
			$orderid = $pdata['woo_order_id'];
			$order_info = $this->model_checkout_order->getOrder($orderid);
			
			$message = '';
			foreach($this->request->post as $k => $val){
				$message .= $k.': ' . $val . "\n";
			}
			
			try {

                

                if($this->verify_hash($pdata,$this->request->post['hash'])){					

                    if(!empty($order_info)){
						$payment_data = $this->get_payment_details($this->request->post['layer_payment_id']);

                        if(isset($payment_data['error'])){
							$message .=' '.$payment_data['error'];
							$this->session->data['error'] = $message;		
							$this->response->redirect($this->url->link('checkout/checkout', '', true));							
                        }

                        if(isset($payment_data['id']) && !empty($payment_data)){
                            if($payment_data['payment_token']['id'] != $pdata['layer_pay_token_id']){

                                $message .=" Layer: received layer_pay_token_id and collected layer_pay_token_id doesnt match";
								$this->session->data['error'] = $message;		
								$this->response->redirect($this->url->link('checkout/checkout', '', true));
                            }


                            if($pdata['layer_order_amount'] != $payment_data['amount'] || $order_info['total'] !=$payment_data['amount'] ){
								
								$message .=" Layer: received amount and collected amount doesnt match";
								$this->session->data['error'] = $message;		
								$this->response->redirect($this->url->link('checkout/checkout', '', true));
                            }

                            switch ($payment_data['status']){
                                case 'authorized':
								case 'captured': 
									$this->session->data['success'] = "Payment is successful...";
									$this->model_checkout_order->addOrderHistory($orderid, $this->config->get('payment_layerpayment_order_status_id'),'Payment Successful,  '.$layer_payment_id.'',true);
									$this->response->redirect($this->url->link('checkout/success', '', true));				
                                    break;
                                case 'failed':								    
                                case 'cancelled':                                    									
									$this->model_checkout_order->addOrderHistory($orderid, $this->config->get('payment_layerpayment_order_fail_status_id'),$message,true);					
									$this->session->data['error'] = "Payment is cancelled/failed...";
									$this->response->redirect($this->url->link('checkout/checkout', '', true));
									break;
                                default:                                    
                                    exit;
                                break;
                            }
                        } else {
                            $message .=" invalid payment data received E98";
							$this->session->data['error'] = $message;		
							$this->response->redirect($this->url->link('checkout/checkout', '', true));                               
                        }
                    } else {
                        throw new Exception("unable to create order object");
                    }
                } else {
                    throw new Exception("hash validation failed");
                }

            } catch (Throwable $exception){
               
				$message .= "Layer: an error occurred " . $exception->getMessage();
				$this->session->data['error'] = $message;		
				$this->response->redirect($this->url->link('checkout/checkout', '', true));
            }							
			
		}
	}

	public function create_hash($data){
		ksort($data);
		$hash_string = $this->apikey;
		foreach ($data as $key=>$value){
			$hash_string .= '|'.$value;
		}
		return hash_hmac("sha256",$hash_string,$this->secretkey);
	}
	
	public function verify_hash($data,$rec_hash){
		$gen_hash = $this->create_hash($data);
		if($gen_hash === $rec_hash){
			return true;
		}
		return false;
	}
	
	protected function create_payment_token($data){

        try {
            $pay_token_request_data = array(
                'amount'   			=> (isset($data['amount']))? $data['amount'] : NULL,
                'currency' 			=> (isset($data['currency']))? $data['currency'] : NULL,
                'name'     			=> (isset($data['name']))? $data['name'] : NULL,
                'email_id' 			=> (isset($data['email_id']))? $data['email_id'] : NULL,
                'contact_number' 	=> (isset($data['contact_number']))? $data['contact_number'] : NULL,
                'mtx'    			=> (isset($data['mtx']))? $data['mtx'] : NULL,
                'udf'    			=> (isset($data['udf']))? $data['udf'] : NULL,
            );

            $pay_token_data = $this->http_post($pay_token_request_data,"payment_token");

            return $pay_token_data;
        } catch (Exception $e){			
            return [
                'error' => $e->getMessage()
            ];

        } catch (Throwable $e){
			
			return [
                'error' => $e->getMessage()
            ];
        }
    }

    protected function get_payment_token($payment_token_id){

        if(empty($payment_token_id)){

            throw new Exception("payment_token_id cannot be empty");
        }

        try {

            return $this->http_get("payment_token/".$payment_token_id);

        } catch (Exception $e){

            return [
                'error' => $e->getMessage()
            ];

        } catch (Throwable $e){

            return [
                'error' => $e->getMessage()
            ];
        }

    }

    public function get_payment_details($payment_id){

        if(empty($payment_id)){

            throw new Exception("payment_id cannot be empty");
        }

        try {

            return $this->http_get("payment/".$payment_id);

        } catch (Exception $e){
			
            return [
                'error' => $e->getMessage()
            ];

        } catch (Throwable $e){

            return [
                'error' => $e->getMessage()
            ];
        }

    }


    protected function build_auth($body,$method){

        $time_stamp = trim(time());
        unset($body['udf']);

        if(empty($body)){

            $token_string = $time_stamp.strtoupper($method);

        } else {            
            $token_string = $time_stamp.strtoupper($method).json_encode($body);
        }

        $token = trim(hash_hmac("sha256",$token_string,$this->secretkey));

        return array(                       
            'Content-Type: application/json',                                 
            'Authorization: Bearer '.$this->apikey.':'.$this->secretkey,
            'X-O-Timestamp: '.$time_stamp
        );

    }


    protected function http_post($data,$route){

        foreach (@$data as $key=>$value){

            if(empty($data[$key])){

                unset($data[$key]);
            }
        }

        if($this->payment_mode != 'live'){
            $url = self::BASE_URL_SANDBOX."/".$route;
        } else {
            $url = self::BASE_URL_UAT."/".$route;
        }
		
        $header = $this->build_auth($data,"post");
		
        try
        {
            $curl = curl_init();
		    curl_setopt($curl, CURLOPT_URL, $url);
		    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		    curl_setopt($curl, CURLOPT_SSLVERSION, 6);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_MAXREDIRS,10);
		    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		    curl_setopt($curl, CURLOPT_ENCODING, '');		
		    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, JSON_HEX_APOS|JSON_HEX_QUOT ));
            
		    $response = curl_exec($curl);
            $curlerr = curl_error($curl);
            
            if($curlerr != '')
            {
                return [
                    "error" => "Http Post failed",
                    "error_data" => $curlerr,
                ];
            }
            return json_decode($response,true);
        }
        catch(Exception $e)
        {
            return [
                "error" => "Http Post failed",
                "error_data" => $e->getMessage(),
            ];
        }           
        
    }

    protected function http_get($route){

        if($this->payment_mode != 'live'){
			$url = self::BASE_URL_SANDBOX."/".$route;
        } else {			
            $url = self::BASE_URL_UAT."/".$route;
		}

        $header = $this->build_auth($data = [],"get");

        try
        {           
            $curl = curl_init();
		    curl_setopt($curl, CURLOPT_URL, $url);
		    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		    curl_setopt($curl, CURLOPT_SSLVERSION, 6);
		    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		    curl_setopt($curl, CURLOPT_ENCODING, '');		
		    curl_setopt($curl, CURLOPT_TIMEOUT, 60);		   
            $response = curl_exec($curl);
            $curlerr = curl_error($curl);
            if($curlerr != '')
            {
                return [
                    "error" => "Http Get failed",
                    "error_data" => $curlerr,
                ];
            }
            return json_decode($response,true);
        }
        catch(Exception $e)
        {
            return [
                "error" => "Http Get failed",
                "error_data" => $e->getMessage(),
            ];
        }
    }
}
?>
