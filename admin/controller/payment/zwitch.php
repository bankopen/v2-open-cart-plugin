<?php
namespace Opencart\Admin\Controller\Extension\Zwitch\Payment;
/**
 * Class Bank Transfer
 *
 * @package Opencart\Admin\Controller\Extension\zwitch\Payment
 */
class Zwitch extends \Opencart\System\Engine\Controller {

    public function index(): void
    {
		$this->load->language('extension/zwitch/payment/zwitch');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/zwitch/payment/zwitch', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/zwitch/payment/zwitch' . $this->separator() . 'save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment');

		$data['payment_zwitch_secret_key'] = $this->config->get('payment_zwitch_secret_key');
		$data['payment_zwitch_access_key'] = $this->config->get('payment_zwitch_access_key');
		$data['payment_zwitch_page_color'] = $this->config->get('payment_zwitch_page_color');
		
        // loading geo_zone model
        $this->load->model('localisation/geo_zone');
        // getting all zeo zones
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		$data['payment_upay_geo_zone_id'] = $this->config->get('payment_upay_geo_zone_id');

        $this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        $data['payment_zwitch_order_status_id'] = $this->config->get('payment_zwitch_order_status_id');

		$data['payment_zwitch_test_mode'] = $this->config->get('payment_zwitch_test_mode');
		$data['payment_zwitch_status'] = $this->config->get('payment_zwitch_status');
		$data['payment_zwitch_sort_order'] = $this->config->get('payment_zwitch_sort_order');

		$data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/zwitch/payment/zwitch', $data));

    }

	public function save(): void {
		$this->load->language('extension/zwitch/payment/zwitch');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/zwitch/payment/zwitch')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (empty($this->request->post['payment_zwitch_secret_key'])) {
			$json['error'] = $this->language->get('error_secret_key');
		}

		if (empty($this->request->post['payment_zwitch_access_key'])) {
			$json['error'] = $this->language->get('error_access_key');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('payment_zwitch', $this->request->post);

			$json['success'] = $this->language->get('text_success');
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

	public function install()
	{
		// enable telephone field
		if ( ! $this->config->get('config_telephone_required') ) {
			$this->config->set('config_telephone_required', 1);
		}

		if ( ! $this->config->get('config_telephone_display') ) {
			$this->config->set('config_telephone_display', 1);
		}
	}
}