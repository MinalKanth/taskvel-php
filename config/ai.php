<?php
// ------------------------------------------------------------
// AI (task suggestions) config.
// Uses Google's Gemini API, which has a free tier — no credit card
// required, just a free API key from https://aistudio.google.com/apikey
//
// Add this line to your .env file (same place as your other keys):
//   GEMINI_API_KEY=your_key_here
// ------------------------------------------------------------
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');

// Free-tier model. "gemini-2.0-flash" is fast and cheap/free-quota friendly.
// You can change this later without touching any other file.
define('GEMINI_MODEL', getenv('GEMINI_MODEL') ?: 'gemini-2.0-flash');
