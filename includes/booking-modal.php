<!-- New rental request modal (client portal). Collects everything required at booking.
     The Fee Estimator fills it through window.openBookingWithEstimate() in estimator.js. -->
<div id="bookingModal" class="overlay" role="dialog" aria-modal="true" aria-labelledby="bookingTitle" onclick="if (event.target === this) closeModal('bookingModal')">
  <div class="sheet modal">
    <div class="modal__head">
      <h2 id="bookingTitle" class="modal__title">New Rental Request</h2>
      <button onclick="closeModal('bookingModal')" aria-label="Close" class="modal__close"><iconify-icon icon="lucide:x" width="22"></iconify-icon></button>
    </div>
    <form onsubmit="event.preventDefault(); closeModal('bookingModal');" class="form form--tight modal__form">
      <div class="form-grid">
        <div><label class="label">Full Name</label><input type="text" value="Sarah Jenkins" required class="field"></div>
        <div><label class="label">Contact Details</label><input type="tel" placeholder="Mobile number" required class="field"></div>
      </div>
      <div><label class="label">Equipment</label>
        <select id="bkEquipment" name="equipment" class="field"><option>10-Wheeler Dump Truck</option><option>Backhoe</option></select></div>
      <div><label class="label">Project Site Address</label>
        <input id="bkSite" name="site" type="text" placeholder="Enter full site address" required class="field"></div>
      <div class="form-grid form-grid--dates">
        <div><label class="label">Rental Start</label><input id="bkStart" type="date" required class="field"></div>
        <div><label class="label">Rental End</label><input id="bkEnd" type="date" required class="field"></div>
      </div>
      <div><label class="label">Valid ID</label>
        <input type="file" accept="image/*,.pdf" required class="field field--file"></div>

      <!-- Filled by the Fee Estimator (values come from the estimate result, see estimator.js) -->
      <div id="bkEstimate" class="est-summary hidden">
        <div class="est-summary__title"><iconify-icon icon="lucide:calculator" width="14"></iconify-icon> Estimate attached to this request</div>
        <div class="est-summary__row"><span id="bkSumBase">Equipment rental</span><span id="bkBase"></span></div>
        <div class="est-summary__row"><span id="bkSumHaul">Hauling fee</span><span id="bkHaul"></span></div>
        <div class="est-summary__row"><span id="bkSumWeight">Weight surcharge</span><span id="bkWeight"></span></div>
        <div class="est-summary__row est-summary__row--total"><span>Estimated total</span><span id="bkTotal"></span></div>
        <input type="hidden" name="est_days" id="bkHDays"><input type="hidden" name="est_distance_km" id="bkHKm">
        <input type="hidden" name="est_base" id="bkHBase"><input type="hidden" name="est_haul" id="bkHHaul">
        <input type="hidden" name="est_weight" id="bkHWeight"><input type="hidden" name="est_total" id="bkHTotal">
      </div>

      <div class="note-box">
        <div><iconify-icon icon="lucide:badge-check" width="14"></iconify-icon> A down payment or security deposit is required before your booking is confirmed.</div>
        <div><iconify-icon icon="lucide:route" width="14"></iconify-icon> Delivery / mobilization fees are charged per distance and trip.</div>
        <div><iconify-icon icon="lucide:banknote" width="14"></iconify-icon> Payment is accepted in cash on delivery / on site.</div>
      </div>
      <button type="submit" class="btn btn--amber btn--block">Submit Request</button>
    </form>
  </div>
</div>
