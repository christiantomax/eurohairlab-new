<?php

declare(strict_types=1);

$args = isset($args) && is_array($args) ? $args : [];
$row = isset($args['row']) && is_array($args['row']) ? $args['row'] : null;

if ($row === null) {
    return;
}

$popup_id = (int) ($row['id'] ?? 0);
$width_pct = min(100, max(20, (int) ($row['popup_width_percent'] ?? 50)));
$height_px = min(2000, max(200, (int) ($row['popup_height_px'] ?? 500)));
$overlay = min(1, max(0, (float) ($row['overlay_opacity'] ?? 0.5)));

$headline = eh_popup_localized('headline', $row);
$cta_text = eh_popup_localized('cta_text', $row);
$cta_url = isset($row['cta_url']) ? (string) $row['cta_url'] : '';

$image_url = function_exists('eh_popup_resolve_panel_image_url')
    ? eh_popup_resolve_panel_image_url($row)
    : '';

$theme_uri = get_template_directory_uri();
$close_icon_url = $theme_uri . '/assets/images/icons/close-icon-3.svg';
$media_style = $image_url !== ''
    ? sprintf('background-image: url(%s);', esc_url($image_url))
    : '';
?>
<div
  id="eh-popup-root"
  class="eh-popup"
  data-eh-popup
  data-popup-id="<?php echo esc_attr((string) $popup_id); ?>"
  hidden
  aria-hidden="true"
>
  <div class="eh-popup__overlay" data-eh-popup-overlay style="--eh-popup-overlay-opacity: <?php echo esc_attr((string) $overlay); ?>;"></div>
  <div
    class="eh-popup__dialog"
    role="dialog"
    aria-modal="true"
    aria-labelledby="eh-popup-headline"
    data-eh-popup-dialog
    style="--eh-popup-width: <?php echo esc_attr((string) $width_pct); ?>%; --eh-popup-height: <?php echo esc_attr((string) $height_px); ?>px;"
  >
    <div class="eh-popup__close-container">
      <button
        type="button"
        class="eh-popup__close"
        data-eh-popup-close
        aria-label="<?php echo esc_attr__('Close', 'eurohairlab'); ?>"
      >
        <span class="eh-popup__close-btn">
          <img
            class="eh-popup__close-img"
            src="<?php echo esc_url($close_icon_url); ?>"
            width="30"
            height="30"
            alt=""
            decoding="async"
          >
        </span>
      </button>
    </div>
    <div class="eh-popup__layout">
      <div
        class="eh-popup__media<?php echo $image_url !== '' ? ' eh-popup__media--has-image' : ''; ?>"
        <?php echo $media_style !== '' ? 'style="' . esc_attr($media_style) . '"' : ''; ?>
        aria-hidden="true"
      ></div>
      <div class="eh-popup__body">
        <?php if ($headline !== '') : ?>
          <div id="eh-popup-headline" class="eh-popup__headline">
            <?php echo wp_kses_post($headline); ?>
          </div>
        <?php endif; ?>
        <?php if ($cta_url !== '' && $cta_text !== '') : ?>
          <p class="eh-popup__cta-wrap">
            <a class="eh-popup__cta" href="<?php echo esc_url($cta_url); ?>"><?php echo esc_html($cta_text); ?></a>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
