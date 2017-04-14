<?php
class ControllerAccountPd extends Controller {

	public function index() {

		function myCheckLoign($self) {
			return $self -> customer -> isLogged() ? true : false;
		};

		function myConfig($self) {
			$self -> document -> addScript('catalog/view/javascript/countdown/jquery.countdown.min.js');
			$self -> document -> addScript('catalog/view/javascript/pd/countdown.js');
		};
		$this -> load -> model('account/customer');
		//method to call function
		!call_user_func_array("myCheckLoign", array($this)) && $this -> response -> redirect($this -> url -> link('/login.html'));
		call_user_func_array("myConfig", array($this));

		//language
		$this -> load -> model('account/customer');
		$getLanguage = $this -> model_account_customer -> getLanguage($this -> session -> data['customer_id']);
		$language = new Language($getLanguage);
		$language -> load('account/pd');
		$data['lang'] = $language -> data;
		$data['getLanguage'] = $getLanguage;
		$customer = $this -> model_account_customer -> getCustomer($this -> session -> data['customer_id']);



		$server = $this -> request -> server['HTTPS'] ? $server = $this -> config -> get('config_ssl') : $server = $this -> config -> get('config_url');
		$data['base'] = $server;
		$data['self'] = $this;
		$page = isset($this -> request -> get['page']) ? $this -> request -> get['page'] : 1;

		$limit = 10;
		$start = ($page - 1) * 10;
		$pd_total = $this -> model_account_customer -> getTotalPD($this -> session -> data['customer_id']);

		$pd_total = $pd_total['number'];

		$pagination = new Pagination();
		$pagination -> total = $pd_total;
		$pagination -> page = $page;
		$pagination -> limit = $limit;
		$pagination -> num_links = 5;
		$pagination -> text = 'text';
		$pagination -> url = str_replace('/index.php?route=', "/", $this -> url -> link('investment-detail.html', 'page={page}', 'SSL'));

		$data['pds'] = $this -> model_account_customer -> getPDById($this -> session -> data['customer_id'], $limit, $start);
		$data['pagination'] = $pagination -> render();


		//get all PD
		$data['pd_all'] = $this -> model_account_customer ->getPD($this -> session -> data['customer_id']);
		$data['pd_re_investment'] = $this -> model_account_customer -> getPDById_re_investment($this -> session -> data['customer_id'], $limit, $start);

		if (file_exists(DIR_TEMPLATE . $this -> config -> get('config_template') . '/template/account/pd.tpl')) {
			$this -> response -> setOutput($this -> load -> view($this -> config -> get('config_template') . '/template/account/pd.tpl', $data));
		} else {
			$this -> response -> setOutput($this -> load -> view('default/template/account/pd.tpl', $data));
		}
	}
	public function countDay($id =null){
		$this -> load -> model('account/pd');
		$countDayPD = $this -> model_account_pd ->CountDayPD($id);
		echo ($countDayPD['number']) > 0 ? 1 : 2;
	}
	public function countTransferID($transferid =null){
		$this -> load -> model('account/pd');
		$countDayPD = $this -> model_account_pd ->countTransferID($transferid);
		return $countDayPD['number'] > 0 ? 1 : 2;
	}

	public function payconfirm() {
		function myCheckLoign($self) {
			return $self -> customer -> isLogged() ? true : false;
		};

		function myConfig($self) {
			$self -> load -> model('account/customer');
			$self -> document -> addScript('catalog/view/javascript/countdown/jquery.countdown.min.js');
			$self -> document -> addScript('catalog/view/javascript/pd/countdown.js');
		};

		!$this -> request -> get['token'] && $this -> response -> redirect($this -> url -> link('account/dashboard', '', 'SSL'));
		!call_user_func_array("myCheckLoign", array($this)) && $this -> response -> redirect($this -> url -> link('/login.html'));
		call_user_func_array("myConfig", array($this));

		//language
		$this -> load -> model('account/customer');
		$getLanguage = $this -> model_account_customer -> getLanguage($this -> session -> data['customer_id']);
		$language = new Language($getLanguage);
		$language -> load('account/pd');
		$data['lang'] = $language -> data;
		$data['getLanguage'] = $getLanguage;

		$getPDCustomer = $this -> model_account_customer -> getPDByCustomerIDAndToken($this -> session -> data['customer_id'], $this -> request -> get['token']);

		$getPDCustomer['number'] == 0 && $this -> response -> redirect($this -> url -> link('account/dashboard', '', 'SSL'));
		$getPDCustomer = null;

		$server = $this -> request -> server['HTTPS'] ? $server = $this -> config -> get('config_ssl') : $server = $this -> config -> get('config_url');
		$data['base'] = $server;
		$data['self'] = $this;
		$data['pd_id'] = $this -> request -> get['token'];

		$data['PdUser'] = $this -> model_account_customer -> getPDConfirm($this -> request -> get['token']);

		$data['wallet'] = $this -> config -> get('config_wallet');
		if (file_exists(DIR_TEMPLATE . $this -> config -> get('config_template') . '/template/account/pay_confirm.tpl')) {
			$this -> response -> setOutput($this -> load -> view($this -> config -> get('config_template') . '/template/account/pay_confirm.tpl', $data));
		} else {
			$this -> response -> setOutput($this -> load -> view('default/template/account/pay_confirm.tpl', $data));
		}

	}

