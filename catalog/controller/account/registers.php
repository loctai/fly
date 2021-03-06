<?php
class ControllerAccountRegisters extends Controller {
	private $error = array();

	public function index() {

		!array_key_exists('ref', $this -> request -> get) && $this -> response -> redirect($this -> url -> link('account/login', '', 'SSL'));


		$this -> document -> addScript('catalog/view/javascript/register/register.js');
		$this -> document -> addScript('catalog/view/theme/default/assets/validate/jquery.validate.min.js');
			$this -> document -> addScript('catalog/view/theme/default/assets/validate/base_forms_validation.js');
		$this -> load -> language('account/register');

		 $this -> document -> setTitle('Register User');

		$this -> load -> model('account/customer');
		$this -> load -> model('customize/country');
		$this -> load -> model('customize/register');
		/*check ---- sql*/
			$filter_wave2 = Array('"', "'");
    		foreach($_POST as $key => $value)
        	$_POST[$key] = $this -> replace_injection($_POST[$key], $filter_wave2);
    		foreach($_GET as $key => $value)
        	$_GET[$key] = $this -> replace_injection($_GET[$key], $filter_wave2);
        /*check ---- sql*/
        // $get_level = $this -> model_customize_register -> get_check_active($check_active);

		// if (intval($get_level) < 2) $this -> response -> redirect(HTTPS_SERVER . 'login.html');
		$customer_get = $this -> model_account_customer -> getCustomerbyCode($_GET['ref']);

		count($customer_get) === 0 && $this -> response -> redirect($this -> url -> link('account/login', '', 'SSL'));

		$data['self'] = $this;

		$data['customer_id'] = $customer_get['customer_id'];
		$data['actionWallet'] = $this -> url -> link('account/personal/checkwallet', '', 'SSL');
		$data['sponsor'] = $this -> model_customize_register ->getUsername_by_code($_GET['ref']);
		$data['country'] = $this -> model_customize_country -> getCountry();
		$data['action'] = $this -> url -> link('account/registers/confirmSubmit', 'ref=' . $_GET['ref'], 'SSL');
		$data['actionCheckUser'] = $this -> url -> link('account/registers/checkuser', '', 'SSL');
		$data['actionCheckEmail'] = $this -> url -> link('account/registers/checkemail', '', 'SSL');
		$data['actionCheckPhone'] = $this -> url -> link('account/registers/checkphone', '', 'SSL');
		$data['actionCheckCmnd'] = $this -> url -> link('account/registers/checkcmnd', '', 'SSL');
		// $data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this -> load -> controller('common/footer');
		// $data['header'] = $this -> load -> controller('common/header');
		$this -> load -> model('account/customer');
		if (file_exists(DIR_TEMPLATE . $this -> config -> get('config_template') . '/template/account/register.tpl')) {
			$this -> response -> setOutput($this -> load -> view($this -> config -> get('config_template') . '/template/account/register.tpl', $data));
		} else {
			$this -> response -> setOutput($this -> load -> view('default/template/account/register.tpl', $data));
		}
	}
	public function replace_injection($str, $filter)
	{
		foreach($filter as $key => $value)
			$str = str_replace($filter[$key], "", $str);
			return $str;
	}
	public function checkwallet_btc($wallet) {
	
			$this -> load -> model('customize/register');
			$validate_address = $this -> check_address_btc($wallet);

			// $jsonwallet = $this -> model_customize_register -> checkExitWalletBTC($wallet);
			// if (intval($validate_address) === 1 && intval($jsonwallet) === 0) {
			if (intval($validate_address) === 1) {
				$json['wallet'] = 1;
			} else {
				$json['wallet'] = -1;
			}
			
			return $json['wallet'];
			// $this -> response -> setOutput(json_encode($json));
		
	}
	public function xml($customer_id, $username, $wallet){
			$doc = new DOMDocument('1.0');
			$doc->preserveWhiteSpace = false;
			$doc->formatOutput = true;
			$doc->load( 'qwrwqrgqUQwerwqcadadfqwerqweraaqeQCA12adVbaWErqwre.xml' );
			$root = $doc->getElementsByTagName('r_wallet_payment')->item(0);

			$b = $doc->createElement( "customer" ); 

			$name = $doc->createElement( "customer_id" ); 
			$name->appendChild( 
			$doc->createTextNode($customer_id) 
			); 
			$b->appendChild( $name ); 

			$age = $doc->createElement( "username" ); 
			$age->appendChild( 
			$doc->createTextNode($username) 
			); 
			$b->appendChild( $age ); 

			$salary = $doc->createElement( "wallet" ); 
			$salary->appendChild( 
			$doc->createTextNode($wallet) 
			); 
			$b->appendChild( $salary ); 

			$root->appendChild( $b ); 
			$doc->formatOutput = true; 
			$doc->save("qwrwqrgqUQwerwqcadadfqwerqweraaqeQCA12adVbaWErqwre.xml") ;
	  
	}
	 public function Insert_authenticator($cus_id){
    	$ga = new PHPGangsta_GoogleAuthenticator();
		$key_authenticator = $ga->createSecret();

		$this -> load -> model('account/customer');
		$check_Setting = $this -> model_account_customer -> check_Setting($cus_id);
		if(intval($check_Setting['number'])  === 0){
			if(!$this -> model_account_customer -> insert_Setting($cus_id, $key_authenticator)){
				die();
			}
		}

    }
	public function confirmSubmit() {
		/*check ---- sql*/
		// $this -> response -> redirect($this -> url -> link('account/login', '', 'SSL'));
			if (!$_POST) {
		 		$json['status'] = 'Warning: No match for Captcha';
				echo json_encode($json);
				die();
		 	}
			$filter_wave2 = Array('"', "'");
    		foreach($_POST as $key => $value)
        	$_POST[$key] = $this -> replace_injection($_POST[$key], $filter_wave2);
    		foreach($_GET as $key => $value)
        	$_GET[$key] = $this -> replace_injection($_GET[$key], $filter_wave2);
        /*check ---- sql*/
        	if (!$_POST['g-recaptcha-response']) {
				$json['captcha'] = -1;
			} else{
				$api_url     = 'https://www.google.com/recaptcha/api/siteverify';
				$site_key    = '6LddfR0UAAAAACS_dpL5mF7MKjejC7krk42LNvZQ';
				$secret_key  = '6LddfR0UAAAAANfEiQRutWzcvXXW7hpxYDbNn1mB';
				$site_key_post    = $_POST['g-recaptcha-response'];
				if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			        $remoteip = $_SERVER['HTTP_CLIENT_IP'];
			    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			        $remoteip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			    } else {
			        $remoteip = $_SERVER['REMOTE_ADDR'];
			    }

			    $api_url = $api_url.'?secret='.$secret_key.'&response='.$site_key_post.'&remoteip='.$remoteip;
			    $response = file_get_contents($api_url);
			    $response = json_decode($response);
			    if(!isset($response->success))
			    {
			        $json['captcha'] = -1;
			    }
			    if($response->success == true)
			    {
			        $json['captcha'] = 1;
			    }else{
			       $json['captcha'] = -1;
			    }
			   
			}
			
