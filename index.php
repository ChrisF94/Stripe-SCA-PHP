<html>
<head>
<title>Stripe SCA</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="//code.jquery.com/jquery-1.9.1.js"></script>
<style>
.StripeElement {
  background-color: white;
  padding: 8px 12px;
  border-radius: 4px;
  border: 1px solid transparent;
  box-shadow: 0 1px 3px 0 #e6ebf1;
  -webkit-transition: box-shadow 150ms ease;
  transition: box-shadow 150ms ease;
}
.StripeElement--focus {
    box-shadow: 0 1px 3px 0 #cfd7df;
}
.StripeElement--invalid {
    border-color: #fa755a;
}
.StripeElement--webkit-autofill {
    background-color: #fefde5 !important;
}
</style>
</head>
<body>
<center>
<div id="feedback"></div>
<div class="form-row">
<label for="card-element">Credit or debit card</label>
<div id="card-element"></div><br>
<div id="card-errors"></div>
</div><br>
<button id="card-button">Submit Payment</button>
</div>
</div>
</center>
</body>
<script src="https://js.stripe.com/v3/"></script>
<script>
var stripe = Stripe('pk_test_7wfckSiqfZNjmsVhRjqSDV6z');
var elements = stripe.elements();
var style = {
    base: {
        color: '#32325d',
        fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
        fontSmoothing: 'antialiased',
        fontSize: '16px',
        '::placeholder': {
            color: '#aab7c4'
        }
    },
    invalid: {
        color: '#fa755a',
        iconColor: '#fa755a'
    }
};
var cardElement = elements.create('card', {style: style});
cardElement.mount('#card-element');
cardElement.addEventListener('change', function(event) {
    var displayError = document.getElementById('card-errors');
if (event.error) {
    $('#card-errors').show().addClass('feedback error').html(event.error.message);
    } else {
        displayError.textContent = '';
        $("#card-errors").removeClass("feedback error");
    }
});
var product = document.getElementById('product');
var cardButton = document.getElementById('card-button');
var displayError = document.getElementById('card-errors');
cardButton.addEventListener('click', function(ev) {
    stripe.createPaymentMethod('card', cardElement, {
    }).then(function(result) {
    //cardElement.clear();
    if (result.error) {
        $('#card-errors').show().addClass('feedback error').html(result.error.message);
    } else {
     
      fetch('ajax/buy.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ payment_method_id: result.paymentMethod.id })
        
      }).then(function(result) {
        // Handle server response (see Step 3)
        result.json().then(function(json) {
          handleServerResponse(json);
        })
      });
    }
  });
}, {once : true});
function handleServerResponse(response) {
   
  if (response.error) {
        $("#card-errors").addClass("feedback error");
        displayError.textContent = response.error;
  } else if (response.requires_action) {
    stripe.handleCardAction(
        
      response.payment_intent_client_secret
      
    ).then(function(result) {
      if (response.error) {
          $("#card-errors").addClass("feedback error");
        displayError.textContent = response.error;
      } else {
        // The card action has been handled
        // The PaymentIntent can be confirmed again on the server
        fetch('ajax/buy.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ payment_intent_id: result.paymentIntent.id })
        }).then(function(confirmResult) {
          return confirmResult.json();
        }).then(handleServerResponse);
      }
    });
  } else {
    $('#feedback').show().removeClass('feedback error').addClass('feedback success').html(response.payment);
  }
}
</script>
</html>
