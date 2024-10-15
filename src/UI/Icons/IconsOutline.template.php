<?php $strokeWidth = $options['stroke-width'] ?? '1.5'; ?>

<?php if ($icon == 'book-open') { ?>

<svg class="<?= $class; ?>"  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
</svg>

<?php } elseif ($icon == 'academic-cap') { ?>

<svg class="<?= $class; ?>" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
  </svg>
  
<?php } elseif ($icon == 'user') { ?>
    <svg class="<?= $class; ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
    </svg>

<?php } elseif ($icon == 'users' || $icon == 'meet the teacher') { ?>

<svg class="<?= $class; ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
</svg>

<?php } elseif ($icon == 'user-group') { ?>

<svg class="<?= $class; ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" aria-hidden="true">
  <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
</svg>

<?php } elseif ($icon == 'chat-bubble-text') { ?>

<svg class="<?= $class; ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" >
    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
</svg> 

<?php } elseif ($icon == 'calendar') { ?>
    <svg class="<?= $class; ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
    </svg>
  
<?php } elseif ($icon == 'clock') { ?>
<svg class="<?= $class; ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" aria-hidden="true">
<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
</svg>
  
<?php } elseif ($icon == 'star') { ?>

<svg class="<?= $class; ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" >
    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
</svg>
  
<?php } elseif ($icon == 'trophy' || $icon == 'house points') { ?>

<svg class="<?= $class; ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
  </svg>
  
<?php } elseif ($icon == 'badge' || $icon == 'badges') { ?>

<svg class="<?= $class; ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" aria-hidden="true">
<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
</svg>

<?php } elseif ($icon == 'share' || $icon == 'free learning') { ?>

<svg class="<?= $class; ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
  </svg>

<?php } elseif ($icon == 'link' || $icon == 'staff information' or $icon == 'student information' or $icon == 'parent information') { ?>

<svg class="<?= $class; ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
</svg>

<?php } elseif ($icon == 'lightbulb' || $icon == 'atl') { ?>

<svg class="<?= $class; ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
</svg>

<?php } elseif ($icon == 'help' || $icon == 'question-mark-circle') { ?>

<svg class="<?= $class; ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" aria-hidden="true">
  <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
</svg>

<?php } elseif ($icon == 'squares') { ?>

<svg class="<?= $class; ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 8.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v8.25A2.25 2.25 0 0 0 6 16.5h2.25m8.25-8.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-7.5A2.25 2.25 0 0 1 8.25 18v-1.5m8.25-8.25h-6a2.25 2.25 0 0 0-2.25 2.25v6" />
</svg>

<?php } elseif ($icon == 'zoom' || $icon == 'magnifying-glass-plus' ) { ?>

<svg class="<?= $class; ?>" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607ZM10.5 7.5v6m3-3h-6" />
</svg>

<?php } elseif ($icon == 'gift') { ?>

<svg class="<?= $class; ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" aria-hidden="true">
  <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
</svg>

<?php } elseif ($icon == 'shield-check') { ?>

<svg class="<?= $class; ?>" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="<?= $strokeWidth; ?>" stroke="currentColor" aria-hidden="true">
  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
</svg>

<?php } elseif ($icon == 'notifications') { ?>
<svg class="<?= $class; ?>" style="max-width: 2.5rem;" x="0px" y="0px" viewBox="0 0 100 100" style="enable-background:new 0 0 100 100;" aria-hidden="true"><g i:extraneous="self"><path d="M87.5,67.2L83,61.8c-2.8-3.4-4.3-7.7-4.3-12.1V39.6c0-14.6-11-26.7-25.1-28.5v-5c0-2-1.6-3.6-3.6-3.6    c-2,0-3.6,1.6-3.6,3.6v5C32.3,12.9,21.3,25,21.3,39.6v10.1c0,4.4-1.5,8.7-4.3,12.1l-4.4,5.4c-2.3,2.8-2.8,6.7-1.2,10    c1.6,3.3,4.8,5.4,8.5,5.4h13.8c0.8,8.4,7.8,14.9,16.4,14.9s15.6-6.6,16.4-14.9h13.8c3.7,0,6.9-2.1,8.5-5.4    C90.2,73.9,89.8,70.1,87.5,67.2z M50,90.3c-4.6,0-8.4-3.4-9.1-7.8h18.2C58.4,86.9,54.6,90.3,50,90.3z M82.2,74.1    c-0.2,0.4-0.7,1.3-2,1.3H19.8c-1.3,0-1.8-0.9-2-1.3s-0.5-1.4,0.3-2.4l4.4-5.4c3.8-4.7,5.9-10.6,5.9-16.7V39.6    C28.5,27.7,38.1,18,50,18s21.5,9.7,21.5,21.5v10.1c0,6.1,2.1,12,5.9,16.7l4.4,5.4C82.7,72.7,82.4,73.7,82.2,74.1z"/></g></svg>
    
<?php } elseif ($icon == 'message-wall') { ?>
<svg class="<?= $class; ?>" style="max-width: 2.5rem;" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" aria-hidden="true"><g><path d="M85,20H61.962l-13.218-9.441c-0.914-0.653-2.117-0.74-3.116-0.226C44.628,10.847,44,11.876,44,13v7h-8    c-1.656,0-3,1.343-3,3v9.27H15c-1.656,0-3,1.343-3,3v40.235c0,1.656,1.344,3,3,3h26.628L56.94,89.442    C57.459,89.812,58.069,90,58.685,90c0.469,0,0.939-0.11,1.372-0.332c1-0.515,1.628-1.544,1.628-2.668v-8.495H74    c1.657,0,3-1.344,3-3V61h8c1.657,0,3-1.343,3-3V23C88,21.343,86.657,20,85,20z M71,72.505H58.685c-1.657,0-3,1.343-3,3v5.666    l-11.351-8.107c-0.509-0.363-1.118-0.559-1.744-0.559H18V38.27h53V72.505z M82,55h-5V35.27c0-1.657-1.343-3-3-3H39V26h8    c1.657,0,3-1.343,3-3v-4.17l9.256,6.612C59.765,25.805,60.374,26,61,26h21V55z"/><path d="M30,57.81c0.79,0,1.561-0.318,2.12-0.88C32.68,56.37,33,55.6,33,54.81s-0.32-1.56-0.88-2.12c-1.12-1.12-3.131-1.12-4.24,0    C27.319,53.25,27,54.02,27,54.81s0.319,1.561,0.88,2.12C28.44,57.491,29.21,57.81,30,57.81z"/><path d="M44.94,57.81c0.79,0,1.569-0.318,2.12-0.88c0.56-0.56,0.88-1.33,0.88-2.12s-0.32-1.56-0.88-2.12    c-1.11-1.12-3.12-1.12-4.241,0c-0.549,0.561-0.879,1.33-0.879,2.12s0.319,1.561,0.879,2.12C43.38,57.491,44.16,57.81,44.94,57.81z    "/><path d="M59.89,57.81c0.79,0,1.561-0.318,2.12-0.88c0.56-0.56,0.88-1.33,0.88-2.12s-0.32-1.56-0.88-2.12    c-1.12-1.12-3.13-1.109-4.239,0c-0.561,0.561-0.881,1.33-0.881,2.12s0.32,1.57,0.881,2.12C58.33,57.491,59.101,57.81,59.89,57.81z    "/></g></svg>

<?php } ?>
