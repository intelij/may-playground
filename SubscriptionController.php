<?php

namespace Vanguard\Http\Controllers\Web;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Invoice;
use Stripe\PaymentMethod;
use Stripe\Price;
use Stripe\Product;
use Stripe\Subscription;
use Vanguard\Http\Controllers\Controller;

class SubscriptionController extends Controller
{

    private function generateRandomEmail() {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $domain = ['gmail.com', 'yahoo.com', 'hotmail.com', 'example.com']; // Add more domains as needed
        $username = '';

        // Generate a random username with length between 5 to 10 characters
        $usernameLength = mt_rand(5, 10);
        for ($i = 0; $i < $usernameLength; $i++) {
            $username .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        // Append some random numbers to the username
        $username .= mt_rand(100, 999);

        // Choose a random domain from the list
        $randomDomain = $domain[mt_rand(0, count($domain) - 1)];

        return $username . '@' . $randomDomain;
    }

    private function generateRandomFullName() {
        $firstNames = ['John', 'Jane', 'Alice', 'Bob', 'Emma', 'Michael', 'Sophia', 'William', 'Olivia', 'James'];
        $lastNames = ['Smith', 'Johnson', 'Brown', 'Taylor', 'Wilson', 'Davis', 'Clark', 'Hall', 'Walker', 'White'];

        $randomFirstName = $firstNames[array_rand($firstNames)];
        $randomLastName = $lastNames[array_rand($lastNames)];

        return $randomFirstName . ' ' . $randomLastName .' Perfume (75oz)' ;
    }

    private function getStripeProducts($productName="GEO_SUB_29")
    {
        $existingProducts = Product::all();

        $product = null;

        foreach ($existingProducts as $productFound) {
            if ($productFound->name === $productName) {
                $product = $productFound;
            }
        }

        if (is_null($product)) {
            $product = Product::create([
                'name' => $productName,
                'type' => 'service',
            ]);
        }

        // Return product with the specified name is not found
        return $product;
    }

    public function createSubscription(Request $request)
    {
        // Set your Stripe API key
        Stripe::setApiKey('sk_test_rQyyGyrrgCHDV9QpaOr8cBay');

        $randomPrice = $unitAmount = 2900;
        $prefix = "GEO_SUB_";

        $productName = $prefix.$randomPrice;

        // Create a new Stripe customer
        $customer = Customer::create([
            'email' => $this->generateRandomEmail(),
            // Add any additional customer data here
        ]);

        dump($customer);

        $product = $this->getStripeProducts($productName);

        dump($product);

        // Create a payment method using Stripe test tokens
        $paymentMethod = PaymentMethod::create([
            'type' => 'card',
            'card' => [
                'number' => '4242424242424242', // Test card number for success
                'exp_month' => 12, // Example expiration month (any valid future month)
                'exp_year' => 2024, // Example expiration year (any valid future year)
                'cvc' => '123', // Example CVC (any 3 or 4 digit number)
            ],
        ]);

        // Attach the payment method to the customer
        $customer->attachPaymentMethod($paymentMethod->id);
        //*
        //**********************************************************/

        // // Create a payment method using Stripe test tokens
        // $paymentMethod = PaymentMethod::create([
        //     'type' => 'card',
        //     'card' => [
        //         'token' => $request->stripe_token, // Stripe test token
        //     ],
        // ]);

        // // Attach the payment method to the customer
        // $customer->attachPaymentMethod($paymentMethod->id);

        // Create a new Stripe price
        $price = Price::create([
            'product' => $product->id,
            'unit_amount' => $unitAmount, // Price in cents
            'currency' => 'gbp', // Change currency as needed
            'recurring' => [
                'interval' => 'month', // Monthly subscription
            ],
            // Add any additional price data here
        ]);

        dump($price);

        // Subscribe the customer to the product with the newly created price
        $subscription = Subscription::create([
            'customer' => $customer->id,
            'items' => [
                [
                    'price' => $price->id, // Stripe price ID for the product
                ],
            ],
            'payment_behavior' => 'default_incomplete',
            // 'billing_cycle_anchor' => 'unchanged', // Anchor billing cycle to today
            'proration_behavior' => 'create_prorations', // Prorate for any changes
            // 'trial_end' => 'now'
        ]);

        dump($subscription, $subscription->latest_invoice);

        // Pay the first invoice immediately
        // $invoice = Invoice::retrieve($subscription->latest_invoice);
        // $invoice->pay();


        // Return success response
        return response()->json(['message' => 'Subscription created successfully', 'subscription' => $subscription]);
    }


}
