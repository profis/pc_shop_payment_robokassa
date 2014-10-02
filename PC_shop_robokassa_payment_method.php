<?php

class PC_shop_robokassa_payment_method extends PC_shop_payment_method {
	public function make_online_payment() {
		$login = $this->cfg['pc_shop_payment_robokassa']['robokassa_login'];
		$pass = $this->cfg['pc_shop_payment_robokassa']['robokassa_pass1'];
		$test = $this->cfg['pc_shop_payment_robokassa']['robokassa_test'];

		$orderId = $this->_order_data['id'];
		$total = number_format($this->_order_data['total_price'], 2, '.', '');

		$description = "Oplata zakaza N" . $orderId;

		$checksum = md5("{$login}:{$total}:{$orderId}:{$pass}");

		$params = array(
			'MrchLogin' => $login,
			'OutSum' => $total,
			'InvId' => $orderId,
			'Desc' => $description,
			'SignatureValue' => $checksum,
			'Encoding' => 'utf-8',
			'Culture' => $this->site->ln,
		);

		$url = $test ? 'http://test.robokassa.ru/Index.aspx' : 'https://auth.robokassa.ru/Merchant/Index.aspx';
		$this->core->Redirect($url . '?' . http_build_query($params));
	}
	
	public function callback() {
		$login = $this->cfg['pc_shop_payment_robokassa']['robokassa_login'];
		$pass = $this->cfg['pc_shop_payment_robokassa']['robokassa_pass2'];

		$total = $this->_response['OutSum'];
		$orderId = $this->_response['InvId'];
		$checksum = $this->_response['SignatureValue'];

		if( md5("{$total}:{$orderId}:$checksum") == strtolower($checksum) ) {
			echo "OK{$orderId}\n";
			return true;
		}

		echo "bad sign\n";
		return false;
	}
	
	public function accept() {
		$login = $this->cfg['pc_shop_payment_robokassa']['robokassa_login'];
		$pass = $this->cfg['pc_shop_payment_robokassa']['robokassa_pass1'];

		$total = $this->_response['OutSum'];
		$orderId = $this->_response['InvId'];
		$checksum = $this->_response['SignatureValue'];

		if( md5("{$total}:{$orderId}:$checksum") == strtolower($checksum) ) {
			return self::STATUS_ERROR;
		}
		return self::STATUS_SUCCESS;
	}

	protected function _get_response_payment_status() {
		return true;
	}

	protected function _get_response_order_id() {
		return $this->_response['InvId'];
	}

	protected function _get_response_test() {
		return $this->cfg['pc_shop_payment_robokassa']['robokassa_test'];
	}

	protected function _get_response_amount() {
		return $this->_response['OutSum'];
	}

	protected function _get_response_currency() {
		$order_model = new PC_shop_order_model();
		$order = $order_model->get_one(array(
			'where' => array(
				'id' => $this->_get_response_order_id(),
				'payment_option' => 'robokassa'
			),
		));
		if( !$order )
			return null;
		return $order['currency'];
	}

}
