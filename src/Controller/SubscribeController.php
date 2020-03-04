<?php

namespace App\Controller;


require_once(__DIR__ . '/../../inc.php');

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Offers;
use App\Service\PaypalSubscription;
use App\Service\UserSubscribeService;
use DateTime;
use DateTimeZone;

class SubscribeController extends AbstractController
{

    /**
     * @Route("/subscription", name="subscribe")
     */
    public function subscription(UserSubscribeService $userSubscribeService)
    {
        $idUserActif = $userSubscribeService->getIdUserCourant();
        $user = $userSubscribeService->getOneUserActif($idUserActif);
        $dateNow = new DateTime();
        $dateEnd = $user->getEndSubscription();

        if($dateEnd > $dateNow){
            return $this->render('subscription\subscription_choix.html.twig',[
                'endSubscription' => $dateEnd,
                'userSubscribe' => $user,
            ]);
        }
        else
        {
            $offer = Offers::getOffers();
                return $this->render('subscription\subscription_choix.html.twig',[
                'offer' => $offer,
            ]);
        }

        //dd($idUserActif);
        
        
    }


    /**
     * @Route("/traitement/payment", name="payment_paypal")
     */
    public function verificationPayment(REQUEST $request,UserSubscribeService $userSubscribeService)
    {
        $subscription = $request->request->get('offer');
        $paypal = new PaypalSubscription(PAYPAL_USERNAME,PAYPAL_PASSWORD,PAYPAL_SIGNATURE,Offers::getOffers());
        
        if(isset($subscription))
        {
            
            $paypal->subscribe($subscription);
            
        }
        return new Response('');
    }



    /**
     * @Route("/verification/process", name="validation_payment")
     */
    public function validationPayment(Request $request,UserSubscribeService $subscribeservice)
    {
        $paypal = new PaypalSubscription(PAYPAL_USERNAME,PAYPAL_PASSWORD,PAYPAL_SIGNATURE,Offers::getOffers());
        
        $token = $request->query->get('token');

        $paypal->doSubscribe($token,$subscribeservice);

        //return $this->redirectToRoute('subscribe');
        return new Response();
    }
    

    /**
     * @Route("/ipn", name="ipn_verification")
     */
    public function verificationIpn(Request $request,UserSubscribeService $subscribeservice)
    {
        
       var_dump($request->request->get('txn_type'));die;

        file_put_contents("post.json",json_encode($_POST));
       //data = ces valeurs sont des parametres dans post ipn apres validation de paypal
        
       
        $data = "payment_cycle=Monthly&txn_type=recurring_payment_profile_created&last_name=Niaina&initial_payment_status=Completed&next_payment_date=03%3A00%3A00+Apr+03%2C+2020+PDT&residence_country=US&initial_payment_amount=12.00&currency_code=EUR&time_created=00%3A21%3A16+Mar+03%2C+2020+PST&verify_sign=AWBH8aweUVt9BYeOiM3v9vUxLIOJAUtXBzTE4EeJ17Jqv.RKUp49fLIQ&period_type=+Regular&payer_status=verified&test_ipn=1&tax=2.00&payer_email=zah%40test.com&first_name=Eric&receiver_email=zahotest%40paypal.com&payer_id=46Q8BECSD6EK8&product_type=1&initial_payment_txn_id=0BU68210314351340&shipping=0.00&amount_per_cycle=12.00&profile_status=Active&charset=windows-1252&notify_version=3.9&amount=12.00&outstanding_balance=0.00&recurring_payment_id=I-0RTBH8SC5CGF&product_name=Abonnement+mensuel&ipn_track_id=e5a1a03b02f27";
        
        parse_str($data,$data);
    
        $paypal = new PaypalSubscription(PAYPAL_USERNAME,PAYPAL_PASSWORD,PAYPAL_SIGNATURE,Offers::getOffers());
    
        if($paypal->verifyIpn($data))
        {
            $payer_id = $data['payer_id'];
            $user = $subscribeservice->getUserForPayerId($payer_id);
            
            $profile_id = $user->getProfileId();
            
            $detail = $paypal->getProfileDetail($profile_id);//verification d'abonnement avec la date end
           
            $subscription_end = new DateTime($detail['NEXTBILLINGDATE']);
            $timezone = new DateTimeZone(date_default_timezone_get());
            $dateExpiration = $subscription_end->setTimezone($timezone);
            //$subscription_end->format('Y-m-d  H:i:s'));
            $dateExpiration = $dateExpiration->format('Y-m-d H:i:s');
            $subscribeservice->modifierDateExpiration($dateExpiration,$user->getId());
            
        }
       
       
        return new Response('');
    }


    /**
     * @Route("subscription/canceled={canceled}", name="subscription_canceled")
     */
    public function cancelSubscribe(Request $request,UserSubscribeService $userSubscribeService,$canceled){
        $idUserActif = $userSubscribeService->getIdUserCourant();
        $user = $userSubscribeService->getOneUserActif($idUserActif);
        $paypal = new PaypalSubscription(PAYPAL_USERNAME,PAYPAL_PASSWORD,PAYPAL_SIGNATURE,Offers::getOffers());

        if(isset($canceled))
        {
           
            $nullable = "";
            $paypal->unSubscribe($user->getProfileId());
            $userSubscribeService->modifierProfileId($nullable,$idUserActif);
                
        }

        return new Response('Subscription Canceled');
    }




}