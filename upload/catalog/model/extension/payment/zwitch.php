<?php

class ModelExtensionPaymentZwitch extends Model
{
    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/zwitch');

        $status = false;
        $method_data = [];

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_zwitch_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if (!$this->config->get('payment_zwitch_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		if ($status) {
			$method_data = [
				'code'          => 'zwitch',
				'title'         => $this->language->get('heading_title'),
				'trems'         => '',
				'sort_order'    => $this->config->get('payment_zwitch_transfer_sort_order')
			];
		}

		return $method_data;
    }

    public function createPaymentToken()
    {
        // Generate the timestamp in IST
        date_default_timezone_set('Asia/Kolkata');
        $timestamp = date('Y-m-d\TH:i:s'); // IST timestamp

        $endpoint = "https://api.zwitch.io/v1/pg/payment_token";

        if ( $this->config->get('payment_zwitch_test_mode') ) {
            $endpoint = "https://api.zwitch.io/v1/pg/sandbox/payment_token";
        }

        $connection_options = [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($this->getOrderDetails()),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "X-O-Timestamp: {$timestamp}",
                "Authorization: Bearer " . $this->getBearerToken(),
            ],
        ];

        $connection = curl_init();
        curl_setopt_array($connection, $connection_options);
        $request = json_decode(curl_exec($connection), true);
        curl_close($connection);

        return $request['id'];

    }

    public function getOrderDetails()
    {
        $this->load->model('checkout/order');

        $order_id = $this->session->data['order_id'];
        $order_info = $this->model_checkout_order->getOrder($order_id);

        return [
            "amount" => number_format($order_info['total'], 2, '.', ''),
            "currency" => "INR",
            "mtx" => bin2hex(random_bytes(10)) . '_order_' .  $order_id,
            "contact_number" => $order_info['telephone'],
            "email_id" => $order_info['email'],
        ];
        
    }

    public function getBearerToken(): string
    {
        return "{$this->config->get('payment_zwitch_access_key')}:{$this->config->get('payment_zwitch_secret_key')}";
    }
}