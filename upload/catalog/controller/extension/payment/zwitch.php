<?php

class ControllerExtensionPaymentZwitch extends Controller 
{
    public function index()
    {
        $this->load->language('extension/payment/zwitch');
        $this->load->model('extension/payment/zwitch');
        
        $data['test_mode'] = $this->config->get('payment_zwitch_test_mode');
        $data['logo'] = $this->config->get('config_url') . 'image/' . $this->config->get('config_logo') ?? '';
        $data['payment_zwitch_page_color'] = $this->config->get('payment_zwitch_page_color');
        $data['payment_token'] = $this->model_extension_payment_zwitch->createPaymentToken();
        $data['access_key'] = $this->config->get('payment_zwitch_access_key');


        $data['success_redirect_url'] = $this->url->link('checkout/success');
        $data['failure_redirect_url'] = $this->url->link('checkout/failure');
        $data['cancel_redirect_url'] = $this->url->link('checkout/cart');

        return $this->load->view('extension/payment/zwitch', $data);
    }

    public function confirm()
    {
		$json = [];

        if (isset($this->request->post['payment_id']) && isset($this->session->data['payment_method']['code']) && $this->session->data['payment_method']['code'] == 'zwitch') {
            
            $this->load->language('extension/payment/zwitch');

            if (!isset($this->session->data['order_id'])) {
                $json['error'] = $this->language->get('error_order');
            }

            if (!$json) {
                $this->load->model('checkout/order');

                $comment = "Payment ID #" . $this->request->post['payment_id'];

                $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_zwitch_order_status_id'), $comment, true);
                
                $json['redirect'] = $this->url->link('checkout/success', '', true);
            }
        }

        $this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
    }

}