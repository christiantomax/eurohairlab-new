<?php

declare(strict_types=1);

/**
 * Contact map card — Figma 4035:604 typography (Tailwind: font-futuraHv / font-futuraBk, eh-ink / black).
 */

$theme_uri = esc_url(get_template_directory_uri());
$location_icon_uri = $theme_uri . '/assets/contact-location-icon.png';
$hours_icon_uri = $theme_uri . '/assets/contact-hours-icon.png';
$page_id = get_queried_object_id();
$mb_get = static function (string $key) use ($page_id) {
    if (!$page_id || !function_exists('rwmb_meta')) {
        return null;
    }

    return rwmb_meta($key, [], $page_id);
};
$resolve_link = static function ($value, string $fallback = ''): string {
    $value = is_string($value) ? trim($value) : '';

    if ($value === '') {
        return $fallback;
    }

    if (
        str_starts_with($value, 'http://')
        || str_starts_with($value, 'https://')
        || str_starts_with($value, 'mailto:')
        || str_starts_with($value, 'tel:')
        || str_starts_with($value, '#')
    ) {
        return $value;
    }

    if (str_starts_with($value, '/')) {
        return home_url($value);
    }

    return home_url('/' . ltrim($value, '/'));
};

$contact_default = [
    'map_embed_url' => 'https://www.google.com/maps/d/u/0/embed?mid=1J8SoXlVhDI_sC2xNRYTBhqiw3h5hWVU&ehbc=2E312F&noprof=1',
    'hours_title' => 'Operating Hours',
    'operating_hours' => '09.00 - 21.00',
    'whatsapp_text' => 'WhatsApp Consultation',
    'whatsapp_href' => 'https://wa.me/62215550188',
    'appointment_text' => 'Book Appointment',
    'appointment_href' => eurohairlab_get_primary_cta_url(),
];

