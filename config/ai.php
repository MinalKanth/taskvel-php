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

// Model to use. gemini-2.0-flash was retired in 2026. As of this writing
// Google's current stable lineup is the Gemini 3.x family (see
// https://ai.google.dev/gemini-api/docs/models) — gemini-3.5-flash-lite is
// their fastest, cheapest *stable* 3.x model, a good fit for the short
// suggestions/summaries this app asks for. Google rotates model names
// every few months, so if AI features stop working again, this is usually
// why — check the models page above for the current stable model name and
// update it here (or via GEMINI_MODEL in .env) without touching any other
// file.
define('GEMINI_MODEL', getenv('GEMINI_MODEL') ?: 'gemini-3.5-flash-lite');
