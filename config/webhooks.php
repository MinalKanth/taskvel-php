<?php
/**
 * Optional Slack / Microsoft Teams incoming-webhook URLs. If set, Daily
 * Check-in events (task started/completed, day checked out) also post a
 * message to these channels — no OAuth app needed, just an Incoming
 * Webhook URL from your workspace's app directory. Leave blank to disable.
 */
define('SLACK_WEBHOOK_URL', '');   // e.g. 'https://hooks.slack.com/services/T000/B000/XXXX'
define('TEAMS_WEBHOOK_URL', '');   // e.g. 'https://yourorg.webhook.office.com/webhookb2/...'
