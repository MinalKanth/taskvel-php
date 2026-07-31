<?php
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');       
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: ''); 
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: ''); 
define('STRIPE_PRICE_PRO', getenv('STRIPE_PRICE_PRO') ?: '');          
define('STRIPE_PRICE_BUSINESS', getenv('STRIPE_PRICE_BUSINESS') ?: '');
define('STRIPE_PRICE_ORG_SEAT_MONTHLY', getenv('STRIPE_PRICE_ORG_SEAT_MONTHLY') ?: ''); 
define('STRIPE_PRICE_ORG_SEAT_YEARLY', getenv('STRIPE_PRICE_ORG_SEAT_YEARLY') ?: '');   
define('APP_BASE_URL', getenv('APP_BASE_URL') ?: '');                  