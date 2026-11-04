<?php /* Expects $panelHeadline (string with <br>) */ ?>
<div class="auth-panel">
  <!-- Background video (shows on login + register). poster = still image shown while the video loads -->
  <video class="auth-panel__video" autoplay muted loop playsinline >
    <source src="assets/video/hero.mp4" type="video/mp4">
  </video>
  <div class="eyebrow eyebrow--amber">Built on reliability</div>
  <h2><?= $panelHeadline ?></h2>
  <p>Dependable truck rentals. Professional site excavation. </p>
  <div class="auth-panel__points">
    <span>100% certified operators</span>
    <span>Safety-first execution</span>
  </div>
</div>
