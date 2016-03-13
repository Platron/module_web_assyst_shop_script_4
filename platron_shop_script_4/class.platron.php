<?php
/**
 * @connect_module_class_name Platron
 * @package DynamicModules
 * @subpackage Payment
 */
class Platron extends PaymentModule {

	var $type = PAYMTD_TYPE_ONLINE;
	var $url = 'https://www.platron.ru/payment.php';
//	var $default_logo = 'http://www.webasyst.net/collections/design/payment-icons/robokassa.gif';

	function _initVars(){
			
		parent::_initVars();
		$this->title = 'Platron';
		$this->description = 'Через систему оплаты <a href="www.platron.ru">Platron</a>';
		
		$this->method_title = 'Platon';
		$this->method_description = 'Через систему оплаты <a href="www.platron.ru">Platron</a>';
		
		$this->sort_order = 1;
			
		$this->Settings = array(
			'CONF_PLATRON_PG_MERCHANT_ID',
			'CONF_PLATRON_PG_SECRET_KEY',
			'CONF_PLATRON_PG_LIFETIME',
			'CONF_PLATRON_PG_TESTING_MODE',
			'CONF_PLATRON_ORDERSTATUS_SUCCESS',
			'CONF_PLATRON_ORDERSTATUS_FAIL',
			'CONF_PLATRON_ORDERSTATUS_TO_CHECK',
		);
	}

	function _initSettingFields(){

		$this->SettingsFields['CONF_PLATRON_PG_MERCHANT_ID'] = array(
				'settings_value' 		=> '',
				'settings_title' 		=> 'номер магазина',
				'settings_description' 	=> 'Можно посмотреть в <a href="https://www.platron.ru/admin/merchants.php">настройках магазина</a>',
				'settings_html_function'=> 'setting_TEXT_BOX(0,',
				'sort_order' 			=> 1,
		);

		$this->SettingsFields['CONF_PLATRON_PG_SECRET_KEY'] = array(
				'settings_value' 		=> '',
				'settings_title' 		=> 'секретный ключ',
				'settings_description' 	=> 'Можно посмотреть в <a href="https://www.platron.ru/admin/merchants.php">настройках магазина</a>',
				'settings_html_function' => 'setting_TEXT_BOX(0,',
				'sort_order' 			=> 1,
		);
		$this->SettingsFields['CONF_PLATRON_PG_LIFETIME'] = array(
				'settings_value' 		=> '0',
				'settings_title' 		=> 'Время жизни счета в минутах',
				'settings_description' 	=> 'Максимальное значение 60*24*7. Запрос check невозможно реализовать из-за отсутствия доступа из плагина к заказу. Поэтому заказы нельзя удалять или отменять.',
				'settings_html_function'=> 'setting_TEXT_BOX(0,',
				'sort_order' 			=> 1,
		);
		$this->SettingsFields['CONF_PLATRON_PG_TESTING_MODE'] = array(
				'settings_value' 		=> '1',
				'settings_title' 		=> 'Тестовый режим',
				'settings_description' 	=> 'Снять для боевого режима',
				'settings_html_function'=> 'setting_CHECK_BOX(',
				'sort_order' 			=> 1,
		);
		$this->SettingsFields['CONF_PLATRON_ORDERSTATUS_SUCCESS'] = array(
			'settings_value' 		=> '-1',
			'settings_title' 			=> 'Статус оплаченного заказа',
			'settings_description' 	=> 'Статус, который будет автоматически установлен для заказа после успешной оплаты',
			'settings_html_function' 	=> 'setting_SELECT_BOX(PaymentModule::_getStatuses(),',
			'sort_order' 			=> 1,
		);
		$this->SettingsFields['CONF_PLATRON_ORDERSTATUS_FAIL'] = array(
			'settings_value' 		=> '-1',
			'settings_title' 			=> 'Статус неудачного заказа',
			'settings_description' 	=> 'Статус, который будет автоматически установлен для заказа после неуспешной оплаты',
			'settings_html_function' 	=> 'setting_SELECT_BOX(PaymentModule::_getStatuses(),',
			'sort_order' 			=> 1,
		);
		$this->SettingsFields['CONF_PLATRON_ORDERSTATUS_TO_CHECK'] = array(
			'settings_value' 		=> '-1',
			'settings_title' 			=> 'Статус заказа, доступного для оплаты',
			'settings_description' 	=> 'Статус, который будет автоматически установлен для заказа после успешной оплаты',
			'settings_html_function' 	=> 'setting_SELECT_BOX(PaymentModule::_getStatuses(),',
			'sort_order' 			=> 1,
		);
	}