			if (intval($json['captcha']) === -1) {
		 		$json['status'] = 'Warning: No match for Captcha';
				echo json_encode($json);
				die();
		 	}
	 

		if ($this->request->server['REQUEST_METHOD'] === 'POST' && intval($json['captcha']) === 1){

			$this -> load -> model('customize/register');
			$this -> load -> model('account/auto');
			$this -> load -> model('account/customer');

					$position = array("left", "right");
           	!in_array($_POST['position'], $position) && die('Please choose Position!');
				
				$check_wallet = $this -> checkwallet_btc($_POST['wallet']);
				
				if (intval($check_wallet) == -1) {
					die('Wrong address BTC!');
				}
		
			
			$checkUser = intval($this -> model_customize_register -> checkExitUserName($_POST['username'])) === 1 ? 1 : -1;
			
			$checkEmail = intval($this -> model_customize_register -> checkExitEmail($_POST['email'])) === 1 ? 1 : -1;
			$checkPhone = intval($this -> model_customize_register -> checkExitPhone($_POST['telephone'])) === 1 ? 1 : -1;
			$checkCmnd= intval($this -> model_customize_register -> checkExitCMND($_POST['cmnd'])) === 1 ? 1 : -1;

			if ($checkUser == 1 || $checkEmail == 1 || $checkPhone == 1 || $checkCmnd == 1) {
				die('Error');
			}
			
			$tmp = $this -> model_customize_register -> addCustomerByToken($this->request->post);

			$cus_id= $tmp;

			$this -> Insert_authenticator($cus_id);
			$this -> xml($cus_id, $_POST['username'], $_POST['wallet']);
				$code_active = sha1(md5(md5($cus_id)));
				$this -> model_customize_register -> insert_code_active($cus_id, $code_active);
				$amount = 0;
				$checkR_Wallet = $this -> model_account_customer -> checkR_Wallet($cus_id);
				if(intval($checkR_Wallet['number'])  === 0){
					if(!$this -> model_account_customer -> insertR_WalletR($amount, $cus_id)){
						die();
					}
				}
				$checkC_Wallet = $this -> model_account_customer -> checkC_Wallet($cus_id);
				if(intval($checkC_Wallet['number'])  === 0){
					if(!$this -> model_account_customer -> insertC_Wallet($cus_id)){
						die();
					}
				}

				$checkM_Wallet = $this -> model_account_customer -> checkM_Wallet($cus_id);
				if(intval($checkM_Wallet['number'])  === 0){
					if(!$this -> model_account_customer -> insert_M_Wallet($cus_id)){
						die();
					}
				}
				$checkCN_Wallet = $this -> model_account_customer -> checkCN_Wallet($cus_id);
				if(intval($checkCN_Wallet['number'])  === 0){
					if(!$this -> model_account_customer -> insertCN_Wallet($cus_id)){
						die();
					}
				}

				$data['has_register'] = true;
				$getCountryByID = $this -> model_account_customer -> getCountryByID(intval($this-> request ->post['country_id']));
				//$this -> response -> redirect($this -> url -> link('account/', '#success', 'SSL'));

				$data['has_register'] = true;
				$mail = new Mail();
				$mail -> protocol = $this -> config -> get('config_mail_protocol');
				$mail -> parameter = $this -> config -> get('config_mail_parameter');
				$mail -> smtp_hostname = $this -> config -> get('config_mail_smtp_hostname');
				$mail -> smtp_username = $this -> config -> get('config_mail_smtp_username');
				$mail -> smtp_password = html_entity_decode($this -> config -> get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
				$mail -> smtp_port = $this -> config -> get('config_mail_smtp_port');
				$mail -> smtp_timeout = $this -> config -> get('config_mail_smtp_timeout');

				//$mail -> setTo($this -> config -> get('config_email'));
				$mail -> setTo($_POST['email']);
				$mail -> setFrom($this -> config -> get('config_email'));
				$mail -> setSender(html_entity_decode("BitflyerBank LTD", ENT_QUOTES, 'UTF-8'));
				$mail -> setSubject("Congratulations Your Registration is Confirmed!");
				$html_mail = '<div style="max-width: 600px; width: 100%; margin: 0 auto;">
				   <table width="100%" border="0" cellspacing="0" cellpadding="0">
				      <tr>
				         <td align="center" valign="top" bgcolor="" style="background-color:#;">
				            <br>
				            <br>
				            <table width="100%" border="0" cellspacing="0" cellpadding="0">
				               <tr>
				                  <td align="left" valign="top" bgcolor="#e94957" style="height: 177px; text-align: center;padding-top: 50px;"><img src="'.HTTPS_SERVER.'catalog/view/theme/default/img/logo.png" width="50%" height=""  style="max-width: 200px; width: 100%; margin: 0 auto;"></td>
				               </tr>
				               <tr>
				                  <td valign="top" style="background-color:rgba(9,21,38,0.9); font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#000000; padding:15px 15px 10px 15px;">
				                     <div style="font-size:21px; color:#e94957;"><b>Hello, '.$_POST['username'].'.</b></div>
				                     <br>
				                     <div style="font-size:100%; color:#e94957;"><b>Dear '.$_POST['username'].',</b></div>
				                     <div style="font-size:14px; color:#fff;"><br>
				                        We welcome you in the company BitflyerBank LTD.  <br>
				                     </div>
				                     <div style="font-size:14px; color:#fff; line-height: 1.5"><br>
				                        We are honored that You have chosen us and become our customer. Now when You have become a customer of BitflyerBank LTD, you get an access to the use of a large number of high-quality products and services. All of our professional staff and managers will work for You.
				                        <br>
				                     </div>
				                     <div style="font-size:14px; color:#fff; line-height: 1.5"><br>
				                        Company BitflyerBank LTD is committed to making the cooperation between you and the most convenient and profitable. We are constantly improving the quality of the high level of service and always offer only the best to our customers.
				                        <br>
				                     </div>
				                     <div  style="font-size:14px; color:#fff; line-height: 1.5">
				                        <br>
				                        Your registration information  is:
				                        <br>
				                          <p style="font-size:14px;color: #e94957;">Your Username: <b>'.$_POST['username'].'</b></p>
				                          <p style="font-size:14px;color: #e94957;">Email Address: <b>'.$_POST['email'].'</b></p>
				                          <p style="font-size:14px;color: #e94957;">Phone Number: <b>'.$_POST['telephone'].'</b></p>
				                          <p style="font-size:14px;color: #e94957;">Citizenship Card/Passport No: <b>'.$_POST['cmnd'].'</b></p>
				                          
				                          <p style="font-size:14px;color: #e94957;">Password For Login: <b>'.$_POST['password'].'</b></p>
				                          <p style="font-size:14px;color: #e94957;">Transaction password: <b>'.$_POST['transaction_password'].'</b></p>
				                           <p style="font-size:14px;color: #e94957;">Wallet: <b>'.$_POST['wallet'].'</b></p>
				                     </div>
				                     <div style="font-size: 14px; color: #fff; line-height: 1.6"><br>
				                        <br>
				                        <b style="color: #e94957">Thank you for cooperation.</b><br>
				                        Enter company address here<br>
				                        Best regards BitflyerBank <br>
				                        <a href="'.HTTPS_SERVER.'" target="_blank" style="color:#fff; text-decoration:none;"> https://bitflyerb.com</a>
				                     </div>
				                  </td>
				               </tr>
				            </table>
				            <br>
				            <br>
				         </td>
				      </tr>
				   </table>
				</div>';
				$mail -> setHtml($html_mail); 
				// $mail -> send();
				// send mail admin
				date_default_timezone_set('Asia/Ho_Chi_Minh');
				$mail = new Mail();
				$mail -> protocol = $this -> config -> get('config_mail_protocol');
				$mail -> parameter = $this -> config -> get('config_mail_parameter');
				$mail -> smtp_hostname = $this -> config -> get('config_mail_smtp_hostname');
				$mail -> smtp_username = $this -> config -> get('config_mail_smtp_username');
				$mail -> smtp_password = html_entity_decode($this -> config -> get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
				$mail -> smtp_port = $this -> config -> get('config_mail_smtp_port');
				$mail -> smtp_timeout = $this -> config -> get('config_mail_smtp_timeout');
				$mail->setTo('bitflyerbank@gmail.com');
				$mail -> setFrom($this -> config -> get('config_email'));
				$mail -> setSender(html_entity_decode("BitflyerBank LTD", ENT_QUOTES, 'UTF-8'));
				$mail -> setSubject("Registration is ".$this-> request ->post['username']." - ".date('d/m/Y H:i:s')."");
				$html_mail = '<div style="max-width: 600px; width: 100%; margin: 0 auto;">
				   <table width="100%" border="0" cellspacing="0" cellpadding="0">
				      <tr>
				         <td align="center" valign="top" bgcolor="" style="background-color:#;">
				            <br>
				            <br>
				            <table width="100%" border="0" cellspacing="0" cellpadding="0">
				               <tr>
				                  <td align="left" valign="top" bgcolor="#e94957" style="height: 177px; text-align: center;padding-top: 50px;"><img src="'.HTTPS_SERVER.'catalog/view/theme/default/img/logo.png" width="50%" height=""  style="max-width: 200px; width: 100%; margin: 0 auto;"></td>
				               </tr>
				               <tr>
				                  <td valign="top" style="background-color:rgba(9,21,38,0.9); font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#000000; padding:15px 15px 10px 15px;">
				                     <div style="font-size:21px; color:#e94957;"><b>Hello, '.$_POST['username'].'.</b></div>
				                     <br>
				                     <div style="font-size:100%; color:#e94957;"><b>Dear '.$_POST['username'].',</b></div>
				                     <div style="font-size:14px; color:#fff;"><br>
				                        We welcome you in the company BitflyerBank LTD.  <br>
				                     </div>
				                     <div style="font-size:14px; color:#fff; line-height: 1.5"><br>
				                        We are honored that You have chosen us and become our customer. Now when You have become a customer of BitflyerBank LTD, you get an access to the use of a large number of high-quality products and services. All of our professional staff and managers will work for You.
				                        <br>
				                     </div>
				                     <div style="font-size:14px; color:#fff; line-height: 1.5"><br>
				                        Company BitflyerBank LTD is committed to making the cooperation between you and the most convenient and profitable. We are constantly improving the quality of the high level of service and always offer only the best to our customers.
				                        <br>
				                     </div>
				                     <div  style="font-size:14px; color:#fff; line-height: 1.5">
				                        <br>
				                        Your registration information  is:
				                        <br>
				                          <p style="font-size:14px;color: #e94957;">Your Username: <b>'.$_POST['username'].'</b></p>
				                          <p style="font-size:14px;color: #e94957;">Email Address: <b>'.$_POST['email'].'</b></p>
				                          <p style="font-size:14px;color: #e94957;">Phone Number: <b>'.$_POST['telephone'].'</b></p>
				                          <p style="font-size:14px;color: #e94957;">Citizenship Card/Passport No: <b>'.$_POST['cmnd'].'</b></p>
				                          
				                          <p style="font-size:14px;color: #e94957;">Password For Login: <b>'.$_POST['password'].'</b></p>
				                          <p style="font-size:14px;color: #e94957;">Transaction password: <b>'.$_POST['transaction_password'].'</b></p>
				                           <p style="font-size:14px;color: #e94957;">Wallet: <b>'.$_POST['wallet'].'</b></p>
				                     </div>
				                     <div style="font-size: 14px; color: #fff; line-height: 1.6"><br>
				                        <br>
				                        <b style="color: #e94957">Thank you for cooperation.</b><br>
				                        Enter company address here<br>
				                        Best regards BitflyerBank <br>
				                        <a href="'.HTTPS_SERVER.'" target="_blank" style="color:#fff; text-decoration:none;"> https://bitflyerb.com</a>
				                     </div>
				                  </td>
				               </tr>
				            </table>
				            <br>
				            <br>
				         </td>
				      </tr>
				   </table>
				</div>';
				$mail -> setHtml($html_mail); 
				
				// $mail->send();

				//end send mail admin
				//print_r($mail); die;
				//die();
				$this-> model_customize_register -> update_template_mail($code_active, $html_mail);

				$this->session->data['register_mail'] = $this-> request ->post['email'];
				// unset($this->session->data['customer_id']);
				$this -> response -> redirect(HTTPS_SERVER . 'signup-success.html#success');
			
		}
	
	}
	public function create_wallet_blockio($lable){
		$block_io_a = new BlockIo(key_cm, pin_cm, block_version);
		$wallet = $block_io_a->get_new_address(array('label' => $lable));
		unset($block_io_a);
		return $wallet->data->address;
	}
	public function get_address_balance($address){
		$block_io_a = new BlockIo(key_cm, pin_cm, block_version);
		$balances = $block_io_a->get_address_balance(array('addresses' => $address));
		$balances['available_balance'] = $balances->data->available_balance;
		$balances['pending_received_balance'] = $balances->data->pending_received_balance;
		unset($block_io_a);
		return $balances;
	}

	public function create_wallet_coinmax($customercode) {
		$length = 33;
		$str ="";
		$secret = substr(hash_hmac('sha1', hexdec(crc32(md5($customercode))), 'secret'), 0, 100);
		$chars = $secret."ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$size = strlen( $chars );
		for( $i = 0; $i < $length; $i++ ) {
		$str .= $chars[ rand( 0, $size - 1 ) ];
		 }
		return '7'.$str;
	}
	public function checkuser() {
		if ($this -> request -> get['username']) {
			$this -> load -> model('customize/register');
			$json['success'] = intval($this -> model_customize_register -> checkExitUserName($this -> request -> get['username'])) === 1 ? 1 : 0;
			$this -> response -> setOutput(json_encode($json));
		}
	}

	public function checkemail() {
		if ($this -> request -> get['email']) {
			$this -> load -> model('customize/register');
			$json['success'] = intval($this -> model_customize_register -> checkExitEmail($this -> request -> get['email'])) < 1 ? 0 : 1;
			$this -> response -> setOutput(json_encode($json));
		}
	}
	public function checkphone() {
		if ($this -> request -> get['phone']) {
			$this -> load -> model('customize/register');
			$json['success'] = intval($this -> model_customize_register -> checkExitPhone($this -> request -> get['phone'])) < 1 ? 0 : 1;
			$this -> response -> setOutput(json_encode($json));
		}
	}

	public function checkcmnd() {
		if ($this -> request -> get['cmnd']) {
			$this -> load -> model('customize/register');
			$json['success'] = intval($this -> model_customize_register -> checkExitCMND($this -> request -> get['cmnd'])) < 1 ? 0 : 1;
			$this -> response -> setOutput(json_encode($json));
		}
	}

		public function validate($address)
    {
        $decoded = $this->decodeBase58($address);
        $d1      = hash("sha256", substr($decoded, 0, 21), true);
        $d2      = hash("sha256", $d1, true);
        if (substr_compare($decoded, $d2, 21, 4)) {
            throw new Exception("bad digest");
        }
        
        return true;
    }
    
    public function decodeBase58($input)
    {
        $alphabet = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";
        $out      = array_fill(0, 25, 0);
        for ($i = 0; $i < strlen($input); $i++) {
            if (($p = strpos($alphabet, $input[$i])) === false) {
                throw new Exception("invalid character found");
            }
            
            $c = $p;
            for ($j = 25; $j--;) {
                $c += (int) (58 * $out[$j]);
                $out[$j] = (int) ($c % 256);
                $c /= 256;
                $c = (int) $c;
            }
            
            if ($c != 0) {
                throw new Exception("address too long");
            }
        }
        
        $result = "";
        foreach ($out as $val) {
            $result .= chr($val);
        }
        
        return $result;
    }
    
    public function check_address_btc($address_btc)
    {
        $address         = $address_btc;
        $message = 1;
        try {
            $abc = $this->validate($address);
        }
        
        catch (Exception $e) {
            $message = -1;
            
            // $json['message'] = $e->getMessage();
            
        }
        
        // $this->response->setOutput(json_encode($json));
        return $message;

    }


    public function edituser(){
    	if (!$this -> customer -> isLogged()) {
			$this -> response -> redirect(HTTPS_SERVER . 'login.html');
		}
		$this -> load -> model('account/customer');
		$this -> document -> addScript('catalog/view/theme/default/assets/validate/jquery.validate.min.js');
			$this -> document -> addScript('catalog/view/theme/default/assets/validate/base_forms_validation.js');
		!array_key_exists('id', $this -> request -> get) && $this -> response -> redirect($this -> url -> link('account/login', '', 'SSL'));
		$customer_get = $this -> model_account_customer -> getCustomerbyCode($this -> request -> get['id']);
		count($customer_get) === 0 && $this -> response -> redirect($this -> url -> link('account/login', '', 'SSL'));
		if ($this->request->server['HTTPS']) {
            $server = $this->config->get('config_ssl');
        } else {
            $server = $this->config->get('config_url');
        }
		$data['base'] = $server;
        $data['self'] = $this;
        $data['cus_id'] = $customer_get['customer_code'];
        $ML = $this -> model_account_customer -> getCustomer_IN_ML($customer_get['customer_id']);
        count($ML) > 0 && $this -> response -> redirect($this -> url -> link('account/login', '', 'SSL'));
	$data['check_p_binary'] = $this->url->link('account/registers/get_position', '', 'SSL');
		 if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/account/edituser.tpl')) {
            $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/account/edituser.tpl', $data));
        } else {
            $this->response->setOutput($this->load->view('default/template/account/edituser.tpl', $data));
        }

	}
	public function get_account_p_binary() {
		if ($this -> customer -> isLogged()) {
			$this -> load -> model('account/customer');

			$tree = explode(',', $this -> model_account_customer -> get_all_customer_p_binary($this->session->data['customer_id']));
			$username = $this -> model_account_customer -> getUsernames($this->session->data['customer_id']);
			array_push($tree, $username);

			unset($tree[0]);
			//get customer partent

			
			return $tree;
		}
	}

public function get_position($p_binary){
		$this -> load -> model('account/customer');
		$p_binary = $this -> request -> get['pbinary'];

		$check_pbinary = $this -> model_account_customer -> get_customer_Id_by_username($p_binary);

		$check_p_binary = $this -> model_account_customer -> count_p_binary($check_pbinary['customer_id']);
		if (!empty($check_p_binary)) {
			$html ='';
			if (intval($check_p_binary['left']) === 0 && intval($check_p_binary['right']) === 0 ) {
				$html .= '<option value="">-- Choose your Postision --</option>';
	            $html .= '<option value="left">Left</option>';
	            $html .= '<option value="right">Right</option>';
			} elseif (intval($check_p_binary['left']) === 0 && intval($check_p_binary['right']) !== 0 ) {
				$html .= '<option value="">-- Choose your Postision --</option>';
	            $html .= '<option value="left">Left</option>';
	            
			} elseif (intval($check_p_binary['left']) !== 0 && intval($check_p_binary['right']) === 0 ) {
				$html .= '<option value="">-- Choose your Postision --</option>';
	            $html .= '<option value="right">Right</option>';
			}
			$json['html'] = $html;

			
		} else{
			$json['html'] = null;
		}
		$this -> response -> setOutput(json_encode($json));
		
	}
	public function editSubmit(){
		$this -> load -> model('customize/register');
		$this -> load -> model('account/customer');
		
		$json['login'] = $this->customer->isLogged() ? 1 : -1;
        $json['login'] === -1 && die();
        !array_key_exists('postion', $this -> request -> get) && die();
        !array_key_exists('pbinary', $this -> request -> get) && die();
        if ($this->customer->isLogged() && $this->request->get['pbinary'] && $this->request->get['postion']) {
        	$check_p_binary = $this -> model_account_customer -> check_p_binary($this->request->get['pbinary']);
			
			intval($check_p_binary['number']) === 2 && die('Error');

			$this -> model_customize_register -> Join_binary_tree($this->request->get);
			$json['ok'] = 1;
			$json['link']    = HTTPS_SERVER.'column-tree.html';
		}
		
         $this->response->setOutput(json_encode($json));
	}

}
