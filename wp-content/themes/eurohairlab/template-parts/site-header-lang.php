<?php

declare(strict_types=1);

$current = function_exists('eurohairlab_get_public_lang') ? eurohairlab_get_public_lang() : 'en';
$current = $current === 'id' ? 'id' : 'en';
$trigger_label = strtoupper($current);
$url_en = function_exists('eurohairlab_get_public_lang_switch_url') ? eurohairlab_get_public_lang_switch_url('en') : '#';
$url_id = function_exists('eurohairlab_get_public_lang_switch_url') ? eurohairlab_get_public_lang_switch_url('id') : '#';
$menu_id = 'eh-lang-select-menu';
$trigger_id = 'eh-lang-select-trigger';
?>
<div class="eh-lang-select shrink-0" data-eh-lang-select>
  <button
    type="button"
    id="<?php echo esc_attr($trigger_id); ?>"
    class="eh-lang-select__trigger"
    aria-expanded="false"
    aria-haspopup="true"
    aria-controls="<?php echo esc_attr($menu_id); ?>"
  >
    <span class="eh-lang-select__value"><?php echo esc_html($trigger_label); ?></span>
    <span class="eh-lang-select__chevron" aria-hidden="true">
      <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M2.5 4.25 6 7.75 9.5 4.25" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </span>
  </button>
  <div
    id="<?php echo esc_attr($menu_id); ?>"
    class="eh-lang-select__menu"
    role="menu"
    aria-labelledby="<?php echo esc_attr($trigger_id); ?>"
  >
    <a role="menuitem" class="eh-lang-select__option<?php echo $current === 'en' ? ' is-active' : ''; ?>" href="<?php echo esc_url($url_en); ?>"><?php echo esc_html__('English', 'eurohairlab'); ?></a>
    <a role="menuitem" class="eh-lang-select__option<?php echo $current === 'id' ? ' is-active' : ''; ?>" href="<?php echo esc_url($url_id); ?>"><?php echo esc_html__('Bahasa Indonesia', 'eurohairlab'); ?></a>
  </div>
</div>