	function after_processing_html( $orderID, $active = true  ){

		$order = ordGetOrder( $orderID );
		$content = ordGetOrderContent($orderID);
		$description =CONF_SHOP_NAME.": ";
		foreach($content as $content_item){
			$description .= preg_replace('/^\[[^\]]*]/','',str_replace(array('&nbsp;'),array(' '),strip_tags($content_item['name']).'x'.$content_item['Quantity'].";"));
		}

		$description = preg_replace('/[^\.\?,\[]\(\):;"@\\%\s\w\d]+/',' ',$description);

		//**
		$form_fields = array();
		$form_fields['pg_merchant_id'] = $this->_getSettingValue('CONF_PLATRON_PG_MERCHANT_ID');
		$form_fields['pg_order_id'] = $order['orderID'];
		$form_fields['pg_currency'] = $order['currency_code'];
//		$form_fields['pg_amount'] = sprintf('%0.2f',($order['clear_total_price']))+sprintf('%0.2f',(floatval(@$order['shipping_cost'])));
		$form_fields['pg_amount'] = sprintf('%0.2f',($order['order_amount']));
		$form_fields['pg_testing_mode'] = $this->_getSettingValue('CONF_PLATRON_PG_TESTING_MODE');
		$form_fields['pg_lifetime'] = $this->_getSettingValue('CONF_PLATRON_PG_LIFETIME');
		$form_fields['pg_description'] = "Оплата заказа #".$order['orderID'];
		$form_fields['pg_check_url'] = $this->getDirectTransactionResultURL('check',null,false);
		$form_fields['pg_result_url'] = $this->getDirectTransactionResultURL('result',null,false);
		$form_fields['pg_success_url'] = $this->getTransactionResultURL('success',array(__FILE__));
		$form_fields['pg_failure_url'] = $this->getTransactionResultURL('failure',array(__FILE__));
		$form_fields['pg_salt'] = rand(21,43433); // Параметры безопасности сообщения. Необходима генерация pg_salt и подписи сообщения.
		
		if(!empty($order['customer_phone'])){
			preg_match_all("/\d/", @$order['customer_phone'], $array);
			if(!empty($array)){
				$strPhone = implode($array[0]);
				if(strlen($strPhone) == 11)
					$form_fields['pg_user_phone'] = '7'.substr($strPhone,1);
				if(strlen($strPhone) == 10)
					$form_fields['pg_user_phone'] = $strPhone;
				if(strlen($strPhone) == 9)
					$form_fields['pg_user_phone'] = '7'.$strPhone;
			}
		}
		
		if(!empty($order['reg_fields_values'])){
			foreach($order['reg_fields_values'] as $arrFields){

				if($arrFields['reg_field_ID']!=9) continue;

				preg_match_all("/\d/", @$arrFields['reg_field_value'], $array);		
				$strPhone = implode($array[0]);
				if(strlen($strPhone) == 11)
					$form_fields['pg_user_phone'] = '7'.substr($strPhone,1);
				if(strlen($strPhone) == 10)
					$form_fields['pg_user_phone'] = $strPhone;
				if(strlen($strPhone) == 9)
					$form_fields['pg_user_phone'] = '7'.$strPhone;
			}
		}
		
		if(!empty($order['customer_email']) && preg_match('/^.+@.+\..+$/',$order['customer_email'])){
			$form_fields['pg_user_email'] = $order['customer_email'];
			$form_fields['pg_user_contact_email'] = $order['customer_email'];
		}
		$form_fields['cms_payment_module'] = 'WEBASSYST';
		$form_fields['pg_sig'] = self::make('payment.php', $form_fields, $this->_getSettingValue('CONF_PLATRON_PG_SECRET_KEY'));
		
//		var_dump($form_fields);
//		die();

		$form = "\n<form id=\"platron_form\" method=\"POST\" action=\"$this->url\" style=\"text-align:center;\">\n";
		foreach($form_fields as $field_name=>$field_value){
			if(!isset($field_value))continue;
			$field_value = xHtmlSpecialChars($field_value);
			$form .= "\n\t<input type=\"hidden\" value=\"$field_value\" name=\"$field_name\">";
		}
		$form .= "\n".'<input type="submit" value="Оплатить" />'."\n";
		$form .= "\n</form>\n";

		if($active){
			$form .= '<script type="text/javascript">
			<!--
			setTimeout(\'document.getElementById("platron_form").submit();\',100);
			//-->
			</script>';
		}

		return $form;
	}

	function transactionResultHandler($transaction_result = '',$message = '',$source = 'frontend'){
		if($source != 'handler')
			return parent::transactionResultHandler($transaction_result,$message,$source);
			
		$arrRequest = !(empty($_GET))?$_GET:$_POST;
		$thisScriptName = self::getOurScriptName();

		if(!self::check($arrRequest['pg_sig'], $thisScriptName, $arrRequest, $this->_getSettingValue('CONF_PLATRON_PG_SECRET_KEY')))
			die("Bad signature");
		$order = _getOrderById($arrRequest['pg_order_id']);
		$arrStatuses = array();
		$arrNotExplodedStatuses = explode(',',$this->_getStatuses());
		foreach($arrNotExplodedStatuses as $strOneStatus){
			$arrOneStatus = explode(':', $strOneStatus);
			$arrStatuses[$arrOneStatus[1]] = $arrOneStatus[0];
		}

		if($arrRequest['transaction_result'] == 'check'){
			$bCheckResult = 1;
			if(empty($order) || !$order){
				$bCheckResult = 0;
				$error_desc = 'Нет такого заказа';
			}
			if($order['statusID'] != $this->_getSettingValue('CONF_PLATRON_ORDERSTATUS_TO_CHECK')){
				$bCheckResult = 0;
				$error_desc = 'Доступный для оплаты заказ: '.$arrStatuses[$this->_getSettingValue('CONF_PLATRON_ORDERSTATUS_TO_CHECK')].'. Сейчас '.$arrStatuses[$order['statusID']];
			}
			
			$arrResp['pg_salt']              = $arrRequest['pg_salt']; // в ответе необходимо указывать тот же pg_salt, что и в запросе
			$arrResp['pg_status']            = $bCheckResult ? 'ok' : 'error';
			$arrResp['pg_error_description'] = $bCheckResult ?  ""  : $error_desc;
			$arrResp['pg_sig']				 = self::make($thisScriptName, $arrResp, $this->_getSettingValue('CONF_PLATRON_PG_SECRET_KEY'));
			
			$objResponce = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><response/>');
			$objResponce->addChild('pg_salt', $arrResp['pg_salt']);
			$objResponce->addChild('pg_status', $arrResp['pg_status']);
			$objResponce->addChild('pg_error_description', $arrResp['pg_error_description']);
			$objResponce->addChild('pg_sig', $arrResp['pg_sig']);
			
			header('Content-type: text/xml');
			print $objResponce->asXML();
		}
		if($arrRequest['transaction_result'] == 'result'){
			$bCheckResult = 1;
			$pg_status = 'ok';
			$pg_description = 'Оплата принята';
			if ($arrRequest['pg_result'] == 1) {
				if(empty($order) || !$order){
					$pg_description = 'Нет такого заказа';
					if($arrRequest['pg_can_reject'])
						$pg_status = 'rejected';
				}
				if($order['statusID'] != $this->_getSettingValue('CONF_PLATRON_ORDERSTATUS_TO_CHECK') && $order['statusID'] != $this->_getSettingValue('CONF_PLATRON_ORDERSTATUS_SUCCESS')){
					$pg_description = 'Доступный для оплаты заказ: '.$arrStatuses[$this->_getSettingValue('CONF_PLATRON_ORDERSTATUS_TO_CHECK')].'. Сейчас '.$arrStatuses[$order['statusID']];
					if($arrRequest['pg_can_reject'])
						$pg_status = 'rejected';
				}
				if($pg_status == 'ok')
					// сменить статус заказа на оплачен
					ostSetOrderStatusToOrder( $order['orderID'], $this->_getSettingValue('CONF_PLATRON_ORDERSTATUS_SUCCESS'),'Заказ оплачен. Номер заказа в Платрон '.$arrRequest['pg_payment_id'],0);
			}
			else {
				$pg_description = 'Информация принята';
				// смена статуса заказа на отменен
				if($this->_getSettingValue('CONF_PLATRON_ORDERSTATUS_TO_CHECK') == $order['statusID'])
					ostSetOrderStatusToOrder( $order['orderID'], $this->_getSettingValue('CONF_PLATRON_ORDERSTATUS_FAIL'),'Не успешная оплата. Номер заказа в Платрон '.$arrRequest['pg_payment_id'],0);
			}

			$objResponce = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><response/>');
			$objResponce->addChild('pg_salt', $arrRequest['pg_salt']); // в ответе необходимо указывать тот же pg_salt, что и в запросе
			$objResponce->addChild('pg_status', $pg_status);
			$objResponce->addChild('pg_description', $pg_description);
			$objResponce->addChild('pg_sig', self::makeXML($thisScriptName, $objResponce, $this->_getSettingValue('CONF_PLATRON_PG_SECRET_KEY')));

			header('Content-type: text/xml');
			print $objResponce->asXML();
		}
		die();
	}
	
	/**
	 * Get script name from URL (for use as parameter in self::make, self::check, etc.)
	 *
	 * @param string $url
	 * @return string
	 */
	public static function getScriptNameFromUrl ( $url )
	{
		$path = parse_url($url, PHP_URL_PATH);
		$len  = strlen($path);
		if ( $len == 0  ||  '/' == $path{$len-1} ) {
			return "";
		}
		return basename($path);
	}
	
	/**
	 * Get name of currently executed script (need to check signature of incoming message using self::check)
	 *
	 * @return string
	 */
	public static function getOurScriptName ()
	{
		return self::getScriptNameFromUrl( $_SERVER['PHP_SELF'] );
	}

	/**
	 * Creates a signature
	 *
	 * @param array $arrParams  associative array of parameters for the signature
	 * @param string $strSecretKey
	 * @return string
	 */
	public static function make ( $strScriptName, $arrParams, $strSecretKey )
	{
		return md5( self::makeSigStr($strScriptName, $arrParams, $strSecretKey) );
	}

	/**
	 * Verifies the signature
	 *
	 * @param string $signature
	 * @param array $arrParams  associative array of parameters for the signature
	 * @param string $strSecretKey
	 * @return bool
	 */
	public static function check ( $signature, $strScriptName, $arrParams, $strSecretKey )
	{
		return (string)$signature === self::make($strScriptName, $arrParams, $strSecretKey);
	}


	/**
	 * Returns a string, a hash of which coincide with the result of the make() method.
	 * WARNING: This method can be used only for debugging purposes!
	 *
	 * @param array $arrParams  associative array of parameters for the signature
	 * @param string $strSecretKey
	 * @return string
	 */
	static function debug_only_SigStr ( $strScriptName, $arrParams, $strSecretKey ) {
		return self::makeSigStr($strScriptName, $arrParams, $strSecretKey);
	}


	private static function makeSigStr ( $strScriptName, $arrParams, $strSecretKey ) {
		unset($arrParams['pg_sig']);
		
		ksort($arrParams);

		array_unshift($arrParams, $strScriptName);
		array_push   ($arrParams, $strSecretKey);

		return join(';', $arrParams);
	}

	/********************** singing XML ***********************/

	/**
	 * make the signature for XML
	 *
	 * @param string|SimpleXMLElement $xml
	 * @param string $strSecretKey
	 * @return string
	 */
	public static function makeXML ( $strScriptName, $xml, $strSecretKey )
	{
		$arrFlatParams = self::makeFlatParamsXML($xml);
		return self::make($strScriptName, $arrFlatParams, $strSecretKey);
	}

	/**
	 * Verifies the signature of XML
	 *
	 * @param string|SimpleXMLElement $xml
	 * @param string $strSecretKey
	 * @return bool
	 */
	public static function checkXML ( $strScriptName, $xml, $strSecretKey )
	{
		if ( ! $xml instanceof SimpleXMLElement ) {
			$xml = new SimpleXMLElement($xml);
		}
		$arrFlatParams = self::makeFlatParamsXML($xml);
		return self::check((string)$xml->pg_sig, $strScriptName, $arrFlatParams, $strSecretKey);
	}

	/**
	 * Returns a string, a hash of which coincide with the result of the makeXML() method.
	 * WARNING: This method can be used only for debugging purposes!
	 *
	 * @param string|SimpleXMLElement $xml
	 * @param string $strSecretKey
	 * @return string
	 */
	public static function debug_only_SigStrXML ( $strScriptName, $xml, $strSecretKey )
	{
		$arrFlatParams = self::makeFlatParamsXML($xml);
		return self::makeSigStr($strScriptName, $arrFlatParams, $strSecretKey);
	}

	/**
	 * Returns flat array of XML params
	 *
	 * @param (string|SimpleXMLElement) $xml
	 * @return array
	 */
	private static function makeFlatParamsXML ( $xml, $parent_name = '' )
	{
		if ( ! $xml instanceof SimpleXMLElement ) {
			$xml = new SimpleXMLElement($xml);
		}

		$arrParams = array();
		$i = 0;
		foreach ( $xml->children() as $tag ) {
			
			$i++;
			if ( 'pg_sig' == $tag->getName() )
				continue;
				
			/**
			 * Имя делаем вида tag001subtag001
			 * Чтобы можно было потом нормально отсортировать и вложенные узлы не запутались при сортировке
			 */
			$name = $parent_name . $tag->getName().sprintf('%03d', $i);

			if ( $tag->children() ) {
				$arrParams = array_merge($arrParams, self::makeFlatParamsXML($tag, $name));
				continue;
			}

			$arrParams += array($name => (string)$tag);
		}

		return $arrParams;
	}
}
?>