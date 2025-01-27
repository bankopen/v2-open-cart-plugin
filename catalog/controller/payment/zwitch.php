<?php
namespace Opencart\Catalog\Controller\Extension\Zwitch\Payment;

class Zwitch extends \Opencart\System\Engine\Controller 
{
    public function index()
    {
        $this->load->language('extension/zwitch/payment/zwitch');
        $this->load->model('extension/zwitch/payment/zwitch');
        
        $data['language'] = $this->config->get('config_language');
        $data['test_mode'] = $this->config->get('payment_zwitch_test_mode');
        $data['logo'] = $this->config->get('config_url') . 'image/' . $this->config->get('config_logo') ?? '';
        $data['payment_zwitch_page_color'] = $this->config->get('payment_zwitch_page_color');
        $data['payment_token'] = $this->model_extension_zwitch_payment_zwitch->createPaymentToken();
        $data['access_key'] = $this->config->get('payment_zwitch_access_key');
        $data['separator'] = $this->separator();


        $data['success_redirect_url'] = $this->url->link('checkout/success');
        $data['failure_redirect_url'] = $this->url->link('checkout/failure');
        $data['cancel_redirect_url'] = $this->url->link('checkout/cart');

        return $this->load->view('extension/zwitch/payment/zwitch', $data);
    }

    public function confirm()
    {
        $this->load->language('extension/zwitch/payment/zwitch');

		$json = [];

		if (!isset($this->session->data['order_id'])) {
			$json['error'] = $this->language->get('error_order');
		}

        if (!isset($this->session->data['payment_method']) || $this->session->data['payment_method']['code'] != 'zwitch.zwitch') {
			$json['error'] = $this->language->get('error_payment_method');
		}

        if (!$json) {
            $this->load->model('checkout/order');

            $comment = "Payment ID #" . $this->request->post['payment_id'];

			$this->model_checkout_order->addHistory($this->session->data['order_id'], $this->config->get('payment_zwitch_order_status_id'), $comment, true);
            
            $json['redirect'] = $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true);
        }

        $this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
    }

    private function separator():string
    {
        if (VERSION >= '4.0.2.0') {
            return '.';
        }

        return '|';
    }
}