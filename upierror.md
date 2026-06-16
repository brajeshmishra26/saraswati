# UPI Click-To-Pay Error — Session Transcript

Date: 2026-06-10

This file captures the session conversation and investigation notes about the UPI "Account Not Supported" error on the donation page so work can continue later.

---

User: (issue)
In "https://maa-saraswati.co.in/donate.php?lang=hi" page 
when we click 'UPI Click-To-Pay' Button
it open UPI app But in final step it throw error
'Account Not Supported' 
while QR is working fine

Assistant: I'll investigate the UPI Click-To-Pay issue on the donation page. Let me examine the donation logic and UPI configuration.

(Investigation actions)
- Opened `donate.php`.
- Opened `includes/functions.php` to find `donation_upi_link()` and `donation_upi_intent_link()`.
- Checked `data/upi-uri.txt` (file exists but contains only whitespace).
- Checked `includes/config.php` for UPI settings.

Findings:
- The `donation_upi_link()` function prefers these sources in order:
  1. `data/upi-uri.txt` (if it contains a `upi://pay?` URI)
  2. `$site['bank']['upi_uri']` from `includes/config.php` (if it contains a `upi://pay?` URI)
  3. Builds a `upi://pay?pa=...&pn=...&cu=INR` URI from `$site['bank']['upi_id']` and `$site['bank']['account_name']`.

- In `includes/config.php` the `upi_id` is set to `42636837783@sbi`.
  - This value is actually a bank account number used as `upi_id` in the config, which is incorrect.
  - UPI apps expect a valid VPA (Virtual Payment Address) like `name@bank` or a registered phone-based VPA.

- `data/upi-uri.txt` is blank, so the code falls back to building a link from the `upi_id` value.

Root cause:
- The configured `upi_id` appears to be an account number, not a valid registered UPI VPA. That causes UPI apps to open but fail with "Account Not Supported" during final validation.
- The QR works because the QR likely embeds a different, valid UPI URI.

Recommended fixes (actions to complete):
1. Obtain the actual registered UPI VPA for the account from the bank or mobile UPI app (e.g., `yourvpa@sbi` or `phone@sbi`).
2. Update `includes/config.php` to set the correct `upi_id`.
   - Example: `'upi_id' => 'yourvpa@sbi',`
3. Alternatively, paste a full working UPI URI into `site['bank']['upi_uri']` or into `data/upi-uri.txt` (exact `upi://pay?pa=...` string) so the app uses that explicit URI.

Example of `upi_uri` override:
```
upi://pay?pa=yourvpa@sbi&pn=Maa%20Saraswati%20Sansthan&cu=INR
```

Notes:
- After updating, test on a mobile device using the UPI Click-to-Pay button and Android Intent.

Conversation snippet (latest):
User: write this session conversation to upierror.md so we can pickup where we left
Assistant: (created this file)

---

Status: Notes written to this file. Next step: update `includes/config.php` with a valid VPA or paste the working UPI URI into `data/upi-uri.txt`.
