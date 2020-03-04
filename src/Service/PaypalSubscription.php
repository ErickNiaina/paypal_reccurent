<?php

namespace App\Service;

use DateInterval;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\Date;

class PaypalSubscription 
{

    private $username;
    private $password;
    private $signature;
    private $offers;
    private $endpoint;
    private $sandbox;
    
    public function __construct($username, $password, $signature, $offers, $sandbox = true)
    {
        $this->username = $username;
        $this->password = $password;
        $this->signature = $signature;
        $this->offers = $offers;
        $this->endpoint = "https://api-3t." . ($sandbox ? "sandbox." : "") . "paypal.com/nvp";
        $this->sandbox = $sandbox;
    }

    public function nvp($options = [])
    {
        $curl = curl_init();

        $data = [
            'USER' => $this->username,
            'PWD' => $this->password,
            'SIGNATURE' => $this->signature,
            'VERSION' => 86
        ];

        $data = array_merge($data,$options);

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->endpoint,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => http_build_query($data),
        ]);

        $response = curl_exec($curl);

        $responseArray = [];
        parse_str($response, $responseArray);

        return $responseArray;

    }

    //envoyer les requete et passer dans un compte paypal
    public function subscribe($offer_id)
    {
        if (!isset($this->offers[$offer_id])) {
            throw new Exception('Cette offre n\'existe pas');
        }

        $offer = $this->offers[$offer_id];

        $data = [
            'METHOD' => 'SetExpressCheckout',
            'PAYMENTREQUEST_0_AMT' => $offer['price'] * 1.2,
            'PAYMENTREQUEST_0_ITEMAMT' => $offer['price'],
            'PAYMENTREQUEST_0_TAXAMT' => $offer['price'] * 0.2,
            'PAYMENTREQUEST_0_CURRENCYCODE' => "EUR",
            'PAYMENTREQUEST_0_CUSTOM' => $offer_id,
            'L_BILLINGTYPE0' => 'RecurringPayments',
            'L_BILLINGAGREEMENTDESCRIPTION0' => $offer['name'],
            'cancelUrl' => 'https://bcb1ca86.ngrok.io/subscription',
            'returnUrl' => 'https://bcb1ca86.ngrok.io/verification/process'
        ];

        $response = $this->nvp($data);

        if (!isset($response['TOKEN'])) {

            throw new Exception($response['L_LONGMESSAGE0']);
            
        }

        $token = $response['TOKEN'];

        $url = "https://www." . ($this->sandbox ? "sandbox." : "") . "paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=$token";

        header('location:'.$url);
        die;
    }

    //recupere les informations apres validation paypal
    public function getCheckoutDetail($token)
    {
        $curl = curl_init();
        $data = [

            'METHOD' => 'GetExpressCheckoutDetails',
            'TOKEN' => $token
        ];

        $response = $this->nvp($data);

        return $response;
            
    }

    //traitement des données
    public function doSubscribe($token,UserSubscribeService $subscribeservice)
    {
        $detail = $this->getCheckoutDetail($token);

        $idUserCourant = $subscribeservice->getIdUserCourant();

        $offer_id = $detail['PAYMENTREQUEST_0_CUSTOM'];

        if (!isset($this->offers[$offer_id])) {
            throw new Exception('Cette offre n\'existe pas');
        }

        $offer = $this->offers[$offer_id];

        $period = $offer['period'] === 'Month' ? new DateInterval('P1M') : new DateInterval('P1Y');

        $start = (new DateTime())->add($period)->getTimestamp();

        $response = $this->nvp([

            'METHOD' => 'CreateRecurringPaymentsProfile',
            'TOKEN' => $token,
            'PAYERID' => $detail['PAYERID'],
            'DESC' => $offer['name'],
            'AMT' => $offer['price'],
            'TAXAMT' => $offer['price'] * 0.2,
            'BILLINGPERIOD' => $offer['period'],
            'BILLINGFREQUENCY' => 1,
            'CURRENCYCODE' => "EUR",
            'COUNTRYCODE' => 'FR',
            'MAXFAILEDPAYMENTS' => 3,
            'PROFILESTARTDATE' => gmdate("Y-m-d\TH:i:s\Z",$start),
            'INITAMT' => $offer['price'] * 1.2
        ]);

        if ($response['ACK'] === "Success") 
        {
            $subscribeservice->modifierUtilisateurAvoirAbonnement($detail['PAYERID'],$response['PROFILEID'],$idUserCourant);
           //var_dump($response,$this->getProfileDetail($response['PROFILEID']),$detail,$offer);
        }
        else
        {
            throw new Exception($response['L_LONGMESSAGE0']);
        }
        
    }

    //faire le payment final
    public function getProfileDetail($profile_id)
    {
        $response = $this->nvp([
            'METHOD' => 'GetRecurringPaymentsProfileDetails',
            'PROFILEID' => $profile_id
         ]);
         
        return $response;
    }


    //prendre en paramètre les données à verifier
    public function verifyIpn($data){

        $curl = curl_init();

        curl_setopt_array($curl, [

            CURLOPT_URL => "https://www." . ($this->sandbox ? "sandbox." : "") . "paypal.com/cgi-bin/webscr?cmd=_notify-validate&".http_build_query($data),
            CURLOPT_RETURNTRANSFER => 1,
        ]);

        $response = curl_exec($curl);

        return $response === "VERIFIED";
    }


    //annuler l'abonnement
    public function unSubscribe($profile_id){

        $response = $this->nvp([

            'METHOD' => 'ManageRecurringPaymentsProfileStatus',
            'PROFILEID' => $profile_id,
            'ACTION' => 'Cancel'
        ]);

        if($response['ACK'] === 'Success'){

            return true;
        }

        throw new Exception($response['L_LONGMESSAGE0']);
    }
}
