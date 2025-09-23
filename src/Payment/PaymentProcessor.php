<?php

namespace Payment;

use Payment\PaymentConfig;

class PaymentProcessor
{
    private $config;
    private $gateway;

    public function __construct($gateway = null)
    {
        $this->config = PaymentConfig::getConfig();
        $this->gateway = $gateway;
    }

    public function createPayment($plan, $amount, $currency = 'USD', $user_id = null)
    {
        $planConfig = PaymentConfig::getPlan($plan);
        if (!$planConfig) {
            throw new \Exception("Plan not found: {$plan}");
        }

        $paymentData = [
            'plan' => $plan,
            'amount' => $amount,
            'currency' => $currency,
            'user_id' => $user_id,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'payment_id' => $this->generatePaymentId()
        ];

        // Store payment in database
        $this->storePayment($paymentData);

        return $paymentData;
    }

    public function processPayment($paymentId, $gateway = null)
    {
        $gateway = $gateway ?: $this->gateway;
        $gatewayConfig = PaymentConfig::getGateway($gateway);
        
        if (!$gatewayConfig || !$gatewayConfig['enabled']) {
            throw new \Exception("Gateway not available: {$gateway}");
        }

        // Get payment data
        $payment = $this->getPayment($paymentId);
        if (!$payment) {
            throw new \Exception("Payment not found: {$paymentId}");
        }

        // Process based on gateway
        switch ($gateway) {
            case 'ecocash':
                return $this->processEcoCashPayment($payment, $gatewayConfig);
            case 'paynow':
                return $this->processPayNowPayment($payment, $gatewayConfig);
            case 'paypal':
                return $this->processPayPalPayment($payment, $gatewayConfig);
            default:
                throw new \Exception("Unsupported gateway: {$gateway}");
        }
    }

    private function processEcoCashPayment($payment, $config)
    {
        // EcoCash API integration will be implemented here
        return [
            'status' => 'pending',
            'gateway' => 'ecocash',
            'redirect_url' => $this->generateEcoCashUrl($payment, $config),
            'message' => 'Redirecting to EcoCash...'
        ];
    }

    private function processPayNowPayment($payment, $config)
    {
        // PayNow API integration will be implemented here
        return [
            'status' => 'pending',
            'gateway' => 'paynow',
            'redirect_url' => $this->generatePayNowUrl($payment, $config),
            'message' => 'Redirecting to PayNow...'
        ];
    }

    private function processPayPalPayment($payment, $config)
    {
        // PayPal API integration will be implemented here
        return [
            'status' => 'pending',
            'gateway' => 'paypal',
            'redirect_url' => $this->generatePayPalUrl($payment, $config),
            'message' => 'Redirecting to PayPal...'
        ];
    }

    private function generatePaymentId()
    {
        return 'PAY_' . time() . '_' . substr(md5(uniqid()), 0, 8);
    }

    private function storePayment($paymentData)
    {
        // Store in database - will be implemented with proper DB class
        // For now, just return the data
        return $paymentData;
    }

    private function getPayment($paymentId)
    {
        // Get from database - will be implemented with proper DB class
        // For now, return mock data
        return [
            'payment_id' => $paymentId,
            'plan' => 'plagiarism_checker',
            'amount' => 5.00,
            'currency' => 'USD',
            'status' => 'pending'
        ];
    }

    private function generateEcoCashUrl($payment, $config)
    {
        // Generate EcoCash payment URL
        return "https://ecocash.example.com/pay?amount={$payment['amount']}&currency={$payment['currency']}&payment_id={$payment['payment_id']}";
    }

    private function generatePayNowUrl($payment, $config)
    {
        // Generate PayNow payment URL
        return "https://paynow.example.com/pay?amount={$payment['amount']}&currency={$payment['currency']}&payment_id={$payment['payment_id']}";
    }

    private function generatePayPalUrl($payment, $config)
    {
        // Generate PayPal payment URL
        return "https://paypal.example.com/pay?amount={$payment['amount']}&currency={$payment['currency']}&payment_id={$payment['payment_id']}";
    }

    public function handleWebhook($gateway, $data)
    {
        // Handle payment webhooks from gateways
        switch ($gateway) {
            case 'ecocash':
                return $this->handleEcoCashWebhook($data);
            case 'paynow':
                return $this->handlePayNowWebhook($data);
            case 'paypal':
                return $this->handlePayPalWebhook($data);
            default:
                throw new \Exception("Unsupported gateway webhook: {$gateway}");
        }
    }

    private function handleEcoCashWebhook($data)
    {
        // Process EcoCash webhook
        return ['status' => 'success', 'message' => 'EcoCash webhook processed'];
    }

    private function handlePayNowWebhook($data)
    {
        // Process PayNow webhook
        return ['status' => 'success', 'message' => 'PayNow webhook processed'];
    }

    private function handlePayPalWebhook($data)
    {
        // Process PayPal webhook
        return ['status' => 'success', 'message' => 'PayPal webhook processed'];
    }
}
