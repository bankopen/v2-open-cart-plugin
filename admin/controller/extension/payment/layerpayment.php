<?php 
class ControllerExtensionPaymentLayerpayment extends Controller {
	private $error = array(); 

	public function index() {
		$this->load->language('extension/payment/layerpayment');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
			
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			$this->model_setting_setting->editSetting('payment_layerpayment', $this->request->post);				
			
			$this->session->data['success'] = $this->language->get('text_success');
			
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['entry_mode'] = $this->language->get('entry_mode');		
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_order_status'] = $this->language->get('entry_order_status');	
		$data['entry_order_fail_status'] = $this->language->get('entry_order_fail_status');	
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
				
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');		
		$data['text_edit'] = $this->language->get('text_edit');
		
		$data['entry_apikey'] = $this->language->get('entry_apikey');
		$data['entry_secretkey'] = $this->language->get('entry_secretkey');
		$data['entry_total'] = $this->language->get('entry_total');	
		
		$data['help_apikey'] = $this->language->get('help_apikey');
		$data['help_total'] = $this->language->get('help_total');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
        $data['help_secretkey'] = $this->language->get('help_secretkey');
		$data['tab_general'] = $this->language->get('tab_general');
		
		if(!isset($this->error['error_apikey'])) $this->error['error_apikey'] ='';
		if(!isset($this->error['error_secretkey'])) $this->error['error_secretkey'] = '';		
		if(!isset($this->error['error_status'])) $this->error['error_status'] = '';
		if(!isset($this->error['error_mode'])) $this->error['error_mode'] = '';

 		if ($this->error) {
			$data = array_merge($data,$this->error);
		} 
		
  		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
      		'separator' => false
   		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_payment'),
			'href'      => $this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true),
      		'separator' => ' :: '
   		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/payment/layerpayment', 'user_token=' . $this->session->data['user_token'], true),
      		'separator' => ' :: '
   		);
				
		$data['action'] = $this->url->link('extension/payment/layerpayment', 'user_token=' . $this->session->data['user_token'], 'SSL');
		
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL');
		
		if (isset($this->request->post['payment_layerpayment_mode'])) {
			$data['payment_layerpayment_mode'] = $this->request->post['payment_layerpayment_mode'];
		} else {
			$data['payment_layerpayment_mode'] = $this->config->get('payment_layerpayment_mode');
		}
		
		
		if (isset($this->request->post['payment_layerpayment_apikey'])) {
			$data['payment_layerpayment_apikey'] = $this->request->post['payment_layerpayment_apikey'];
		} else {
			$data['payment_layerpayment_apikey'] = $this->config->get('payment_layerpayment_apikey');
		}
		
		if (isset($this->request->post['payment_layerpayment_secretkey'])) {
			$data['payment_layerpayment_secretkey'] = $this->request->post['payment_layerpayment_secretkey'];
		} else {
			$data['payment_layerpayment_secretkey'] = $this->config->get('payment_layerpayment_secretkey');
		}
		
		if (isset($this->request->post['payment_layerpayment_total'])) {
			$data['payment_layerpayment_total'] = $this->request->post['payment_layerpayment_total'];
		} else {
			$data['payment_layerpayment_total'] = $this->config->get('payment_layerpayment_total'); 
		} 
				
		if (isset($this->request->post['payment_layerpayment_order_status_id'])) {
			$data['payment_layerpayment_order_status_id'] = $this->request->post['payment_layerpayment_order_status_id'];
		} else {
			$data['payment_layerpayment_order_status_id'] = $this->config->get('payment_layerpayment_order_status_id'); 
		} 

		if (isset($this->request->post['payment_layerpayment_order_fail_status_id'])) {
			$data['payment_layerpayment_order_fail_status_id'] = $this->request->post['payment_layerpayment_order_fail_status_id'];
		} else {
			$data['payment_layerpayment_order_fail_status_id'] = $this->config->get('payment_layerpayment_order_fail_status_id'); 
		} 
		
		$this->load->model('localisation/order_status');
		
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		
		if (isset($this->request->post['payment_layerpayment_geo_zone_id'])) {
			$data['payment_layerpayment_geo_zone_id'] = $this->request->post['payment_layerpayment_geo_zone_id'];
		} else {
			$data['payment_layerpayment_geo_zone_id'] = $this->config->get('payment_layerpayment_geo_zone_id'); 
		} 
		
		$this->load->model('localisation/geo_zone');
										
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		
		if (isset($this->request->post['payment_layerpayment_status'])) {
			$data['payment_layerpayment_status'] = $this->request->post['payment_layerpayment_status'];
		} else {
			$data['payment_layerpayment_status'] = $this->config->get('payment_layerpayment_status');
		}
		
		if (isset($this->request->post['payment_layerpayment_sort_order'])) {
			$data['payment_layerpayment_sort_order'] = $this->request->post['payment_layerpayment_sort_order'];
		} else {
			$data['payment_layerpayment_sort_order'] = $this->config->get('payment_layerpayment_sort_order');
		}
        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

				
		$this->response->setOutput($this->load->view('extension/payment/layerpayment', $data));
	}

	private function validate() {
		$flag=false;
		
		if (!$this->user->hasPermission('modify', 'extension/payment/layerpayment')) {
			$this->error['error_warning'] = $this->language->get('error_permission');
		}
		//PayU both parameters mandatory
		if($this->request->post['payment_layerpayment_apikey'] || $this->request->post['payment_layerpayment_secretkey']) {
			if (!$this->request->post['payment_layerpayment_apikey']) {
				$this->error['error_apikey'] = $this->language->get('error_apikey');
			}
			
			if (!$this->request->post['payment_layerpayment_secretkey']) {
				$this->error['error_secretkey'] = $this->language->get('error_secretkey');
			}
		}
		if($this->request->post['payment_layerpayment_apikey'] && $this->request->post['payment_layerpayment_secretkey']) {
			$flag=true;	
		}
		
		if (!$this->request->post['payment_layerpayment_mode']) {
			$this->error['error_mode'] = $this->language->get('error_mode');
		}
		
		if(!$flag && $this->request->post['payment_layerpayment_status'] == '1')
		{
			$this->error['error_status'] = $this->language->get('error_status');
		}

		return !$this->error;
	}
}
?>