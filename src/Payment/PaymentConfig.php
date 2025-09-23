<?php

namespace Payment;

class PaymentConfig
{
    private static $config = [
        'gateways' => [
            'ecocash' => [
                'name' => 'EcoCash',
                'enabled' => false,
                'merchant_id' => '',
                'api_key' => '',
                'webhook_secret' => '',
                'currency' => 'USD',
                'icon' => 'fa-solid fa-mobile-screen',
                'description' => 'Pay with EcoCash mobile money'
            ],
            'paynow' => [
                'name' => 'PayNow',
                'enabled' => false,
                'merchant_id' => '',
                'api_key' => '',
                'webhook_secret' => '',
                'currency' => 'USD',
                'icon' => 'fa-solid fa-credit-card',
                'description' => 'Pay with PayNow'
            ],
            'paypal' => [
                'name' => 'PayPal',
                'enabled' => false,
                'client_id' => '',
                'client_secret' => '',
                'webhook_id' => '',
                'currency' => 'USD',
                'icon' => 'fa-brands fa-paypal',
                'description' => 'Pay with PayPal'
            ]
        ],
        'plans' => [
            'plagiarism_checker' => [
                'name' => 'Pro Plagiarism Checker',
                'price' => 5.00,
                'currency' => 'USD',
                'description' => 'Unlimited plagiarism checks with advanced features',
                'features' => [
                    'Unlimited document checks',
                    'Advanced AI detection',
                    'Detailed similarity reports',
                    'Multiple API integrations',
                    'Priority support'
                ]
            ]
        ]
    ];

    public static function getConfig()
    {
        return self::$config;
    }

    public static function getGateway($gateway)
    {
        return self::$config['gateways'][$gateway] ?? null;
    }

    public static function getEnabledGateways()
    {
        return array_filter(self::$config['gateways'], function($gateway) {
            return $gateway['enabled'];
        });
    }

    public static function getPlan($plan)
    {
        return self::$config['plans'][$plan] ?? null;
    }

    public static function updateGateway($gateway, $config)
    {
        if (isset(self::$config['gateways'][$gateway])) {
            self::$config['gateways'][$gateway] = array_merge(
                self::$config['gateways'][$gateway], 
                $config
            );
            return true;
        }
        return false;
    }
}
