(function () {
  const config = window.ehPopup || {};
  const root = document.querySelector("[data-eh-popup]");
  if (!root) {
    return;
  }

  const overlay = root.querySelector("[data-eh-popup-overlay]");
  const dialog = root.querySelector("[data-eh-popup-dialog]");
  const closeButtons = root.querySelectorAll("[data-eh-popup-close]");
  const delayMs = Number(config.delayMs) || 0;
  const closeOnEsc = Boolean(config.closeOnEsc);

  let viewRecorded = false;

  const syncViewportLayout = () => {
    const w = window.innerWidth;
    const h = window.innerHeight;
    const landscape = w > h;
    const phone = w <= 767;
    const tabletLandscape = landscape && w >= 768 && w <= 1366 && h <= 900;
    const tabletPortrait = !landscape && w <= 1024;

    root.classList.toggle("is-mobile", phone);
    root.classList.toggle("is-tablet-landscape", tabletLandscape);
    root.classList.toggle("is-tablet-portrait", tabletPortrait && !phone);

    if (!dialog) {
      return;
    }

    if (tabletLandscape) {
      dialog.style.setProperty("--eh-popup-width", "75%");
      dialog.style.width = "75%";
      dialog.style.maxWidth = "calc(100vw - 2rem)";
      return;
    }

    dialog.style.removeProperty("width");
    dialog.style.removeProperty("max-width");
  };

  syncViewportLayout();
  window.addEventListener("resize", syncViewportLayout, { passive: true });

  const recordView = () => {
    if (viewRecorded || !config.ajaxUrl || !config.popupId) {
      return;
    }
    viewRecorded = true;

    const body = new URLSearchParams();
    body.set("action", "eh_popup_record_view");
    body.set("nonce", config.nonce || "");
    body.set("popupId", String(config.popupId));

    fetch(config.ajaxUrl, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body: body.toString(),
    }).catch(() => {});
  };

  const open = () => {
    root.hidden = false;
    root.setAttribute("aria-hidden", "false");
    root.classList.add("is-open");
    document.body.classList.add("eh-popup-open");
    recordView();
  };

  const close = () => {
    root.classList.add("is-closing");
    window.setTimeout(() => {
      root.classList.remove("is-open", "is-closing");
      root.hidden = true;
      root.setAttribute("aria-hidden", "true");
      document.body.classList.remove("eh-popup-open");
    }, 320);
  };

  closeButtons.forEach((btn) => {
    btn.addEventListener("click", close);
  });

  if (overlay) {
    overlay.addEventListener("click", close);
  }

  if (closeOnEsc) {
    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && root.classList.contains("is-open")) {
        close();
      }
    });
  }

  const scheduleOpen = () => {
    if (delayMs > 0) {
      window.setTimeout(open, delayMs);
    } else {
      open();
    }
  };

  if (config.trigger === "pageLoaded") {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", scheduleOpen, { once: true });
    } else {
      scheduleOpen();
    }
  }
})();