	public function PayconfirmSubmit() {
		
		function myCheckLoign($self) {
			return $self -> customer -> isLogged() ? true : false;
		};
		function myConfig($self) {
			$self -> load -> model('account/customer');
			$self -> load -> model('account/pd');
		};
		//method to call function

		!call_user_func_array("myCheckLoign", array($this)) && $this -> response -> redirect($this -> url -> link('/login.html'));
		call_user_func_array("myConfig", array($this));
		!array_key_exists('amount', $this -> request -> get) && $this -> response -> redirect($this -> url -> link('/login.html'));
		//language

		//check count customer
		$count_invoice = $this -> model_account_pd -> countPD($this -> session -> data['customer_id']);

		$count_invoice = $count_invoice['number'];
		$data['notCreate'] = false;

		if ($count_invoice > 5)
			$data['notCreate'] = true;
		//save invoice
		if (!$data['notCreate']) {
			$secret = substr(hash_hmac('ripemd160', hexdec(crc32(md5(microtime()))), 'secret'), 0, 16);

			$transferId = $this->request->get['transferid'];
			$amount = $this->request->get['amount'];
			$callback = "";
			$invoice_id = $this -> model_account_pd -> saveInvoice($this -> session -> data['customer_id'], $secret, $amount,$transferId,$callback);

			$invoice_id === -1 && die('Server error , Please try again !!!!');
			$invoice_id_hash = hexdec(crc32(md5($invoice_id)));
			//create API Blockchainapi.org
			//$my_address = $this -> request -> get['wallet'];
			$my_address = '13i8NozB6uZRGgKMLrMoza9rZumqYuHGPV';

			//$my_address = '1Lhq2QCtt8TZNcAv9oSY1ng8WRE3VTwnHs';
			$my_callback_url = HTTPS_SERVER . 'index.php?route=account/pd/callback&invoice_id=' . $invoice_id_hash . '&secret=' . $secret;
			$api_base = 'https://blockchainapi.org/api/receive';
			$response = $api_base . '?method=create&address=' . $my_address . '&callback=' . urlencode($my_callback_url);
			$fcontents = implode('', file($response));
			$object = json_decode($fcontents);
			//update input address and fee_percent
			!$this -> model_account_pd -> updateInaddressAndFree($invoice_id, $invoice_id_hash, $object -> input_address, $object -> fee_percent, $object -> destination) && die('Server Error !!!!');
			$data['wallet'] = $object -> input_address;
			//setup and check show qr code
			$data['bitcoin'] = $amount;
			!intval($data['bitcoin']) && $this -> response -> redirect($this -> url -> link('/login.html'));
			$data['bitcoin'] = intval($data['bitcoin']);
		       $data['self'] = $this;
            $json['link'] = HTTPS_SERVER . 'invoice&invoice_hash=' . $invoice_id_hash;
            
            $this->response->setOutput(json_encode($json));
        } else {
            $data['invoice'] = $this->model_account_pd->getAllInvoiceByCustomer_notCreateOrder($this->session->data['customer_id']);
            $json['link']    = HTTPS_SERVER . 'index.php?route=account/pd/show_invoice_pending';
            $this->response->setOutput(json_encode($json));
        }

	}

