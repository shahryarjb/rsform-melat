<?php header("Content-type: text/html; charset=UTF-8"); ?>
<?php
/**
 * @package     Joomla - > Site and Administrator payment info
 * @subpackage  com_Rsfrom
 * @subpackage 	trangell_Mellat
 * @copyright   trangell team => https://trangell.com
 * @copyright   Copyright (C) 20016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die('Restricted access');
if (!class_exists ('checkHack')) {
	require_once( JPATH_PLUGINS . '/system/rsfptrangellmellat/trangell_inputcheck.php');
}

class plgSystemRSFPTrangellMellat extends JPlugin {
	var $componentId = 201;
	var $componentValue = 'trangellmellat';
	
	public function __construct( &$subject, $config )
	{
		parent::__construct( $subject, $config );
		$this->newComponents = array(201);
	}
	
	function rsfp_bk_onAfterShowComponents() {
		$lang = JFactory::getLanguage();
		$lang->load('plg_system_rsfptrangellmellat');
		$db = JFactory::getDBO();
		$formId = JRequest::getInt('formId');
		$link = "displayTemplate('" . $this->componentId . "')";
		if ($components = RSFormProHelper::componentExists($formId, $this->componentId))
		   $link = "displayTemplate('" . $this->componentId . "', '" . $components[0] . "')";
?>
        <li class="rsform_navtitle"><?php echo 'درگاه ملت'; ?></li>
		<li><a href="javascript: void(0);" onclick="<?php echo $link; ?>;return false;" id="rsfpc<?php echo $this->componentId; ?>"><span id="TRANGELLMELLAT"><?php echo JText::_('اضافه کردن درگاه ملت'); ?></span></a></li>
		
		
		<?php
		
	}
	
	function rsfp_getPayment(&$items, $formId) {
		if ($components = RSFormProHelper::componentExists($formId, $this->componentId)) {
			$data = RSFormProHelper::getComponentProperties($components[0]);
			$item = new stdClass();
			$item->value = $this->componentValue;
			$item->text = $data['LABEL'];
			// add to array
			$items[] = $item;
		}
	}
	
	function rsfp_doPayment($payValue, $formId, $SubmissionId, $price, $products, $code) {//test
	    $app	= JFactory::getApplication();
		// execute only for our plugin
		if ($payValue != $this->componentValue) return;
		$tax = RSFormProHelper::getConfig('trangellmellat.tax.value');
		if ($tax)
			$nPrice = round($tax,0) + round($price,0) ;
		else 
			$nPrice = round($price,0);

		$dateTime = JFactory::getDate();	
		$fields = array(
			'terminalId' => RSFormProHelper::getConfig('trangellmellat.terminalid'),
			'userName' => RSFormProHelper::getConfig('trangellmellat.username'),
			'userPassword' => RSFormProHelper::getConfig('trangellmellat.userpassword'),
			'orderId' => time(),
			'amount' => $nPrice,
			'localDate' => $dateTime->format('Ymd'),
			'localTime' => $dateTime->format('His'),
			'additionalData' => '',
			'callBackUrl' => JURI::root() . 'index.php?option=com_rsform&formId=' . $formId . '&task=plugin&plugin_task=trangellmellat.notify&code=' . $code,
			'payerId' => 0,
			);
		if ($nPrice > 100) {
			try {
				$soap = new SoapClient('https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl');
				$response = $soap->bpPayRequest($fields);
				
				$response = explode(',', $response->return);
				if ($response[0] != '0') { // if transaction fail
					$msg = $this->getGateMsg($response[0]); 
					$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
					$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 
				}
				else { // if success
					$refId = $response[1];
					echo '
						<form id="paymentForm" method="post" action="https://bpm.shaparak.ir/pgwchannel/startpay.mellat">
							<input type="hidden" name="RefId" value="'.$refId.'" />
						</form>
						<script type="text/javascript">
						document.getElementById("paymentForm").submit();
						</script>'
					;
					exit;
					die;
					
				}
			}
			catch(\SoapFault $e) {
				$msg= $this->getGateMsg('error'); 
				$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
				$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 
			}
		}
		else {
			$msg= $this->getGateMsg('price'); 
			$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
			$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 
		}
	}
	
	function rsfp_bk_onAfterCreateComponentPreview($args = array()) {
		if ($args['ComponentTypeName'] == 'trangellmellat') {
			$args['out'] = '<td>&nbsp;</td>';
			$args['out'].= '<td>'.$args['data']['LABEL'].'</td>';
		}
	}
	
	function rsfp_bk_onAfterShowConfigurationTabs($tabs) {
		$lang = JFactory::getLanguage(); 
		$lang->load('plg_system_rsfptrangellmellat'); 
		$tabs->addTitle('تنظیمات درگاه ملت', 'form-TRANGELMELLAT'); 
		$tabs->addContent($this->trangellmellatConfigurationScreen());
	}
  
	function rsfp_f_onSwitchTasks() {
		if (JRequest::getVar('plugin_task') == 'trangellmellat.notify') {
			$app	= JFactory::getApplication();
			$jinput = $app->input;
			$code 	= $jinput->get->get('code', '', 'STRING');
			$formId = $jinput->get->get('formId', '0', 'INT');
			$db 	= JFactory::getDBO();
			$db->setQuery("SELECT SubmissionId FROM #__rsform_submissions s WHERE s.FormId='".$formId."' AND MD5(CONCAT(s.SubmissionId,s.DateSubmitted)) = '".$db->escape($code)."'");
			$SubmissionId = $db->loadResult();
			//$mobile = $this::getPayerMobile ($formId,$SubmissionId);
			//===================================================================================
			$ResCode = $jinput->post->get('ResCode', '1', 'INT'); 
			$SaleOrderId = $jinput->post->get('SaleOrderId', '1', 'INT'); 
			$SaleReferenceId = $jinput->post->get('SaleReferenceId', '1', 'INT'); 
			$RefId = $jinput->post->get('RefId', 'empty', 'STRING'); 
			if (checkHack::strip($RefId) != $RefId )
				$RefId = "illegal";
			$CardNumber = $jinput->post->get('CardHolderPan', 'empty', 'STRING'); 
			if (checkHack::strip($CardNumber) != $CardNumber )
				$CardNumber = "illegal";
			
			if (
				checkHack::checkNum($ResCode) &&
				checkHack::checkNum($SaleOrderId) &&
				checkHack::checkNum($SaleReferenceId) &&
				checkHack::checkString($code)
			){
				if ($ResCode != '0') {
					$msg= $this->getGateMsg($ResCode); 
					$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
					$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 
				}
				else {
					$fields = array(
					'terminalId' => RSFormProHelper::getConfig('trangellmellat.terminalid'),
					'userName' => RSFormProHelper::getConfig('trangellmellat.username'),
					'userPassword' => RSFormProHelper::getConfig('trangellmellat.userpassword'),
					'orderId' => $SaleOrderId, 
					'saleOrderId' =>  $SaleOrderId, 
					'saleReferenceId' => $SaleReferenceId
					);
					try {
						$soap = new SoapClient('https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl');
						$response = $soap->bpVerifyRequest($fields);

						if ($response->return != '0') {
							$msg= $this->getGateMsg($response->return); 
							$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
							$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 
						}
						else {	
							$response = $soap->bpSettleRequest($fields);
							if ($response->return == '0' || $response->return == '45') {
								if ($SubmissionId) {
										$db->setQuery("UPDATE #__rsform_submission_values sv SET sv.FieldValue=1 WHERE sv.FieldName='_STATUS' AND sv.FormId='".$formId."' AND sv.SubmissionId = '".$SubmissionId."'");
										$db->execute();
										$db->setQuery("UPDATE #__rsform_submission_values sv SET sv.FieldValue='"  . "کد پیگیری  "  . $SaleReferenceId . "    ". $CardNumber . "شماره کارت " . "' WHERE sv.FieldName='transaction' AND sv.FormId='" . $formId . "' AND sv.SubmissionId = '" . $SubmissionId . "'");
										$db->execute();
										$mainframe = JFactory::getApplication();
										$mainframe->triggerEvent('rsfp_afterConfirmPayment', array($SubmissionId));
									}
								$msg= $this->getGateMsg($response->return); 
								$app->enqueueMessage($msg. '<br />'. ' کد پیگیری شما' . $SaleReferenceId  , 'message');	
							}
							else {
								$msg= $this->getGateMsg($response->return); 
								$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
								$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 
							}
						}
					}
					catch(\SoapFault $e)  {
						$msg= $this->getGateMsg('error'); 
						$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
						$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 
					}
				}
			}
			else {
				$msg= $this->getGateMsg('hck2'); 
				$link = JURI::root() . 'index.php?option=com_rsform&formId=' . $formId;
				$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 	
			}
		}
	}
	
	function trangellmellatConfigurationScreen() {
		ob_start();
?>
		<div id="page-trangellmellat" class="com-rsform-css-fix">
			<table  class="admintable">
				<tr>
					<td width="200" style="width: 200px;" align="right" class="key"><label for="api"><?php echo 'شماره ترمینال'; ?></label></td>
					<td><input type="text" name="rsformConfig[trangellmellat.terminalid]" value="<?php echo RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('trangellmellat.terminalid')); ?>" size="100" maxlength="64"></td>
				</tr>
				<tr>
					<td width="200" style="width: 200px;" align="right" class="key"><label for="api"><?php echo 'نام کاربری'; ?></label></td>
					<td><input type="text" name="rsformConfig[trangellmellat.username]" value="<?php echo RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('trangellmellat.username')); ?>" size="100" maxlength="64"></td>
				</tr>
				<tr>
					<td width="200" style="width: 200px;" align="right" class="key"><label for="api"><?php echo 'کلمه عبور'; ?></label></td>
					<td><input type="text" name="rsformConfig[trangellmellat.userpassword]" value="<?php echo RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('trangellmellat.userpassword')); ?>" size="100" maxlength="64"></td>
				</tr>
				<tr>
					<td width="200" style="width: 200px;" align="right" class="key"><label for="tax.value"><?php echo 'مقدار مالیات'; ?></label></td>
					<td><input type="text" name="rsformConfig[trangellmellat.tax.value]" value="<?php echo RSFormProHelper::htmlEscape(RSFormProHelper::getConfig('trangellmellat.tax.value')); ?>" size="4" maxlength="5"></td>
				</tr>
			</table>
		</div>
	
		<?php
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}
	
	function getGateMsg ($msgId) {
		switch($msgId){
			case '0': $out =  'تراکنش با موفقیت انجام شد'; break;
			case '11': $out =  'شماره کارت نامعتبر است'; break;
			case '12': $out =  'موجودی کافی نیست'; break;
			case '13': $out =  'رمز نادرست است'; break;
			case '14': $out =  'تعداد دفعات وارد کردن رمز بیش از حد مجاز است'; break;
			case '15': $out =  'کارت نامعتبر است'; break;
			case '16': $out =  'دفعات برداشت وجه بیش از حد مجاز است'; break;
			case '17': $out =  'کاربر از انجام تراکنش منصرف شده است'; break;
			case '18': $out =  'تاریخ انقضای کارت گذشته است'; break;
			case '19': $out =  'مبلغ برداشت وجه بیش از حد مجاز است'; break;
			case '21': $out =  'پذیرنده نامعتبر است'; break;
			case '23': $out =  'خطای امنیتی رخ داده است'; break;
			case '24': $out =  'اطلاعات کاربری پذیرنده نادرست است'; break;
			case '25': $out =  'مبلغ نامتعبر است'; break;
			case '31': $out =  'پاسخ نامتعبر است'; break;
			case '32': $out =  'فرمت اطلاعات وارد شده صحیح نمی باشد'; break;
			case '33': $out =  'حساب نامعتبر است'; break;
			case '34': $out =  'خطای سیستمی'; break;
			case '35': $out =  'تاریخ نامعتبر است'; break;
			case '41': $out =  'شماره درخواست تکراری است'; break;
			case '42': $out =  'تراکنش Sale‌ یافت نشد'; break;
			case '43': $out =  'قبلا درخواست Verify‌ داده شده است'; break;
			case '44': $out =  'درخواست Verify‌ یافت نشد'; break;
			case '45': $out =  'تراکنش Settle‌ شده است'; break;
			case '46': $out =  'تراکنش Settle‌ نشده است'; break;
			case '47': $out =  'تراکنش  Settle یافت نشد'; break;
			case '48': $out =  'تراکنش Reverse شده است'; break;
			case '49': $out =  'تراکنش Refund یافت نشد'; break;
			case '51': $out =  'تراکنش تکراری است'; break;
			case '54': $out =  'تراکنش مرجع موجود نیست'; break;
			case '55': $out =  'تراکنش نامعتبر است'; break;
			case '61': $out =  'خطا در واریز'; break;
			case '111': $out =  'صادر کننده کارت نامعتبر است'; break;
			case '112': $out =  'خطا سوییج صادر کننده کارت'; break;
			case '113': $out =  'پاسخی از صادر کننده کارت دریافت نشد'; break;
			case '114': $out =  'دارنده کارت مجاز به انجام این تراکنش نیست'; break;
			case '412': $out =  'شناسه قبض نادرست است'; break;
			case '413': $out =  'شناسه پرداخت نادرست است'; break;
			case '414': $out =  'سازمان صادر کننده قبض نادرست است'; break;
			case '415': $out =  'زمان جلسه کاری به پایان رسیده است'; break;
			case '416': $out =  'خطا در ثبت اطلاعات'; break;
			case '417': $out =  'شناسه پرداخت کننده نامعتبر است'; break;
			case '418': $out =  'اشکال در تعریف اطلاعات مشتری'; break;
			case '419': $out =  'تعداد دفعات ورود اطلاعات از حد مجاز گذشته است'; break;
			case '421': $out =  'IP‌ نامعتبر است';  break;
			case	'1':
			case	'error': $out ='خطا غیر منتظره رخ داده است';break;
			case	'hck2': $out = 'لطفا از کاراکترهای مجاز استفاده کنید';break;
			case	'notff': $out = 'سفارش پیدا نشد';break;
			case	'price': $out = 'مبلغ وارد شده کمتر از ۱۰۰۰ ریال می باشد';break;
			default: $out ='خطا غیر منتظره رخ داده است';break;
		}
		return $out;
	}

	function getPayerMobile ($formId,$SubmissionId) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('FieldValue')
			->from($db->qn('#__rsform_submission_values'));
		$query->where(
			$db->qn('FormId') . ' = ' . $db->q($formId) 
							. ' AND ' . 
			$db->qn('SubmissionId') . ' = ' . $db->q($SubmissionId)
							. ' AND ' . 
			$db->qn('FieldName') . ' = ' . $db->q('mobile')
		);
		$db->setQuery((string)$query); 
		$result = $db->loadResult();
		return $result;
	}
}
