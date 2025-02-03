<?php

class ControllerExtensionPaymentZwitch extends Controller {

	private $error = [];

    public function index(): void
    {
		$this->load->language('extension/payment/zwitch');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_zwitch', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}


		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true),
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/zwitch', 'user_token=' . $this->session->data['user_token'], true),
		];

		$data['action'] = $this->url->link('extension/payment/zwitch', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_zwitch_secret_key'])) {
			$data['payment_zwitch_secret_key'] = $this->request->post['payment_zwitch_secret_key'];
		} else {
			$data['payment_zwitch_secret_key'] = $this->config->get('payment_zwitch_secret_key');
		}

		if (isset($this->request->post['payment_zwitch_access_key'])) {
			$data['payment_zwitch_access_key'] = $this->request->post['payment_zwitch_access_key'];
		} else {
			$data['payment_zwitch_access_key'] = $this->config->get('payment_zwitch_access_key');
		}

		if (isset($this->request->post['payment_zwitch_page_color'])) {
			$data['payment_zwitch_page_color'] = $this->request->post['payment_zwitch_page_color'];
		} else {
			$data['payment_zwitch_page_color'] = $this->config->get('payment_zwitch_page_color');
		}

		if (isset($this->request->post['payment_zwitch_test_mode'])) {
			$data['payment_zwitch_test_mode'] = $this->request->post['payment_zwitch_test_mode'];
		} else {
			$data['payment_zwitch_test_mode'] = $this->config->get('payment_zwitch_test_mode');
		}

		if (isset($this->request->post['payment_zwitch_status'])) {
			$data['payment_zwitch_status'] = $this->request->post['payment_zwitch_status'];
		} else {
			$data['payment_zwitch_status'] = $this->config->get('payment_zwitch_status');
		}

		if (isset($this->request->post['payment_zwitch_sort_order'])) {
			$data['payment_zwitch_sort_order'] = $this->request->post['payment_zwitch_sort_order'];
		} else {
			$data['payment_zwitch_sort_order'] = $this->config->get('payment_zwitch_sort_order');
		}
		
        // loading geo_zone model
        $this->load->model('localisation/geo_zone');
        // getting all zeo zones
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_zwitch_geo_zone_id'])) {
			$data['payment_zwitch_geo_zone_id'] = $this->request->post['payment_zwitch_geo_zone_id'];
		} else {
			$data['payment_zwitch_geo_zone_id'] = $this->config->get('payment_zwitch_geo_zone_id');
		}

        $this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_zwitch_order_status_id'])) {
			$data['payment_zwitch_order_status_id'] = $this->request->post['payment_zwitch_order_status_id'];
		} else {
			$data['payment_zwitch_order_status_id'] = $this->config->get('payment_zwitch_order_status_id');
		}

		$data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/zwitch', $data));

    }

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/zwitch')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (empty($this->request->post['payment_zwitch_secret_key'])) {
			$this->error['warning'] = $this->language->get('error_secret_key');
		}

		if (empty($this->request->post['payment_zwitch_access_key'])) {
			$this->error['warning'] = $this->language->get('error_access_key');
		}

		return !$this->error;
	}
}