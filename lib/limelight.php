<?php
include_once('config.php');
error_reporting(-1);
class LimelightOrder {
    public function new_prospect($data) {
        if (!empty($data)) {
            //make sure data is valid first

            //check email
            if (!filter_var($data['fields_email'], FILTER_VALIDATE_EMAIL)) {
                return array('status'=>0,'message'=>'Invalid Email');
            }

            //check phone
            $justNums = preg_replace("/[^0-9]/", '', $data['fields_phone']);
            if (strlen($justNums) == 11) $justNums = preg_replace("/^1/", '',$justNums);
            if (strlen($justNums) != 10) {
                return array('status'=>0,'message'=>'Invalid Phone');
            }

            //check address
            require_once("easypost.php");
            \EasyPost\EasyPost::setApiKey('z0AWbWczyM7J9xmt00V5oA');
            $address = \EasyPost\Address::create_and_verify(array(
                'name' => $data['fields_fname'].' '.$data['fields_lname'],
                'street1' => $data['fields_address1'],
                'street2' => $data['fields_address2'],
                'city' => $data['fields_city'],
                'state' => $data['fields_state'],
                'zip' => $data['fields_zip'],
                'country' => 'US',
                'email' => $data['fields_email']
            ));

            if ($address) {
                if (isset($address->error) && $address->error == 'Address Not Found.') {
                    return array('status'=>0,'message'=>'Invalid Address');
                } elseif (isset($address->error)) {
                    return array('status'=>0,'message'=>$address->error);
                } elseif (isset($address->message) && $address->message == 'Default address: The address you entered was found but more information is needed (such as an apartment, suite, or box number) to match to a specific address.') {
                    return array('status'=>0,'message'=>'More information needed in address (apartment number, suite, etc.)');
                } else {
                    $data['fields_address1'] = $address->street1;
                    $data['fields_address2'] = $address->street2;
                    $data['fields_city'] = $address->city;
                    $data['fields_state'] = $address->state;
                    $data['fields_zip'] = $address->zip;
                }
            }

            global $username;
            global $password;
            global $campaign_id;
            global $url;

            $send_data = array(
                'method'=>'NewProspect',
                'username'=>$username,
                'password'=>$password,
                'firstName'=>$data['fields_fname'],
                'lastName'=>$data['fields_lname'],
                'address1'=>$data['fields_address1'],
                'address2'=>$data['fields_address2'],
                'city'=>$data['fields_city'],
                'state'=>$data['fields_state'],
                'zip'=>$data['fields_zip'],
                'country'=>$data['shippingCountry'],
                'phone'=>$data['fields_phone'],
                'email'=>$data['fields_email'],
                'ipAddress'=>$this->ip(),
                'campaignId'=>$campaign_id
            );
            if (isset($data['AFID'])) $send_data['AFID'] = $data['AFID'];
            if (isset($data['SID'])) $send_data['SID'] = $data['SID'];
            if (isset($data['AFFID'])) $send_data['AFFID'] = $data['AFFID'];
            if (isset($data['c1'])) $send_data['C1'] = $data['c1'];
            if (isset($data['c2'])) $send_data['C2'] = $data['c2'];
            if (isset($data['c3'])) $send_data['C3'] = $data['c2'];
            if (isset($data['click_id'])) $send_data['click_id'] = $data['click_id'];

            $request_url = $url.'/admin/transact.php';
            parse_str($this->curl($request_url,$send_data), $result);

            if ($result['errorFound']==0) {
                return array('status'=>1,'prospect_id'=>$result['prospectId']);
            } else {
                //record error message
                file_put_contents("errors.log",date('m/d/Y H:i:s').' - Prospect Error - Error code: '.$result['responseCode'].': '.$this->handle_error($result['responseCode'])."\r\n",FILE_APPEND);

                //handle error
                return array('status'=>0,'message'=>$this->handle_error($result['responseCode']));
            }
        } else {
            return array('status'=>0,'message'=>'No data received');
        }
    }