$contact = array_merge($contact_default, array_filter([
    'map_embed_url' => $mb_get('eh_contact_map_embed_url'),
    'hours_title' => $mb_get('eh_contact_hours_title'),
    'operating_hours' => $mb_get('eh_contact_operating_hours'),
    'whatsapp_text' => $mb_get('eh_contact_whatsapp_text'),
    'whatsapp_href' => $resolve_link((string) $mb_get('eh_contact_whatsapp_href'), $contact_default['whatsapp_href']),
    'appointment_text' => $mb_get('eh_contact_appointment_text'),
    'appointment_href' => $resolve_link((string) $mb_get('eh_contact_appointment_href'), $contact_default['appointment_href']),
], static fn($value) => $value !== null && $value !== ''));
?>
<main id="main-content" class="bg-white">
  <section class="relative overflow-hidden bg-white pt-28 sm:pt-32 lg:pt-[6rem]">
    <div class="relative min-h-[29rem] sm:min-h-[36rem] lg:min-h-[49.8rem]">
      <iframe
        src="<?php echo esc_url($contact['map_embed_url']); ?>"
        title="Eurohairlab clinic location map"
        class="absolute inset-0 h-full w-full border-0"
        width="1440"
        height="897"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
      ></iframe>

      <div class="pointer-events-none relative z-10 hidden min-h-[29rem] items-end px-4 pb-10 sm:min-h-[36rem] sm:px-5 sm:pb-12 lg:flex lg:min-h-[49.8rem] lg:px-[6.1rem] lg:pb-[4.45rem]">
        <article class="pointer-events-auto reveal ml-auto w-full max-w-[29.75rem] border border-black bg-white px-6 py-6 text-eh-ink shadow-[0_18px_50px_rgba(32,28,32,0.12)] sm:px-7 sm:py-7 lg:px-9 lg:py-8 lg:mb-10">
          <h1 class="flex items-center gap-3 font-futuraHv text-[24px] font-normal capitalize leading-none text-eh-ink">
            <img
              src="<?php echo esc_url($location_icon_uri); ?>"
              alt=""
              class="h-4 w-4 object-contain"
              width="16"
              height="16"
              loading="lazy"
              decoding="async"
            >
            <span>Clinic Location</span>
          </h1>

          <p class="mt-5 flex items-center gap-3 font-futuraHv text-[24px] font-normal capitalize leading-none text-eh-ink">
            <img
              src="<?php echo esc_url($hours_icon_uri); ?>"
              alt=""
              class="h-4 w-4 object-contain"
              width="16"
              height="16"
              loading="lazy"
              decoding="async"
            >
            <span><?php echo esc_html($contact['hours_title']); ?></span>
          </p>

          <p class="mt-5 pl-8 font-futuraBk text-[18px] font-normal capitalize leading-[120%] text-eh-ink"><?php echo esc_html($contact['operating_hours']); ?></p>

          <div class="mt-8 grid gap-4 pl-8">
            <a
              target="_blank"
              rel="noopener noreferrer"
              href="<?php echo esc_url($contact['whatsapp_href']); ?>"
              class="inline-flex min-h-4 items-center justify-center border border-black px-5 py-3 text-center font-futuraBk text-[18px] font-normal capitalize leading-[120%] text-black transition hover:bg-black hover:text-white"
            >
              <?php echo esc_html($contact['whatsapp_text']); ?>
            </a>
            <a
              href="<?php echo esc_url($contact['appointment_href']); ?>"
              class="inline-flex min-h-4 items-center justify-center border border-black px-5 py-3 text-center font-futuraBk text-[18px] font-normal capitalize leading-[120%] text-black transition hover:bg-black hover:text-white"
            >
              <?php echo esc_html($contact['appointment_text']); ?>
            </a>
          </div>
        </article>
      </div>
    </div>
    <div class="px-4 pb-10 pt-6 sm:px-5 sm:pb-12 lg:hidden">
      <article class="reveal w-full border border-black bg-white px-6 py-6 text-eh-ink shadow-[0_18px_50px_rgba(32,28,32,0.12)] sm:px-7 sm:py-7">
        <h1 class="flex items-center gap-3 font-futuraHv text-[24px] font-normal capitalize leading-none text-eh-ink">
          <img
            src="<?php echo esc_url($location_icon_uri); ?>"
            alt=""
            class="h-4 w-4 object-contain"
            width="16"
            height="16"
            loading="lazy"
            decoding="async"
          >
          <span>Clinic Location</span>
        </h1>

        <p class="mt-5 flex items-center gap-3 font-futuraHv text-[24px] font-normal capitalize leading-none text-eh-ink">
          <img
            src="<?php echo esc_url($hours_icon_uri); ?>"
            alt=""
            class="h-4 w-4 object-contain"
            width="16"
            height="16"
            loading="lazy"
            decoding="async"
          >
          <span><?php echo esc_html($contact['hours_title']); ?></span>
        </p>

        <p class="mt-5 pl-8 font-futuraBk text-[18px] font-normal capitalize leading-[120%] text-eh-ink"><?php echo esc_html($contact['operating_hours']); ?></p>

        <div class="mt-8 grid gap-4 pl-8">
          <a
            target="_blank"
            rel="noopener noreferrer"
            href="<?php echo esc_url($contact['whatsapp_href']); ?>"
            class="inline-flex min-h-4 items-center justify-center border border-black px-5 py-3 text-center font-futuraBk text-[18px] font-normal capitalize leading-[120%] text-black transition hover:bg-black hover:text-white"
          >
            <?php echo esc_html($contact['whatsapp_text']); ?>
          </a>
          <a
            href="<?php echo esc_url($contact['appointment_href']); ?>"
            class="inline-flex min-h-4 items-center justify-center border border-black px-5 py-3 text-center font-futuraBk text-[18px] font-normal capitalize leading-[120%] text-black transition hover:bg-black hover:text-white"
          >
            <?php echo esc_html($contact['appointment_text']); ?>
          </a>
        </div>
      </article>
    </div>
  </section>
</main>
