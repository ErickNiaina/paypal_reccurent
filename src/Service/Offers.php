<?php

namespace App\Service;

class Offers{
    
    public static function  getOffers(){
        
        return [
            [
                "name" => "Abonnement mensuel",
                "price" => 10,
                "price_text" => "10£/mois",
                "period" => 'Month'
            ],
            [
                "name" => "Abonnement annuel",
                "price" => 100,
                "price_text" => "100£/ans",
                "period" => 'Year'
            ]
        ];

    }







}