    public function validate_payment($data) {
        if (!empty($data)) {
            if ($data['cc_type']=="") return array('status'=>0,'message'=>'Please select credit card type.');
            if (strlen(preg_replace("/[^0-9]/", '', $data['cc_cvv']))<3) return array('status'=>0,'message'=>'Invalid CVV2.');
            if (strlen(preg_replace("/[^0-9]/", '', $data['cc_number']))<16) return array('status'=>0,'message'=>'Invalid Card Number.');
            return array('status'=>1);
        } else {
            return array('status'=>0,'message'=>'No Data Sent.');
        }
    }

    public function new_order_with_prospect($data) {
        if (!empty($data)) {
            global $username;
            global $password;
            global $product_id;
            global $shipping_id;
            global $initial_upsell_product_id;
            global $url;
            global $accept_prepaid;
            global $second_order;
            global $second_campaign_id;
            global $second_product_id;
            global $second_shipping_id;
            global $second_upsell_product_id;
            global $prepaid_campaign_id;
            global $prepaid_second_campaign_id;
            global $upsell_optional;
            global $allow_custom_products;

            if ($allow_custom_products && isset($data['product_id'])) $product_id = $data['product_id'];
            if ($allow_custom_products && isset($data['second_product_id'])) $second_product_id = $data['second_product_id'];

            $send_data = array(
                'method'=>'NewOrderWithProspect',
                'username'=>$username,
                'password'=>$password,
                'creditCardType'=>$data['cc_type'],
                'creditCardNumber'=>$data['cc_number'],
                'expirationDate'=>str_pad($data['fields_expmonth'].$data['fields_expyear'], 4, '0', STR_PAD_LEFT),
                'CVV'=>$data['cc_cvv'],
                'tranType'=>'Sale',
                'productId'=>$product_id,
                'shippingId'=>$shipping_id,
                'prospectId'=>$data['prospect_id'],
                'upsellCount'=>0,
                'product_name'=>'PreOps'
            );

            if ($initial_upsell_product_id>0) {
                if (!$upsell_optional || (isset($data['add-upsell']) && $data['add-upsell']==1)) {
                    $send_data['upsellCount'] = 1;
                    $send_data['upsellProductIds'] = $initial_upsell_product_id;
                }
            }

            if ($data['same_info']!="on") {
                $send_data = array_merge($send_data,array(
                    'billingFirstName'=>$data['billing_first_name'],
                    'billingLastName'=>$data['billing_last_name'],
                    'billingAddress1'=>$data['billing_address_1'],
                    'billingAddress2'=>$data['billing_address_2'],
                    'billingCity'=>$data['billing_city'],
                    'billingState'=>$data['billing_state'],
                    'billingZip'=>$data['billing_zip'],
                    'billingCountry'=>$data['billing_country'],
                    'billingSameAsShipping'=>'NO'
                ));
            } else {
                $send_data['billingSameAsShipping'] = 'YES';
            }

            if (isset($data['AFID'])) $send_data['AFID'] = $data['AFID'];
            if (isset($data['SID'])) $send_data['SID'] = $data['SID'];
            if (isset($data['AFFID'])) $send_data['AFFID'] = $data['AFFID'];
            if (isset($data['c1'])) $send_data['C1'] = $data['c1'];
            if (isset($data['c2'])) $send_data['C2'] = $data['c2'];
            if (isset($data['c3'])) $send_data['C3'] = $data['c2'];
            if (isset($data['click_id'])) $send_data['click_id'] = $data['click_id'];

            $request_url = $url.'/admin/transact.php';
            parse_str($this->curl($request_url,$send_data), $result);

            if ($prepaid = ($accept_prepaid && $result['errorFound']!=0 && $result['declineReason']=="Prepaid Credit Cards Are Not Accepted")) {
                $send_data['campaignId'] = $prepaid_campaign_id;
                parse_str($this->curl($request_url,$send_data), $result);
            }

            if ($result['errorFound']==0) {
                $order_id = $result['orderId'];

                //try second transaction
                if ($second_order && ($second_product_id>0 || $second_upsell_product_id>0)) {

                    $up = false;
                    if ($second_product_id==0) {
                        //must have upsell - convert upsell to main product
                        if (!$upsell_optional || (isset($data['add-upsell']) && $data['add-upsell']==1)) {
                            $second_product_id = $second_upsell_product_id;
                        }
                    } elseif (!$upsell_optional || (isset($data['add-upsell']) && $data['add-upsell']==1)) {
                        $up = true;
                    }

                    if ($second_product_id>0) {
                        $send_data['method'] = 'NewOrder';
                        $send_data['productId'] = $second_product_id;
                        $send_data['shippingId'] = $second_shipping_id;
                        $send_data['campaignId']= $prepaid ? $prepaid_second_campaign_id:$second_campaign_id;
                        $send_data['ipAddress'] = $this->ip();
                        $send_data['firstName']=$data['fields_fname'];
                        $send_data['lastName']=$data['fields_lname'];
                        $send_data['shippingAddress1']=$data['fields_address1'];
                        $send_data['shippingAddress2']=$data['fields_address2'];
                        $send_data['shippingCity']=$data['fields_city'];
                        $send_data['shippingState']=$data['fields_state'];
                        $send_data['shippingZip']=$data['fields_zip'];
                        $send_data['shippingCountry']=$data['shippingCountry'];
                        $send_data['phone']=$data['fields_phone'];
                        $send_data['email']=$data['fields_email'];
                        if (!$up) {
                            $send_data['upsellCount']=0;
                            $send_data['upsellProductIds']='';
                        } else {
                            $send_data['upsellCount']=1;
                            $send_data['upsellProductIds']=$second_upsell_product_id;
                        }
                        $request_url = $url.'/admin/transact.php';
                        parse_str($this->curl($request_url,$send_data), $result);


                        if ($result['errorFound']==0) {
                            return array('status'=>1,'order_id'=>$order_id);
                        } else {
                            return array('status'=>0,'message'=>'Post Ops order failed: '.$this->handle_error($result['responseCode']));
                        }
                    }
                } else {
                    return array('status'=>1,'order_id'=>$order_id);
                }
            } else {
                //temporary - record error message
                file_put_contents("errors.log",date('m/d/Y H:i:s').' - Order Error (Prospect '.$data['prospect_id'].') - '.' Error code: '.$result['responseCode'].': '.$this->handle_error($result['responseCode'])."\r\n",FILE_APPEND);

                //handle error
                return array('status'=>0,'message'=>$this->handle_error($result['responseCode']));
            }
        } else {
            return array('status'=>0,'message'=>'No data received');
        }
    }

    private function curl($url,$data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.45 Safari/535.19');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        return curl_exec($ch);
    }

    private function handle_error($id) {
        $error_messages = array(
            304=>'Invalid First Name',
            305=>'Invalid Last Name',
            306=>'Invalid Address 1',
            307=>'Invalid City',
            308=>'Invalid State',
            309=>'Invalid Zip Code',
            310=>'Invalid Country',
            311=>'Invalid Billing Address 1',
            312=>'Invalid Billing City',
            313=>'Invalid Billing State',
            314=>'Invalid Billing Zip Code',
            315=>'Invalid Billing Country',
            316=>'Invalid Phone Number',
            317=>'Invalid Email Address',
            318=>'Invalid Credit Card Type',
            319=>'Invalid Credit Card Number',
            320=>'Invalid Expiration Date',
            323=>'CVV Required',
            325=>'Invalid CVV Length',
            342=>'Expired Credit Card',
            800=>'Transaction Declined'
        );

        return isset($error_messages[$id]) ? $error_messages[$id]:'An error occurred. Please try again later. ('.$id.')';
    }

    private function ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}