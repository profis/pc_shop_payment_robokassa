<?php
function pc_shop_payment_robokassa_install($controller) {
	global $core;
	
	$payment_option_model = new PC_shop_payment_option_model();
	$payment_option_model->insert(array('code' => 'robokassa'), array(
		'lt' => array(
			'name' => 'Per robokassa.ru sistemą'
		),
		'en' => array(
			'name' => 'Using robokassa.ru system'
		),
		'ru' => array(
			'name' => 'Используя систему robokassa.ru'
		)
	), array('ignore' => true));
	
	$core->Set_config_if('robokassa_login', '', 'pc_shop_payment_robokassa');
	$core->Set_config_if('robokassa_pass1', '', 'pc_shop_payment_robokassa');
	$core->Set_config_if('robokassa_pass2', '', 'pc_shop_payment_robokassa');
	$core->Set_config_if('robokassa_test', '', 'pc_shop_payment_robokassa');

	$core->Set_variable_if('ru', 'order_payment_description', 'Оплата заказа №{orderId}', 'pc_shop_payment_robokassa');
	$core->Set_variable_if('en', 'order_payment_description', 'Order no. {orderId} payment', 'pc_shop_payment_robokassa');

	return true;
}

function pc_shop_payment_robokassa_uninstall($controller) {
	global $core;
	$payment_option_model = new PC_shop_payment_option_model();
	$payment_option_model->delete(array('where' => array('code' => 'robokassa')));
	return true;
}