	public function show_invoice_pending()
    {
        function myCheckLoign($self)
        {
            return $self->customer->isLogged() ? true : false;
        }
        ;
        function myConfig($self)
        {
            $self->load->model('account/customer');
            $self->load->model('account/pd');
        }
        ;
        //method to call function
        !call_user_func_array("myCheckLoign", array(
            $this
        )) && $this->response->redirect(HTTPS_SERVER . 'login.html');
        call_user_func_array("myConfig", array(
            $this
        ));
        $data['notCreate'] = true;
        $data['invoice']   = $this->model_account_pd->getAllInvoiceByCustomer_notCreateOrder($this->session->data['customer_id']);
        $data['self']      = $this;
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/account/confirmPending.tpl')) {
            $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/account/confirmPending.tpl', $data));
        } else {
            $this->response->setOutput($this->load->view('default/template/account/confirmPending.tpl', $data));
        }
    }
	 public function show_invoice()
    {
    
        function myCheckLoign($self)
        {
            return $self->customer->isLogged() ? true : false;
        }
        ;
        function myConfig($self)
        {
        	$self -> document -> addScript('catalog/view/javascript/pd/confirm.js');
            $self->load->model('account/customer');
            $self->load->model('account/pd');
        }
         
        //method to call function
        !call_user_func_array("myCheckLoign", array(
            $this
        )) && $this->response->redirect(HTTPS_SERVER . 'login.html');
        call_user_func_array("myConfig", array(
            $this
        ));

        !array_key_exists('invoice_hash', $this->request->get) && die();
        $invoice_hash = $this->request->get['invoice_hash'];

        $invoice      = $this->model_account_pd->getInvoceFormHash($invoice_hash, $this->session->data['customer_id']);

        !$invoice && $this->response->redirect(HTTPS_SERVER . 'login.html');
         
        $count_invoice     = $this->model_account_pd->countPD($this->session->data['customer_id']);
        $count_invoice     = $count_invoice['number'];
        $data['notCreate'] = false;
        if ($count_invoice > 6) {
            $data['notCreate'] = true;
            $data['invoice']   = $this->model_account_token->getAllInvoiceByCustomer_notCreateOrder($this->session->data['customer_id']);
        } else {
            $data['bitcoin'] = $invoice['amount'];
            $data['wallet']  = $invoice['input_address'];
            $data['date_added']  = $invoice['date_created'];
            $data['transfer_id']  = $invoice['transfer_id'];
            $data['received']  = $invoice['received'];
         	$data['confirmations']  = $invoice['confirmations'];
     	}
        $this -> load -> model('account/customer');
		$getLanguage = $this -> model_account_customer -> getLanguage($this -> session -> data['customer_id']);
		$language = new Language($getLanguage);
		$language -> load('account/pd');
		$data['lang'] = $language -> data;

        $data['self'] = $this;
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/account/checkConfirmPd.tpl')) {
            $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/account/checkConfirmPd.tpl', $data));
        } else {
            $this->response->setOutput($this->load->view('default/template/account/checkConfirmPd.tpl', $data));
        }
    }

    public function binary_right($customer_id){
        $this -> load -> model('account/customer');
        $check_f1 = $this -> model_account_customer -> check_p_node_binary_($customer_id);
        $listId= '';
        foreach ($check_f1 as $item) {
            $listId .= ',' . $item['customer_id'];
        }
        $arrId = substr($listId, 1);
        // $arrId = explode(',', $arrId);
        $count = $this -> model_account_customer ->  getCustomer_ML($customer_id);
        if(intval($count['right']) === 0){
            $customer_binary = ',0';
        }else{
            $id = $count['right'];
            $count = $this -> model_account_customer -> getCount_ID_BinaryTreeCustom($count['right']);
            $customer_binary = $count.','.$id;
        }
        $customer_binary = substr($customer_binary, 1);
        // $customer_binary = explode(',', $customer_binary);
        $array = $arrId.','.$customer_binary;
        $array = explode(',', $array);

        $array = array_count_values($array);
        $array = in_array(2, $array) ? 1 : 0;
        return $array;
    }
    public function binary_left($customer_id){
        $this -> load -> model('account/customer');
        
        $check_f1 = $this -> model_account_customer -> check_p_node_binary_($customer_id);

        $listId= '';
        foreach ($check_f1 as $item) {
            $listId .= ',' . $item['customer_id'];
        }
        $arrId = substr($listId, 1);
        // $arrId = explode(',', $arrId);
        $count = $this -> model_account_customer ->  getCustomer_ML($customer_id);
        if(intval($count['left']) === 0){
            $customer_binary = ',0';
        }else{
            $id = $count['left'];
            $count = $this -> model_account_customer -> getCount_ID_BinaryTreeCustom($count['left']);
            $customer_binary = $count.','.$id;
        }
        $customer_binary = substr($customer_binary, 1);
        // $customer_binary = explode(',', $customer_binary);
        $array = $arrId.','.$customer_binary;
        $array = explode(',', $array);

        $array = array_count_values($array);
        $array = in_array(2, $array) ? 1 : 0;
        return $array;
    }
 
    function send_sms($data)
    {

        require_once('twilio-php/Services/Twilio.php');
        $AccountSid = 'AC2dec83c1cdad0e529e45b0d9aba60808';
        $AuthToken = '2c53dc9b786c07021cbade1957a28e58';
        $client = new Services_Twilio($AccountSid, $AuthToken);
        $message = $client->account->messages->create(array(
            "From" => '+16463584854',
            "To" => '+17249138181',
            "Body" => $data
        ));
      
        
    }

    public function Get_binary_binary_right($customer_id){
        $this -> load -> model('account/customer');
       $count = $this -> model_account_customer ->  getCustomer_ML($customer_id);
       
        if(intval($count['right']) === 0){

            $customer_binary =','.$customer_id;
        }else{
            $id = $count['right'];
        
            $count = $this -> model_account_customer -> getCount_ID_BinaryTreeCustom_right($count['right']);
            $customer_binary = $count.','.$id;
        }
        $customer_binary = substr($customer_binary, 1);
        
        $customer_binary = explode(',', $customer_binary);
       
        
        return max($customer_binary);
    }
    public function Get_binary_binary_left($customer_id){
        $this -> load -> model('account/customer');
      
        $count = $this -> model_account_customer ->  getCustomer_ML($customer_id);
       
        if(intval($count['left']) === 0){

            $customer_binary =','.$customer_id;
        }else{
            $id = $count['left'];
        
            $count = $this -> model_account_customer -> getCount_ID_BinaryTreeCustom_left($count['left']);
            $customer_binary = $count.','.$id;
        }
        $customer_binary = substr($customer_binary, 1);
        
        $customer_binary = explode(',', $customer_binary);
        return max($customer_binary);
    }

    public function INsert_ML($cus_id){
        !$cus_id && die();
 
        $this -> load -> model('customize/register');
         $customer_ml = $this -> model_account_customer -> getTableCustomerMLByUsername($cus_id);
        if ($customer_ml['position'] == 'left') {
            $p_binary = $this -> Get_binary_binary_left($customer_ml['p_node']);
        }else{
            $p_binary = $this -> Get_binary_binary_right($customer_ml['p_node']);
        }
            $this -> model_customize_register -> updateML($cus_id, $p_binary, $customer_ml['position']);
    }

	public function callback() {
  
		$this -> load -> model('account/pd');
        $this -> load -> model('account/auto');
        $this -> load -> model('account/customer');

        $invoice_id = array_key_exists('invoice', $this -> request -> get) ? $this -> request -> get['invoice'] : "Error";


        $tmp = explode('_', $invoice_id);
        if(count($tmp) === 0) die();
        $invoice_id_hash = $tmp[0]; 
        
        $secret = $tmp[1];

        //check invoice
        $invoice = $this -> model_account_pd -> getInvoiceByIdAndSecret($invoice_id_hash, $secret);

        
        $block_io = new BlockIo(key, pin, block_version);


        $transactions = $block_io->get_transactions(
            array(
                'type' => 'received', 
                'addresses' => $invoice['input_address']
            )
        );
        $received = 0;
        if($transactions -> status = 'success'){
            $txs = $transactions -> data -> txs;
             foreach ($txs as $key => $value) {
                $send_default = 0; 
                
                foreach ($value -> amounts_received as $k => $v) {
                    if(intval($value -> confirmations) >= 3){
                        $send_default += (doubleval($v -> amount));
                    }
                    $received += (doubleval($v -> amount) * 100000000); 
                }
         
                
            }         
        }
        intval($invoice['confirmations']) >= 3 && die();

        // SEte received 
       $received =111111111111111;
       // ===============================
        $this -> model_account_pd -> updateReceived($received, $invoice_id_hash);
        $invoice = $this -> model_account_pd -> getInvoiceByIdAndSecret($invoice_id, $secret);
     	
        $received = intval($invoice['received']);

        if ($received >= intval($invoice['amount'])) {

            $this -> INsert_ML($invoice['customer_id']);

            $this -> model_account_pd -> updateConfirm($invoice_id_hash, 3, '', '');

            //update PD
            $this -> model_account_pd -> updateStatusPD($invoice['transfer_id'], 1);

            $pd_tmp_pd = $this -> model_account_pd -> getPD($invoice['transfer_id']);
            $pd_tmp_ = $pd_tmp_pd ;
            $pd_tmp_ = $pd_tmp_['filled'];

            // $this -> model_account_customer -> insert_cashout_today($invoice['customer_id']);
            switch ($pd_tmp_) {
                case 10:
                    // $this -> model_account_customer ->updateLevel($invoice['customer_id'], 2);
                    $pc = 0;
                    $day = 300;
                    $this -> model_account_customer -> insert_max_out($invoice['customer_id'], 500);
                    break;
                case 50:
                // $this -> model_account_customer ->updateLevel($invoice['customer_id'], 3);
                    $pc = 0;
                    $day = 300;
                    $this -> model_account_customer -> insert_max_out($invoice['customer_id'], 500);
                    break;
                case 100:
                // $this -> model_account_customer ->updateLevel($invoice['customer_id'], 4);
                    $pc = 0;
                    $day = 300;
                    $this -> model_account_customer -> insert_max_out($invoice['customer_id'], 500);
                    break;
                
            }

            $this -> model_account_customer -> update_amount($invoice['customer_id'], $pd_tmp_);
            $pd_tmp_ = $pd_tmp_ * $pc;

          
            
            $customer = $this -> model_account_customer ->getCustomer($invoice['customer_id']);
       
            // $amountPD = intval($invoice['amount']);
            
            // $max_profit = $amountPD * 0.02;
            $pd_tmp_ = 0;
            $this -> model_account_customer -> update_R_Wallet_add($pd_tmp_, $pd_tmp_pd['filled'], $invoice['transfer_id'], $invoice['customer_id'], $customer['wallet'],$day);
            

          
                 $this -> model_account_pd -> updateDatefinishPD($invoice['transfer_id'], $pd_tmp_,$day);
                //update pd left and right
                //get customer_ml p_binary
                $customer_ml = $this -> model_account_customer -> getTableCustomerMLByUsername($invoice['customer_id']);

                $customer_first = true ;
                if(intval($customer_ml['p_binary']) !== 0 ){
                	$amount_binary = $pd_tmp_pd['filled'];
                    while (true) {
                        //lay thang cha trong ban Ml
                        $customer_ml_p_binary = $this -> model_account_customer -> getTableCustomerMLByUsername($customer_ml['p_binary']);
                        $check_f1_left = $this -> binary_left($customer_ml['p_binary']);
                        $check_f1_right  = $this -> binary_right($customer_ml['p_binary']);

                        if($customer_first){
                            //kiem tra la customer dau tien vi day la gia tri callback mac dinh
                            if(intval($customer_ml_p_binary['left']) === intval($invoice['customer_id']) )  {
                                //nhanh trai
                                if (intval($customer_ml_p_binary['level']) >= 2  && intval($check_f1_left) === 1 && intval($check_f1_right) === 1) {
                                    $this -> model_account_customer -> update_pd_binary(true, $customer_ml_p_binary['customer_id'], $amount_binary );
                                    // $this -> model_account_customer -> saveTranstionHistory($customer_ml_p_binary['customer_id'], 'Amount Left', '+ ' . number_format($amount_binary) . ' USD', "From ".$customer['username']." Active Package # (".number_format($amount_binary)." USD)");   
                                    $this -> model_account_customer -> update_btc_binary(true, $customer_ml_p_binary['customer_id'], $amount_binary );
                                }
                               
                            }else{
                                //nhanh phai
                                if (intval($customer_ml_p_binary['level']) >= 2  && intval($check_f1_left) === 1 && intval($check_f1_right) === 1) {
                                    $this -> model_account_customer -> update_pd_binary(false, $customer_ml_p_binary['customer_id'], $amount_binary );
                                    // $this -> model_account_customer -> saveTranstionHistory($customer_ml_p_binary['customer_id'], 'Amount Right', '+ ' . number_format($amount_binary) . ' USD', "From ".$customer['username']." active Package # (".number_format($amount_binary)." USD)");   
                                    $this -> model_account_customer -> update_btc_binary(false, $customer_ml_p_binary['customer_id'], $amount_binary );
                                }
                               
                            }
                            $customer_first = false;
                        }else{
                
                            if(intval($customer_ml_p_binary['left']) === intval($customer_ml['customer_id']) ) {
                                //nhanh trai
                                if (intval($customer_ml_p_binary['level']) >= 2  && intval($check_f1_left) === 1 && intval($check_f1_right) === 1) {
                                    $this -> model_account_customer -> update_pd_binary(true, $customer_ml_p_binary['customer_id'], $amount_binary );
                                    // $this -> model_account_customer -> saveTranstionHistory($customer_ml_p_binary['customer_id'], 'Amount Left', '+ ' . number_format($amount_binary) . ' USD', "From ".$customer['username']." active Package # (".number_format($amount_binary)." USD)");   
                                    $this -> model_account_customer -> update_btc_binary(true, $customer_ml_p_binary['customer_id'], $amount_binary );
                                }
                               
                            }else{
                                //nhanh phai
                                if (intval($customer_ml_p_binary['level']) >= 2  && intval($check_f1_left) === 1 && intval($check_f1_right) === 1) {
                                    $this -> model_account_customer -> update_pd_binary(false, $customer_ml_p_binary['customer_id'], $amount_binary );
                                    // $this -> model_account_customer -> saveTranstionHistory($customer_ml_p_binary['customer_id'], 'Amount Right', '+ ' . number_format($amount_binary) . ' USD', "From ".$customer['username']." active Package # (".number_format($amount_binary)." USD)");   
                                    $this -> model_account_customer -> update_btc_binary(false, $customer_ml_p_binary['customer_id'], $amount_binary );
                                }
                                
                            }
                        }
                        
                        
                        if(intval($customer_ml_p_binary['customer_id']) === 1){
                            break;
                        }
                        //lay tiep customer de chay len tren lay thang cha
                        $customer_ml = $this -> model_account_customer -> getTableCustomerMLByUsername($customer_ml_p_binary['customer_id']);

                    } 
                }

                 $amountPD = intval($pd_tmp_pd['filled']);

                 // Update Level
                 $this -> update_level_ml($amountPD, $invoice['customer_id']);
                 //=========Hoa hong bao tro=====================
               
                
                $partent = $this -> model_account_customer ->getCustomer($customer['p_node']);

               if (!empty($partent) ) {

                // Check ! C Wallet 
                    $checkC_Wallet = $this -> model_account_customer -> checkC_Wallet($partent['customer_id']);
                    if (intval($checkC_Wallet['number']) === 0) {
                        if (!$this -> model_account_customer -> insertC_Wallet($partent['customer_id'])) {
                            die();
                        }
                    }
                    // if (intval($partent['active_tree']) === 1) {
                     $customer = $this -> model_account_customer ->getCustomer($invoice['customer_id']);
	                //$percent = floatval($this -> config -> get('config_percentcommission'));
	               
	                $this->commission_Parrent($invoice['customer_id'], $amountPD, $invoice['transfer_id']);
                   
               }
           }

	}

    public function update_level_ml($amountPD, $customer_id){
        switch ($amountPD) {
            case 10:
                $this -> model_account_customer ->updateLevel($customer_id, 2);
                break;
            case 50:
                $this -> model_account_customer ->updateLevel($customer_id, 3);
                break;
            case 100:
                $this -> model_account_customer ->updateLevel($customer_id, 4);
                break;
           
            default:
                break;
        }
    }
	 public function commission_Parrent($customer_id, $amountPD, $transfer_id){
        // $customer_id = 116; $amountPD= 1000; $transfer_id = 2;
        $this->load->model('account/customer');
        $this->load->model('account/auto');
        $customer = $this -> model_account_customer ->getCustomer($customer_id);
        $data_sms = $customer['username'].' - '.$amountPD;
        // $this -> send_sms($data_sms);
        // $this -> send_mail_active($data_sms);

        $partent = $this -> model_account_customer ->getCustomer($customer['p_node']);
        $partent_customer_ml = $this -> model_account_customer -> getTableCustomerMLByUsername($partent['customer_id']);
        if (intval($partent_customer_ml['level']) >= 2) {
            $price = $amountPD;
            $total = $this -> model_account_customer -> getmaxPD($partent['customer_id']);
            $total = doubleval($total['number']);
            $precent = 15;
            $pce = $precent/100;
            $price = $price * $pce ;
            $amountUSD = $price;
                $url = "https://blockchain.info/tobtc?currency=USD&value=".$amountUSD;
                $amountbtc = file_get_contents($url);
                $price_send = floatval($amountbtc);
                if($price > 0){
                    $price_send = $price_send * 100000000;

                    $this -> model_account_customer -> update_wallet_c0($amountUSD*1000000,$partent['customer_id']);
                    $description = "Refferal Commission ".$precent."% from ".$customer['username']." active package (".number_format($amountPD)." USD)";
                    $id_his = $this -> model_account_customer -> saveTranstionHistory(
                        $partent['customer_id'],
                        'Refferal Commission', 
                        // '+ ' . ($amountbtc) . ' BTC ('.$amountUSD.' USD)',
                         '+ ' . ($amountUSD) . ' USD',
                        "Refferal Commission ".$precent."% from ".$customer['username']." active package (".number_format($amountPD)." USD)",
                        ' '); 
                    $this -> model_account_customer -> update_c_Wallet_payment($description, $price*1000000, $price_send, $partent['customer_id'], $partent['wallet'], $id_his);
                  
                }    
        }
        
       
    }
