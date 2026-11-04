<!-- Inquiry modal. Open it from anywhere with: onclick="openInquiry('Item name')" -->
<div id="inquiryModal" class="overlay" role="dialog" aria-modal="true" aria-labelledby="inquiryTitle"
     onclick="if (event.target === this) closeInquiry()">
  <div class="sheet modal">

    <!-- Form view -->
    <div id="inquiryFormView">
      <div class="modal__head">
        <div>
          <h2 id="inquiryTitle" class="modal__title">Send an Inquiry</h2>
          <p class="modal__sub">We usually respond within 24 hours.</p>
        </div>
        <button type="button" onclick="closeInquiry()" aria-label="Close" class="modal__close">
          <iconify-icon icon="lucide:x" width="22"></iconify-icon>
        </button>
      </div>

      <form id="inquiryForm" onsubmit="submitInquiry(event)" class="form form--tight modal__form">
        <div>
          <label class="label">Inquiring About</label>
          <input id="inquiryItem" type="text" readonly class="field field--readonly">
        </div>
        <div>
          <label class="label" for="inquiryName">Full Name <span class="req">*</span></label>
          <input id="inquiryName" type="text" required placeholder="Enter your full name" class="field">
        </div>
        <div>
          <label class="label" for="inquiryEmail">Email Address <span class="req">*</span></label>
          <input id="inquiryEmail" type="email" required placeholder="you@example.com" class="field">
        </div>
        <div>
          <label class="label" for="inquiryType">Inquiry Type</label>
          <select id="inquiryType" class="field">
            <option>General Inquiry</option>
            <option>Quote &amp; Pricing Request</option>
          </select>
          <p class="hint">Specs and availability are listed on each service card.</p>
        </div>
        <div>
          <label class="label" for="inquiryMessage">Message <span class="req">*</span></label>
          <textarea id="inquiryMessage" required rows="4" placeholder="Tell us about your project, site location, and schedule." class="field"></textarea>
        </div>
        <div class="modal__actions">
          <button type="button" onclick="closeInquiry()" class="btn btn--outline btn--md">Cancel</button>
          <button type="submit" class="btn btn--amber btn--lg">Submit Inquiry</button>
        </div>
      </form>
    </div>

    <!-- Success view -->
    <div id="inquirySuccess" class="modal__success hidden">
      <span class="success-icon"><iconify-icon icon="lucide:check" width="34"></iconify-icon></span>
      <h2>Inquiry sent</h2>
      <p>Thanks, <strong id="inquirySuccessName"></strong>. We'll reply to <strong id="inquirySuccessEmail"></strong> within 24 hours.</p>
      <button type="button" onclick="closeInquiry()" class="btn btn--amber">Done</button>
    </div>

  </div>
</div>
