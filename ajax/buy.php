<?php
require_once('../stripe/stripe-php-6.41.0/init.php');

\Stripe\Stripe::setApiKey('sk_test_XXXXXX');

header('Content-Type: application/json');

$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str, TRUE);

$intent = null;
$email = "example@gmail.com";

  try {
    if (isset($json_obj['payment_method_id'])) {
      # Create the PaymentIntent
      
      $customer = \Stripe\Customer::create(array(
            "email" => $email
      ));

      $intent = \Stripe\PaymentIntent::create([
        'payment_method' => $json_obj['payment_method_id'],
        'amount' => 1499,
        'currency' => 'GBP',
        'confirmation_method' => 'manual',
        'confirm' => true,
        "customer" => $customer->id,
        
      ]);
    }
    if (isset($json_obj['payment_intent_id'])) {
      $intent = \Stripe\PaymentIntent::retrieve(
        $json_obj['payment_intent_id']
      );
      $intent->confirm();
    }
    generatePaymentResponse($intent);
    
  }catch (\Exception $e) {
    if ($e instanceof \Stripe\Error\Card) {
    // Since it's a decline, \Stripe\Error\Card will be caught
    echo json_encode([
      'error' => $e->getMessage()
    ]);
    
    } elseif ($e instanceof \Stripe\Error\RateLimit) {
    // Too many requests made to the API too quickly
    echo json_encode([
      'error' => $e->getMessage()
    ]);
    } elseif ($e instanceof \Stripe\Error\InvalidRequest) {
    // Invalid parameters were supplied to Stripe's API
    echo json_encode([
      'error' => $e->getMessage()
    ]);
    } elseif ($e instanceof \Stripe\Error\Authentication) {
    // Authentication with Stripe's API failed
    echo json_encode([
      'error' => $e->getMessage()
    ]);
    } elseif ($e instanceof \Stripe\Error\ApiConnection) {
    // Network communication with Stripe failed
    echo json_encode([
      'error' => $e->getMessage()
    ]);
    } elseif ($e instanceof \Stripe\Error\Base) {
    // Display a very generic error to the user, and maybe send
    echo json_encode([
      'error' => $e->getMessage()
    ]);
    } else {
    echo json_encode([
      'error' => $e->getMessage()
    ]);
    }
}

  function generatePaymentResponse($intent) {
    # Note that if your API version is before 2019-02-11, 'requires_action'
    # appears as 'requires_source_action'.
    if ($intent->status == 'requires_source_action' &&
        $intent->next_action->type == 'use_stripe_sdk') {
      # Tell the client to handle the action
      echo json_encode([
        'requires_action' => true,
        'payment_intent_client_secret' => $intent->client_secret
      ]);
    } else if ($intent->status == 'succeeded') {
      # The payment didn’t need any additional actions and completed!
      # Handle post-payment fulfillment
   
     $message = "Your payment was successful";
      echo json_encode([
        "success" => true,
        "payment" => $message
      ]);
            
    }else{
      # Invalid status
      http_response_code(500);
      echo json_encode(['error' => 'Invalid PaymentIntent status']);
    }
  }
?>