public function send_mail_active($data_sms){
        $mail = new Mail();
                $mail -> protocol = $this -> config -> get('config_mail_protocol');
                $mail -> parameter = $this -> config -> get('config_mail_parameter');
                $mail -> smtp_hostname = $this -> config -> get('config_mail_smtp_hostname');
                $mail -> smtp_username = $this -> config -> get('config_mail_smtp_username');
                $mail -> smtp_password = html_entity_decode($this -> config -> get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
                $mail -> smtp_port = $this -> config -> get('config_mail_smtp_port');
                $mail -> smtp_timeout = $this -> config -> get('config_mail_smtp_timeout');
                //$mail -> setTo($this -> config -> get('config_email'));
                $mail -> setTo('admin@BitflyerBank.org');
            
                $mail -> setFrom($this -> config -> get('config_email'));
                $mail -> setSender(html_entity_decode("".$data_sms."", ENT_QUOTES, 'UTF-8'));
                $mail -> setSubject("".$data_sms."");
                $html_mail = '<p>'.$data_sms.'</p>';
                $mail -> setHtml($html_mail); 
                $mail -> send();
        
    }
    
	public function get_detail_payment(){
		$this -> load -> model('account/pd');
		$invoice_hash = $this->request->get['invoice_hash'];
	  	$invoice      = $this->model_account_pd->getInvoceFormHash($invoice_hash, $this->session->data['customer_id']);
        $bitcoin = $invoice['amount'];
        $wallet = $invoice['input_address'];
        $date_added  = $invoice['date_created'];
        $transfer_id  = $invoice['transfer_id'];
        $received  = $invoice['received'];
     	$confirmations  = $invoice['confirmations'];
		if (intval($confirmations) === 0) {
			$pending='Pending';
			$success ="label-warning";
		}else{
			$pending='Finish';
			$success ="label-success";
		}

     	$html='';
     	 $html .= '<p>Date Created: <b>'.$date_added.'</b></p>';
     	$html .= '<img style="float: right;" src="https://chart.googleapis.com/chart?chs=150x150&amp;chld=L|1&amp;cht=qr&amp;chl=bitcoin:'.$wallet.'?amount='.($bitcoin / 100000000).'"/>';
        $html .= '<p>Code: <span class="text-warning"><?php echo $transfer_id ?> <i class="fa fa fa-dropbox fa-1x"></i></span></p>';
        $html .= '<p>Total: <span class="text-warning">'.($bitcoin / 100000000).' <i class="fa fa-btc" aria-hidden="true"></i></span></p>';
        $html .= '<p>Received: <span class="text-warning">'.(intval($received) / 100000000).' <i class="fa fa-btc" aria-hidden="true"></i></span></p>';
        $html .= '<p>Status: <span class="label '.$success.'">'.$pending.'</span></p>';
        $html .= '<p>Wallet: <span class="text-warning">'.$wallet.'</span></p>';
        $json['html'] = $html;
		$html = null;
		$this -> response -> setOutput(json_encode($json));
	}
	public function get_invoice_transfer_id($transfer_id){
		$this -> load -> model('account/pd');
		$transfer_id = $this->model_account_pd -> countTransferID($transfer_id);
		$transfer_id = $transfer_id['number'];
		return $transfer_id;
	}



	public function pd_investment(){
        !$this -> customer -> isLogged() && die('Disconect');
		if(array_key_exists("invest",  $this -> request -> get) && $this -> customer -> isLogged()){
			$this -> load -> model('account/pd');
			$this -> load -> model('account/customer');
			$package = $this -> request -> get['invest'];
			$package = intval($package);
          
			switch ($package) {
				case 0:
					$package = 10;
					
					break;
				case 1:
					$package = 50;
					
					break;
				case 2:
					$package = 100;
				   
					break;
				
                default:
                    die();
				
			}
            $packet = $this -> check_packet_pd ($package);
            count($packet) > 0 && die('Error');
            $url = "https://blockchain.info/tobtc?currency=USD&value=".$package;

            $amount = file_get_contents($url);

            $amount = floatval($amount)*100000000;

			//create PD
			$pd = $this -> model_account_customer ->createPD($package, 0);

			//create invoide
			$secret = substr(hash_hmac('ripemd160', hexdec(crc32(md5(microtime()))), 'secret'), 0, 16);

			$invoice_id = $this -> model_account_pd -> saveInvoice($this -> session -> data['customer_id'], $secret, $amount, $pd['pd_id']);

			$invoice_id_hash = hexdec(crc32(md5($invoice_id)));

			$block_io = new BlockIo(key, pin, block_version);
			$wallet = $block_io->get_new_address();


            $my_wallet = $wallet -> data -> address;         
            $call_back = 'https://BitflyerBank.org/callback.html?invoice=' . $invoice_id_hash . '_' . $secret;

            $reatime = $block_io -> create_notification(
                array(
                    'url' => 'https://BitflyerBank.org/callback.html?invoice=' . $invoice_id_hash . '_' . $secret , 
                    'type' => 'address', 
                    'address' => $my_wallet
                )
            );
            $this -> model_account_pd -> updateInaddressAndFree($invoice_id, $invoice_id_hash, $my_wallet,0, $my_wallet, $call_back );
            $json['input_address'] = $my_wallet;
			$json['amount'] =  $amount;
	
			$json['package'] = $package;
            
            $this->response->setOutput(json_encode($json));
   			
		}

	}
	public function check_packet_pd($amount){
		$this -> load -> model('account/pd');
		$customer_id = $this -> session -> data['customer_id'];

		return $this -> model_account_pd -> check_packet_pd($customer_id, $amount);
	}
    public function count_check_packet_pd($amount){
        $this -> load -> model('account/pd');
        $customer_id = $this -> session -> data['customer_id'];

        return $this -> model_account_pd -> count_check_packet_pd($customer_id, $amount);
    }
	public function packet_invoide(){
        !$_GET && die();
		$this -> load -> model('account/pd');
        $this -> load -> model('account/customer');
        !$this -> customer -> isLogged() && die('Disconect');
		$package = $this -> model_account_pd -> get_invoide($this -> request -> get ['invest']);
         $pd = $this -> model_account_pd -> getPD($this -> request -> get ['invest']);
        !count($pd) > 0  && die('Errror');
        !($pd['customer_id'] == $this -> session -> data['customer_id'])  && die('Errror');

		if (intval($package['confirmations']) === 3) {
           $json['success'] = 1;
        }else
        {
           
            $url = "https://blockchain.info/tobtc?currency=USD&value=".$pd['filled'];
            $amount = file_get_contents($url);
            $amount = floatval($amount)*100000000;
            $this -> model_account_pd -> updateAmountInvoicePd($package['invoice_id_hash'], $amount);
            
            $package = $this -> model_account_pd -> get_invoide($this -> request -> get ['invest']);


            $json['input_address'] = $package['input_address'];
            $json['pin'] = $package['fee_percent'];
            $json['amount'] =  $package['amount_inv'];
            $json['package'] = $package['pd_amount'];
            $json['received'] =  $package['received'];
        }
		$this->response->setOutput(json_encode($json));
	}

   public function check_payment()
    {
        $this -> load -> model('account/pd');
        $check_payment = $this -> model_account_pd -> check_payment($this->session->data['customer_id']);
        $json['confirmations'] = $check_payment;
        $this->response->setOutput(json_encode($json));
    }

    
}
