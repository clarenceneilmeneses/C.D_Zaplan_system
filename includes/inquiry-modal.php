<?php
/* Inquiry modal. Open it from anywhere with: onclick="openInquiry('Item name')"
   Submits to api/inquiry.php and is saved in the `inquiries` table (admin → Inquiries). */
$me = current_user();
?>
<div id="inquiryModal" class="overlay" role="dialog" aria-modal="true" aria-labelledby="inquiryTitle"
     onclick="if (event.target === this) closeInquiry()">
  <div class="sheet dialog dialog--shadow">

    <!-- Form view -->
    <div id="inquiryFormView">
      <div class="dialog__head dialog__head--top">
        <div>
          <h2 id="inquiryTitle" class="dialog__title dialog__title--lg">Send an Inquiry</h2>
          <p class="dialog__sub">We usually respond within 24 hours.</p>
        </div>
        <button type="button" onclick="closeInquiry()" aria-label="Close" class="close-btn">
          <iconify-icon icon="lucide:x" width="22"></iconify-icon>
        </button>
      </div>

      <form id="inquiryForm" onsubmit="submitInquiry(event)" class="form dialog__form">
        <?= csrf_field() ?>
        <!-- Filled in when the inquiry comes from the map estimator -->
        <input type="hidden" name="type_id"><input type="hidden" name="days">
        <input type="hidden" name="lat"><input type="hidden" name="lng"><input type="hidden" name="km">
        <!-- Spam trap: humans never see or fill this -->
        <input type="text" name="website" tabindex="-1" autocomplete="off" class="visually-hidden" aria-hidden="true">

        <div id="inquiryError" class="alert alert--error hidden"></div>
        <div id="inquiryEstimate" class="notice hidden"></div>
        <div>
          <label class="label" for="inquiryItem">Inquiring About</label>
          <input id="inquiryItem" name="item" type="text" readonly class="field field--readonly">
        </div>
        <div>
          <label class="label" for="inquiryName">Full Name <span class="required">*</span></label>
          <input id="inquiryName" name="full_name" type="text" required maxlength="120" value="<?= e($me['full_name'] ?? '') ?>" placeholder="Enter your full name" class="field">
        </div>
        <div class="form-grid form-grid--two">
          <div>
            <label class="label" for="inquiryEmail">Email Address <span class="required">*</span></label>
            <input id="inquiryEmail" name="email" type="email" required value="<?= e($me['email'] ?? '') ?>" placeholder="you@example.com" class="field">
          </div>
          <div>
            <label class="label" for="inquiryPhone">Mobile Number</label>
            <input id="inquiryPhone" name="phone" type="tel" maxlength="30" value="<?= e($me['phone'] ?? '') ?>" placeholder="09XX XXX XXXX" class="field">
          </div>
        </div>
        <div>
          <label class="label" for="inquiryType">Inquiry Type</label>
          <select id="inquiryType" name="inquiry_type" class="field">
            <option>General Inquiry</option>
            <option>Quote &amp; Pricing Request</option>
            <option>Availability Check</option>
            <option>Technical Specifications</option>
          </select>
        </div>
        <div>
          <label class="label" for="inquiryMessage">Message <span class="required">*</span></label>
          <textarea id="inquiryMessage" name="message" required rows="4" maxlength="3000" placeholder="Tell us about your project, site location, and schedule." class="field field--textarea"></textarea>
        </div>
        <div class="dialog__actions">
          <button type="button" onclick="closeInquiry()" class="btn btn--outline btn--lg">Cancel</button>
          <button id="inquirySubmit" type="submit" class="btn btn--amber btn--lg">Submit Inquiry</button>
        </div>
      </form>
    </div>

    <!-- Success view -->
    <div id="inquirySuccess" class="dialog__success hidden">
      <span class="success-icon">
        <iconify-icon icon="lucide:check" width="34"></iconify-icon>
      </span>
      <h2 class="dialog__title dialog__title--lg">Inquiry sent</h2>
      <p class="dialog__success-text">
        Thanks, <span id="inquirySuccessName" class="strong"></span>. We'll reply to
        <span id="inquirySuccessEmail" class="strong"></span> within 24 hours.
      </p>
      <button type="button" onclick="closeInquiry()" class="btn btn--amber btn--lg btn--wide dialog__done">Done</button>
    </div>

  </div>
</div>
