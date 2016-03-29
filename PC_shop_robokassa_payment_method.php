<?php

class PC_shop_robokassa_payment_method extends PC_shop_payment_method {
	public function Init($payment_data, $order_data = array(), $shop_site = null) {
		parent::Init($payment_data, $order_data, $shop_site);
		$this->_response = $_REQUEST;
	}

	public function make_online_payment() {
		$login = $this->cfg['pc_shop_payment_robokassa']['robokassa_login'];
		$pass = $this->cfg['pc_shop_payment_robokassa']['robokassa_pass1'];

		$orderId = $this->_order_data['id'];
		$total = $this->getOutSum();

		if( $this->_order_data['currency'] != 'RUB' )
			throw new \Exception('Currencies other than RUB may not be used with Robokassa payment method');

		$ln = ($this->site->ln == 'ru') ? 'ru' : 'en';

		$description = strtr($this->core->Get_variable('order_payment_description', $ln, 'pc_shop_payment_robokassa'), array(
			'{orderId}' => $orderId,
		));

		$checksum = md5("{$login}:{$total}:{$orderId}:{$pass}");

		$params = array(
			'MerchantLogin' => $login,
			'OutSum' => $total,
			'InvId' => $orderId,
			'Desc' => $description,
			'SignatureValue' => $checksum,
			'Encoding' => 'utf-8',
			'Culture' => $ln,
			'IsTest' => $this->cfg['pc_shop_payment_robokassa']['robokassa_test'] ? '1' : '0',
		);
		
		$url = 'https://auth.robokassa.ru/Merchant/Index.aspx';
		$this->core->Redirect($url . '?' . http_build_query($params));
	}

	/**
	 * WARNING: This method is not valid, since the robokassa API returns list of grouped payment methods instead of
	 * currencies.
	 *
	 * @return array
	 * @deprecated
	 */
	public function getCurrencies() {
		$login = $this->cfg['pc_shop_payment_robokassa']['robokassa_login'];
		$ln = ($this->site->ln == 'ru') ? 'ru' : 'en';

		$url = $this->cfg['pc_shop_payment_robokassa']['robokassa_test']
			? 'http://test.robokassa.ru/Webservice/Service.asmx/GetCurrencies'
			: 'https://auth.robokassa.ru/Merchant/WebService/Service.asmx/GetCurrencies';
		$url .= '?MerchantLogin=' . $login . '&Language=' . $ln;

		$xml = file_get_contents($url);
		$list = @new SimpleXMLElement($xml);
		$currencies = array();
		if( !empty($list->Groups->Group) )
			foreach($list->Groups->Group as $group)
				foreach($group->Items->Currency as $currency)
					$currencies[(string)$currency['Label']] = (string)$currency['Name'];

		return $currencies;
	}

	/**
	 * @return array
	 * @deprecated
	 */
	public function getPaymentMethods() {
		$login = $this->cfg['pc_shop_payment_robokassa']['robokassa_login'];
		$currency = $this->_shop_site->price->get_base_currency();
		$total = $this->getOutSum();
		$ln = ($this->site->ln == 'ru') ? 'ru' : 'en';

		$url = $this->cfg['pc_shop_payment_robokassa']['robokassa_test']
			? 'http://test.robokassa.ru/Webservice/Service.asmx/GetPaymentMethods'
			: 'https://auth.robokassa.ru/Merchant/WebService/Service.asmx/GetPaymentMethods';
		$url .= '?MerchantLogin=' . $login . '&IncCurrLabel=' . $currency . '&OutSum=' . $total . '&Language=' . $ln;

		$xml = file_get_contents($url);
		$list = @new SimpleXMLElement($xml);
		$methods = array();
		if( !empty($list->Methods->Method) )
			foreach($list->Methods->Method as $method)
				$methods[(string)$method['Code']] = (string)$method['Description'];

		return $methods;
	}

	private function getOutSum() {
		return number_format($this->_order_data['total_price'], 2, '.', '');
	}

	public function callback() {
		$pass = $this->cfg['pc_shop_payment_robokassa']['robokassa_pass2'];

		// echo json_encode($this->_response);

		$total = $this->_response['OutSum'];
		$orderId = $this->_response['InvId'];
		$checksum = $this->_response['SignatureValue'];

		if( md5("{$total}:{$orderId}:{$pass}") == strtolower($checksum) ) {
			echo "OK{$orderId}\n";

			$this->order_id = $this->_get_response_order_id();
			$this->_order_data = $this->_get_order_data($this->order_id);

			return true;
		}

		echo "bad sign\n";
		return false;
	}
	
	public function accept() {
		$pass = $this->cfg['pc_shop_payment_robokassa']['robokassa_pass1'];

		$total = $this->_response['OutSum'];
		$orderId = $this->_response['InvId'];
		$checksum = $this->_response['SignatureValue'];

		if( md5("{$total}:{$orderId}:{$pass}") != strtolower($checksum) )
			return self::STATUS_ERROR;

		$this->order_id = $this->_get_response_order_id();
		$this->_order_data = $this->_get_order_data($this->order_id);

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
