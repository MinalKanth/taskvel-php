<?php
// This file exists only in case your Stripe Dashboard webhook is registered
// against the underscore URL. All real logic lives in stripe-webhook.php
// (hyphen) — this just delegates to it so there's a single source of truth
// and the two files can never drift out of sync with each other.
require __DIR__ . '/stripe-webhook.php';