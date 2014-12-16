<?php
namespace App\Helpers;

/**
 * Payment Gateway Integration with VINDI.
 *
 * VINDI is a Brazilian payment gateway. This class allows developers
 * to integrate with VINDI's API and perform basic operations, such as:
 *
 * - Register customers
 * - Associate creditcards with existing customers
 * - Create and send a bill to a specific customer
 *
 * That's all you need for a quick payment integration. Complex adjustments
 * can be done through VINDI's website.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   PaymentGatewy
 * @package    Payments
 * @author     Felipe Hlibco <hlibco@gmail.com>
 * @copyright  1997-2014 The PHP Group
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    0.1
 */
class Vindi {

    /**
     * API timeout in seconds
     *
     * @param int
     */
    private $_api_timeout = 60;


    /**
     * API key
     *
     * @param string
     */
    private $_api_key;


    /**
     * If curl_init() should be run manually before make API calls
     *
     * @param boolean
     */
    private $_curl_init_manually = false;


    /**
     * If curl_close() should be run manually after make API calls
     *
     * @param resource
     */
    private $_curl_close_manually = false;


    /**
     * Curl resource
     *
     * @param resource
     */
    private $_ch;


    /**
     * Curl's response
     *
     * @param array
     */
    private $_response;


    /**
     * Curl's response body
     *
     * @param resource
     */
    private $_body;


    public function __construct($api_key)
    {
        $this->_api_key = $api_key;
    }


    /**
     * Initiate curl connection
     *
     * @return void
     */
    public function curlInit()
    {
        $this->_ch = curl_init();
        curl_setopt($this->_ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($this->_ch, CURLOPT_TIMEOUT, $this->_api_timeout);
        curl_setopt($this->_ch, CURLOPT_USERPWD, $this->_api_key . ':');
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_ch, CURLOPT_HEADER, 1);
    }


    /**
     * Close curl connection
     *
     * @return void
     */
    public function curlClose()
    {
        curl_close($this->_ch);
    }


    /**
     * Execute Curl call
     *
     * @param  string $endpoint url to be called
     * @param  array  $params   used to post data to the endpoint
     * @return void
     */
    private function exec($endpoint, $params = null)
    {
        try {
            /**
             * Initiate Curl connection
             */
            if ($this->_curl_init_manually === false) {
                $this->curlInit();
            }


            curl_setopt($this->_ch, CURLOPT_URL, $endpoint);

            if ($params) {
                curl_setopt($this->_ch, CURLOPT_POST, 1);
                curl_setopt($this->_ch, CURLOPT_POSTFIELDS, json_encode($params));
            }

            $response    = curl_exec($this->_ch);
            $error       = curl_error($this->_ch);
            $header_size = curl_getinfo($this->_ch, CURLINFO_HEADER_SIZE);

            $result = array( 'header'     => substr($response, 0, $header_size),
                             'body'       => substr($response, $header_size),
                             'curl_error' => $error,
                             'http_code'  => curl_getinfo($this->_ch, CURLINFO_HTTP_CODE),
                             'last_url'   => curl_getinfo($this->_ch, CURLINFO_EFFECTIVE_URL)
                             );

            if ( !empty($result['body']) ) {
                $this->_body = json_decode($result['body']);
            }

            /**
             * Close Curl connection
             */
            if ($this->_curl_close_manually === false) {
                $this->curlClose();
            }

            return $this->_response = $result;

        } catch (exception $e) {

        }
    }


    /**
     * Get curl response
     *
     * @return array | null
     */
    public function getResponse()
    {
        return $this->_response;
    }


    /**
     * Get curl response's body
     *
     * @return resource | null
     */
    public function getBody()
    {
        return $this->_body;
    }


    /**
     * Get API
     *
     * @return object
     */
    public function getPaymentMethods()
    {
        $endpoint = 'https://www.vindi.com.br/recurrent/api/v1/payment_methods';

        return $this->exec($endpoint);
    }


    /**
     * Create a new customer
     *
     * @param  string $name  customer's name
     * @param  string $email customer's email
     * @param  string $code  customer's id (to track the current user outside the website)
     * @return int | false   customer id or false
     */
    public function createCustomer($name, $email, $code)
    {
        $endpoint = 'https://www.vindi.com.br:443/recurrent/api/v1/customers';

        $params = array(
            'name'  => $name,
            'email' => $email,
            'code'  => $code,
            );

        $response = $this->exec($endpoint, $params);

        if ($response) {
            return $this->getBody()->customer->id;
        }

        return false;
    }


    /**
     * Create a new payment profile associated to a customer
     *
     * @param  string $holder_name     creditcard owner's name
     * @param  string $card_expiration expiration date 'mm/YY'
     * @param  int    $card_number     creditcard number
     * @param  int    $card_cvv        creditcard verification value
     * @param  int    $customer_id     customer id registered in VINDI's platform
     * @return object API object
     */
    public function createPaymentProfile($holder_name, $card_expiration, $card_number, $card_cvv, $customer_id)
    {
        $endpoint = 'https://www.vindi.com.br:443/recurrent/api/v1/payment_profiles';

        $params = array(
            'holder_name'     => $holder_name,
            'card_expiration' => $card_expiration,
            'card_number'     => $card_number,
            'card_cvv'        => $card_cvv,
            'customer_id'     => $customer_id,
        );

        return $this->exec($endpoint, $params);
    }


    /**
     * Create a bill (this is not the invoice it self)
     *
     * @param  string $holder_name     creditcard owner's name
     * @param  string $card_expiration expiration date 'mm/YY'
     * @param  int    $card_number     creditcard number
     * @param  int    $card_cvv        creditcard verification value
     * @param  int    $customer_id     customer id registered in VINDI's platform
     * @return object API object
     */
    public function createBill($customer_id, $payment_method_code, $amount, $product_id)
    {
        $endpoint = 'https://www.vindi.com.br:443/recurrent/api/v1/bills';

        $params = array(
            'customer_id'         => $customer_id,
            'payment_method_code' => $payment_method_code,
            'bill_items'          => array(
                                        array(
                                            'product_id'  => $product_id,
                                            'amount'      => $amount
                                        )
                                    )
            );

        return $this->exec($endpoint, $params);
    }



    /**
     * Get all details from an specified bill
     *
     * @param  int    $id Bill id
     * @return object API object
     */
    public function getBill($id)
    {
        $endpoint = 'https://www.vindi.com.br:443/recurrent/api/v1/bills/' . $id;

        return $this->exec($endpoint);
    }

}
