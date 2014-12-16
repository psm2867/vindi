vindi
=====

VINDI Payment Gateway Integration

Hey Brazilians, this is the fastest way to integrate with VINDI.

This is a really simple PHP + CURL class to communicate with VINDI's API.


Step 1
======
Create a "default" product at VINDI's website (price = $0.00)


STEP 2
======
Get your API key at VINDI's website and instantiate the class:

$payment = new Vindi('api_key_goes_here');


STEP 3
======
Get your customer's creditcard information via https page (form), and make the following calls:

$customer_id = $payment->createCustomer('customer_name', 'customer_email', 'customer_id_on_your_website');


STEP 4
======
Associate the creditcard data to this customer using a Payment Profile:

$payment->createPaymentProfile('holder_name', 'card_expiration', 'card_number', 'card_cvv, $customer_id);


STEP 5
======
Create a bill that will try to capture the amount specified from the Payment Profile linked to the current customer

$payment->createBill($customer_id, 'credit_card', 'amount', 'default_product_id');


In this example, you don't have to create multiple products at VINDI's website (only one!).

Constraint: VINDI API does not allow to link a Bill to a Payment Profile directly. You must have to link a Bill to a Customer. If the customer does not have any Payment Profile associated with, he/she will receive an email to pay the bill on the VINDI's website.

Enjoy it :)


