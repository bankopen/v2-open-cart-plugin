<?php
namespace Opencart\Catalog\Model\Extension\Zwitch\Payment;

class Zwitch extends \Opencart\System\Engine\Model
{
    public function getMethods()
    {
        $this->load->language('extension/zwitch/payment/zwitch');

        $status = true;

		if ($this->cart->hasSubscription()) {
			$status = false;
		}

		$method_data = [];

		if ($status) {
			$option_data['zwitch'] = [
				'code' => 'zwitch.zwitch',
				'name' => $this->language->get('heading_title')
			];

			$method_data = [
				'code'       => 'zwitch',
				'name'       => $this->language->get('heading_title'),
				'option'     => $option_data,
				'sort_order' => $this->config->get('payment_zwitch_transfer_sort_order')
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
            "mtx" => bin2hex(random_bytes(10)) . 'order_' .  $order_id,
            "contact_number" => $order_info['telephone'],
            "email_id" => $order_info['email'],
        ];
        
    }

    public function getBearerToken(): string
    {
        return "{$this->config->get('payment_zwitch_access_key')}:{$this->config->get('payment_zwitch_secret_key')}";
    }
}