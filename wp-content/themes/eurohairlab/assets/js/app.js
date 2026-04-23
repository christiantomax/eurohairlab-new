const menuToggle = document.getElementById("menu-toggle");
const mobileMenu = document.getElementById("mobile-menu");
const siteHeader = document.getElementById("site-header");
const siteHeaderLogo = document.querySelector(".site-header__logo");
const forceDarkLogo = siteHeader?.dataset.forceDarkLogo === "true";

if (menuToggle && mobileMenu) {
  const setMenuState = (isOpen) => {
    menuToggle.setAttribute("aria-expanded", String(isOpen));
    mobileMenu.classList.toggle("hidden", !isOpen);
    siteHeader?.classList.toggle("is-menu-open", isOpen);
    document.body.classList.toggle("overflow-hidden", isOpen);
  };

  menuToggle.addEventListener("click", () => {
    const isExpanded = menuToggle.getAttribute("aria-expanded") === "true";
    setMenuState(!isExpanded);
  });

  mobileMenu.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
      setMenuState(false);
    });
  });
}

const syncHeaderState = () => {
  if (!siteHeader) {
    return;
  }

  const isScrolled = window.scrollY > 24;
  const scrollBottom = window.scrollY + window.innerHeight;
  const pageBottom = document.documentElement.scrollHeight;
  const isAtPageBottom = scrollBottom >= pageBottom - 8;
  const assessmentPage = document.getElementById("assessment-page");
  const assessmentCompleteVisible = Boolean(
    assessmentPage && !assessmentPage.querySelector('[data-assessment-screen="complete"]')?.classList.contains("hidden")
  );
  const isMenuOpen = menuToggle?.getAttribute("aria-expanded") === "true";

  siteHeader.classList.toggle("is-scrolled", isScrolled);
  siteHeader.classList.toggle("is-hidden", isAtPageBottom && !assessmentCompleteVisible);
  siteHeaderLogo?.classList.toggle("brightness-0", forceDarkLogo || isScrolled || isMenuOpen);
};

syncHeaderState();
window.addEventListener("scroll", syncHeaderState, { passive: true });

const revealItems = document.querySelectorAll(".reveal");
const revealObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("is-visible");
        revealObserver.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.08, rootMargin: "0px 0px -8% 0px" }
);

revealItems.forEach((item) => revealObserver.observe(item));

const rangeInput = document.getElementById("comparison-slider");
const overlay = document.querySelector(".before-after-overlay");
const divider = document.querySelector(".before-after-line");

const syncComparison = (value) => {
  if (!overlay || !divider) {
    return;
  }

  overlay.style.clipPath = `inset(0 ${100 - value}% 0 0)`;
  divider.style.left = `${value}%`;
};

if (rangeInput) {
  syncComparison(rangeInput.value);
  rangeInput.addEventListener("input", (event) => {
    syncComparison(event.target.value);
  });
}

const lightbox = document.getElementById("image-lightbox");
const lightboxImage = document.getElementById("lightbox-image");
const lightboxTitle = document.getElementById("lightbox-title");
const lightboxClose = document.getElementById("lightbox-close");
const lightboxTriggers = document.querySelectorAll(".image-lightbox-trigger");

const closeLightbox = () => {
  lightbox?.classList.add("hidden");
  document.body.classList.remove("overflow-hidden");
};

lightboxTriggers.forEach((trigger) => {
  trigger.addEventListener("click", () => {
    if (!lightbox || !lightboxImage || !lightboxTitle) {
      return;
    }

    lightboxImage.src = trigger.dataset.image || "";
    lightboxImage.alt = trigger.dataset.title || "Expanded image";
    lightboxTitle.textContent = trigger.dataset.title || "";
    lightbox.classList.remove("hidden");
    document.body.classList.add("overflow-hidden");
  });
});

lightboxClose?.addEventListener("click", closeLightbox);

lightbox?.addEventListener("click", (event) => {
  if (event.target === lightbox) {
    closeLightbox();
  }
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    closeLightbox();
  }
});

const initHomepageHeroSlider = () => {
  const $ = window.jQuery;

  if (!$?.fn?.slick) {
    return;
  }

  const heroSlider = document.querySelector("[data-homepage-hero-slider]");

  if (!heroSlider || heroSlider.classList.contains("slick-initialized")) {
    return;
  }

  const heroSlideCount = heroSlider.querySelectorAll(":scope > article").length;
  const heroDotsHost = document.getElementById("homepage-hero-dots");
  const showHeroDots = heroSlideCount > 1 && Boolean(heroDotsHost);

  /** @type {Record<string, unknown>} */
  const heroSlickOptions = {
    arrows: false,
    dots: showHeroDots,
    infinite: heroSlideCount > 1,
    speed: 750,
    autoplay: heroSlideCount > 1,
    autoplaySpeed: 2500,
    pauseOnHover: true,
    pauseOnFocus: true,
    fade: true,
    cssEase: "ease",
    slidesToShow: 1,
    slidesToScroll: 1,
  };

  if (showHeroDots && heroDotsHost) {
    heroSlickOptions.appendDots = $(heroDotsHost);
  }

  $(heroSlider).slick(heroSlickOptions);
};

const initHomeProgramsSlider = () => {
  const $ = window.jQuery;

  if (!$?.fn?.slick) {
    return;
  }

  const slider = document.querySelector("[data-programs-home-slider]");

  if (!slider || slider.classList.contains("slick-initialized")) {
    return;
  }

  const slideCount = slider.children.length;
  const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  const syncProgramsHomeSlideA11y = () => {
    const slickSlides = slider.querySelectorAll(".slick-slide");
    slickSlides.forEach((slide) => {
      const isHidden = slide.getAttribute("aria-hidden") === "true";
      slide.toggleAttribute("inert", isHidden);
    });
  };

  const blurFocusedDescendant = () => {
    const activeElement = document.activeElement;
    if (activeElement && slider.contains(activeElement)) {
      activeElement.blur();
    }
  };

  $(slider).on("beforeChange", () => {
    blurFocusedDescendant();
  });

  $(slider).on("init reInit afterChange", () => {
    syncProgramsHomeSlideA11y();
  });

  $(slider).slick({
    variableWidth: true,
    arrows: false,
    dots: false,
    infinite: true,
    speed: 550,
    autoplay: true,
    autoplaySpeed: 4800,
    pauseOnHover: true,
    pauseOnFocus: true,
    swipe: slideCount > 1,
    draggable: slideCount > 1,
    touchMove: slideCount > 1,
    swipeToSlide: true,
    slidesToShow: 1,
    slidesToScroll: 1,
    adaptiveHeight: false,
    centerMode: true,
    centerPadding: '60px',
  });

  const pauseAutoplay = () => {
    if (slider.classList.contains("slick-initialized")) {
      $(slider).slick("slickPause");
    }
  };

  const resumeAutoplay = () => {
    if (slider.classList.contains("slick-initialized")) {
      $(slider).slick("slickPlay");
    }
  };

  slider.addEventListener("focusin", pauseAutoplay);
  slider.addEventListener("focusout", (event) => {
    const nextTarget = event.relatedTarget;
    if (!nextTarget || !slider.contains(nextTarget)) {
      resumeAutoplay();
    }
  });

  syncProgramsHomeSlideA11y();

  if (slideCount < 2) {
    document.querySelectorAll("[data-programs-home-prev], [data-programs-home-next]").forEach((button) => {
      button.classList.add("hidden");
      button.setAttribute("aria-hidden", "true");
      button.setAttribute("tabindex", "-1");
    });
  }

  document.querySelectorAll("[data-programs-home-prev]").forEach((button) => {
    button.addEventListener("click", () => {
      $(slider).slick("slickPrev");
    });
  });

  document.querySelectorAll("[data-programs-home-next]").forEach((button) => {
    button.addEventListener("click", () => {
      $(slider).slick("slickNext");
    });
  });

  window.addEventListener("load", () => {
    $(slider).slick("setPosition");
  });

  window.addEventListener("resize", () => {
    $(slider).slick("setPosition");
  });
};

document.querySelectorAll("[data-accordion-button]").forEach((button) => {
  button.addEventListener("click", () => {
    const panel = button.parentElement?.querySelector("[data-accordion-panel]");
    const icon = button.lastElementChild;
    const isExpanded = button.getAttribute("aria-expanded") === "true";

    button.setAttribute("aria-expanded", String(!isExpanded));
    panel?.classList.toggle("hidden", isExpanded);

    if (icon) {
      icon.textContent = isExpanded ? "+" : "−";
    }
  });
});

const initAboutSliders = () => {
  const $ = window.jQuery;

  if (!$?.fn?.slick) {
    return;
  }

  const techSlider = document.querySelector("[data-about-tech-slider]");

  if (techSlider && !techSlider.classList.contains("slick-initialized")) {
    const techCurrent = document.querySelectorAll("[data-about-tech-current]");
    const techTotal = document.querySelectorAll("[data-about-tech-total]");

    $(techSlider).on("init reInit afterChange", (_event, slick, currentSlide) => {
      const currentIndex = typeof currentSlide === "number" ? currentSlide : 0;

      techCurrent.forEach((item) => {
        item.textContent = String(currentIndex + 1);
      });

      techTotal.forEach((item) => {
        item.textContent = String(slick.slideCount);
      });
    });

    $(techSlider).slick({
      arrows: false,
      dots: false,
      infinite: true,
      speed: 550,
      autoplay: false,
      autoplaySpeed: 2500,
      pauseOnHover: true,
      pauseOnFocus: true,
      swipe: true,
      draggable: true,
      touchMove: true,
      swipeToSlide: true,
      slidesToShow: 1,
      slidesToScroll: 1,
      adaptiveHeight: true,
    });

    window.addEventListener("load", () => {
      $(techSlider).slick("setPosition");
    });

    window.addEventListener("resize", () => {
      $(techSlider).slick("setPosition");
    });

    document.querySelectorAll("[data-about-tech-prev]").forEach((button) => {
      button.addEventListener("click", () => {
        $(techSlider).slick("slickPrev");
      });
    });

    document.querySelectorAll("[data-about-tech-next]").forEach((button) => {
      button.addEventListener("click", () => {
        $(techSlider).slick("slickNext");
      });
    });
  }

  const privacySlider = document.querySelector("[data-about-privacy-slider]");

  if (privacySlider && !privacySlider.classList.contains("slick-initialized")) {
    $(privacySlider).slick({
      arrows: false,
      dots: false,
      infinite: true,
      speed: 550,
      autoplay: true,
      autoplaySpeed: 2500,
      pauseOnHover: true,
      pauseOnFocus: true,
      swipe: true,
      draggable: true,
      touchMove: true,
      swipeToSlide: true,
      slidesToShow: 3,
      slidesToScroll: 1,
      initialSlide: 0,
      responsive: [
        {
          breakpoint: 1024,
          settings: {
            slidesToShow: 2,
          },
        },
        {
          breakpoint: 640,
          settings: {
            slidesToShow: 1,
          },
        },
      ],
    });
  }
};

const initAboutPartnership = () => {
  const section = document.querySelector("[data-about-partnership-section]");

  if (!section) {
    return;
  }

  const bioContainer = section.querySelector("[data-partnership-active-bio]");
  const activeName = section.querySelector("[data-partnership-active-name]");
  const activeTitle = section.querySelector("[data-partnership-active-title]");
  const cards = Array.from(section.querySelectorAll("[data-partnership-card]"));

  if (!bioContainer || !activeName || !activeTitle || cards.length === 0) {
    return;
  }

  const renderBio = (rawBio) => {
    const lines = String(rawBio || "")
      .split(/\r?\n/)
      .map((line) => line.trim())
      .filter(Boolean);

    bioContainer.replaceChildren(
      ...lines.map((line) => {
        const p = document.createElement("p");
        p.textContent = line;
        return p;
      })
    );
  };

  const imageCache = new Map();

  const preloadImage = (src) => {
    if (!src) {
      return Promise.resolve(false);
    }

    if (imageCache.has(src)) {
      return imageCache.get(src);
    }

    const promise = new Promise((resolve) => {
      const image = new Image();
      image.decoding = "async";
      image.onload = () => resolve(true);
      image.onerror = () => resolve(false);
      image.src = src;
    });

    imageCache.set(src, promise);
    return promise;
  };

  const prepareCardImageLayer = (card) => {
    const baseImg = card.querySelector("[data-partnership-card-image]");
    if (!baseImg || card.querySelector("[data-partnership-card-overlay]")) {
      return null;
    }

    const overlayImg = baseImg.cloneNode(true);
    overlayImg.removeAttribute("data-partnership-card-image");
    overlayImg.setAttribute("data-partnership-card-overlay", "");
    overlayImg.alt = "";
    overlayImg.setAttribute("aria-hidden", "true");
    overlayImg.tabIndex = -1;
    overlayImg.classList.add("about-partnership-card__image--overlay");
    overlayImg.style.opacity = "0";
    overlayImg.style.pointerEvents = "none";
    card.appendChild(overlayImg);

    baseImg.classList.add("about-partnership-card__image--base");
    return { baseImg, overlayImg };
  };

  const setCardImage = async (card, useHover) => {
    const layers = prepareCardImageLayer(card);
    const baseImg = layers?.baseImg ?? card.querySelector("[data-partnership-card-image]");
    const overlayImg = layers?.overlayImg ?? card.querySelector("[data-partnership-card-overlay]");

    if (!baseImg || !overlayImg) {
      return;
    }

    const img = card.querySelector("[data-partnership-card-image]");
    const mainSrc = card.dataset.partnershipMainSrc || "";
    const hoverSrc = card.dataset.partnershipHoverSrc || mainSrc;
    const nextSrc = useHover ? hoverSrc : mainSrc;
    const currentSrc = baseImg.getAttribute("src") || "";

    if (!nextSrc || nextSrc === currentSrc) {
      return;
    }

    const token = String(Number(card.dataset.partnershipImageToken || "0") + 1);
    card.dataset.partnershipImageToken = token;

    await preloadImage(nextSrc);

    if (card.dataset.partnershipImageToken !== token) {
      return;
    }

    overlayImg.src = nextSrc;
    overlayImg.classList.add("is-visible");
  };

  const clearCardOverlayImage = (card) => {
    const overlayImg = card.querySelector("[data-partnership-card-overlay]");
    const token = String(Number(card.dataset.partnershipImageToken || "0") + 1);
    card.dataset.partnershipImageToken = token;
    if (!overlayImg) {
      return;
    }

    overlayImg.classList.remove("is-visible");
    window.setTimeout(() => {
      if (card.dataset.partnershipImageToken === token) {
        overlayImg.src = "";
      }
    }, 550);
  };

  const setCardBaseImage = async (card, useHover) => {
    const baseImg = card.querySelector("[data-partnership-card-image]");
    if (!baseImg) {
      return;
    }

    const mainSrc = card.dataset.partnershipMainSrc || "";
    const hoverSrc = card.dataset.partnershipHoverSrc || mainSrc;
    const nextSrc = useHover ? hoverSrc : mainSrc;
    if (!nextSrc) {
      return;
    }

    const currentSrc = baseImg.getAttribute("src") || "";
    if (currentSrc === nextSrc) {
      return;
    }

    const token = String(Number(card.dataset.partnershipBaseToken || "0") + 1);
    card.dataset.partnershipBaseToken = token;
    await preloadImage(nextSrc);
    if (card.dataset.partnershipBaseToken !== token) {
      return;
    }

    baseImg.setAttribute("src", nextSrc);
  };

  let activeIndex = 0;
  // Default active card (index 0) should also use hover image as active marker.
  let pinnedIndex = activeIndex;

  cards.forEach((card) => {
    prepareCardImageLayer(card);
  });

  const setActive = (index) => {
    const card = cards[index];
    if (!card) {
      return;
    }

    activeName.textContent = card.dataset.partnershipName || "";
    activeTitle.textContent = card.dataset.partnershipTitle || "";
    renderBio(card.dataset.partnershipBio || "");

    cards.forEach((item, itemIndex) => {
      const isActive = itemIndex === index;
      item.classList.toggle("is-active", isActive);
      item.setAttribute("aria-pressed", String(isActive));
    });
  };

  const syncPinnedCardMarker = () => {
    cards.forEach((card, index) => {
      void setCardBaseImage(card, pinnedIndex === index);
    });
  };

  cards.forEach((card, index) => {
    card.addEventListener("mouseenter", () => {
      void setCardImage(card, true);
      setActive(index);
    });

    card.addEventListener("mouseleave", () => {
      clearCardOverlayImage(card);
      setActive(activeIndex);
      syncPinnedCardMarker();
    });

    card.addEventListener("focus", () => {
      void setCardImage(card, true);
      setActive(index);
    });

    card.addEventListener("blur", () => {
      clearCardOverlayImage(card);
      setActive(activeIndex);
      syncPinnedCardMarker();
    });

    card.addEventListener("click", () => {
      activeIndex = index;
      pinnedIndex = index;
      clearCardOverlayImage(card);
      setActive(index);
      syncPinnedCardMarker();
    });
  });

  setActive(activeIndex);
  syncPinnedCardMarker();
};

const initBlogFilterSlider = () => {
  const $ = window.jQuery;

  if (!$?.fn?.slick) {
    return;
  }

  const filterSlider = document.querySelector("[data-blog-filter-slider]");
  const filterRail = filterSlider?.closest(".blog-filter-rail");

  if (!filterSlider || filterSlider.classList.contains("slick-initialized")) {
    return;
  }

  $(filterSlider).slick({
    arrows: false,
    dots: false,
    infinite: false,
    autoplay: true,
    autoplaySpeed: 2500,
    pauseOnHover: true,
    pauseOnFocus: true,
    variableWidth: true,
    slidesToShow: 1,
    draggable: true,
    swipe: true,
    touchMove: true,
    swipeToSlide: true,
    touchThreshold: 12,
    mobileFirst: true,
    responsive: [
      {
        breakpoint: 1024,
        settings: "unslick",
      },
    ],
  });

  filterRail?.classList.add("is-slick-active");

  window.addEventListener("resize", () => {
    if (window.innerWidth < 1024 && !filterSlider.classList.contains("slick-initialized")) {
      $(filterSlider).slick({
        arrows: false,
        dots: false,
        infinite: false,
        autoplay: true,
        autoplaySpeed: 2500,
        pauseOnHover: true,
        pauseOnFocus: true,
        variableWidth: true,
        slidesToShow: 1,
        draggable: true,
          swipe: true,
          touchMove: true,
          swipeToSlide: true,
          touchThreshold: 12,
          mobileFirst: true,
        responsive: [
          {
            breakpoint: 1024,
            settings: "unslick",
          },
        ],
      });
      filterRail?.classList.add("is-slick-active");
    } else if (window.innerWidth >= 1024) {
      filterRail?.classList.remove("is-slick-active");
    }
  });
};

const initDiagnosisTechnology = () => {
  const techImage = document.getElementById("diagnosis-tech-image");
  const techLightbox = document.getElementById("diagnosis-tech-lightbox");
  const techTriggers = document.querySelectorAll(".diagnosis-tech-trigger");
  let imageSwapTimeout;

  if (!techImage || !techLightbox || techTriggers.length === 0) {
    return;
  }

  techTriggers.forEach((trigger) => {
    trigger.addEventListener("click", () => {
      const nextImage = trigger.dataset.image || "";
      const nextAlt = trigger.dataset.alt || "Diagnosis technology image";
      const nextTitle = trigger.dataset.title || "Diagnosis technology";

      clearTimeout(imageSwapTimeout);
      techImage.classList.add("opacity-0");

      imageSwapTimeout = window.setTimeout(() => {
        techImage.src = nextImage;
        techImage.alt = nextAlt;
        techImage.classList.remove("opacity-0");
      }, 140);

      techLightbox.dataset.image = nextImage;
      techLightbox.dataset.title = nextTitle;

      techTriggers.forEach((item) => {
        const isActive = item === trigger;
        item.setAttribute("aria-pressed", String(isActive));
        item.classList.toggle("text-[#dea093]", isActive);
        item.classList.toggle("text-white/30", !isActive);
        item.classList.toggle("hover:text-white/50", !isActive);
      });
    });
  });
};

const initTreatmentTechnology = () => {
  const techImage = document.getElementById("treatment-tech-image");
  const techLightbox = document.getElementById("treatment-tech-lightbox");
  const techTriggers = document.querySelectorAll(".treatment-tech-trigger");
  let imageSwapTimeout;

  if (!techImage || !techLightbox || techTriggers.length === 0) {
    return;
  }

  techTriggers.forEach((trigger) => {
    trigger.addEventListener("click", () => {
      const nextImage = trigger.dataset.image || "";
      const nextAlt = trigger.dataset.alt || "Treatment technology image";
      const nextTitle = trigger.dataset.title || "Treatment technology";

      clearTimeout(imageSwapTimeout);
      techImage.classList.add("opacity-0");

      imageSwapTimeout = window.setTimeout(() => {
        techImage.src = nextImage;
        techImage.alt = nextAlt;
        techImage.classList.remove("opacity-0");
      }, 140);

      techLightbox.dataset.image = nextImage;
      techLightbox.dataset.title = nextTitle;

      techTriggers.forEach((item) => {
        const isActive = item === trigger;
        const copy = item.querySelector(".treatment-tech-copy");
        item.setAttribute("aria-pressed", String(isActive));
        item.classList.toggle("text-[#dea093]", isActive);
        item.classList.toggle("text-white/30", !isActive);
        item.classList.toggle("hover:text-white/50", !isActive);
        if (!copy) {
          return;
        }

        if (isActive) {
          copy.classList.remove("hidden");
          void copy.offsetHeight;
          copy.classList.add("max-h-40", "translate-y-0", "py-5");
          copy.classList.remove("max-h-0", "-translate-y-1", "py-0");
          return;
        }

        copy.classList.remove("max-h-40", "translate-y-0", "py-5");
        copy.classList.add("max-h-0", "-translate-y-1", "py-0", "overflow-hidden");
        window.setTimeout(() => {
          if (item.getAttribute("aria-pressed") === "false") {
            copy.classList.add("hidden");
          }
        }, 500);
      });
    });
  });
};

const initTreatmentsMobileSelect = () => {
  const selects = document.querySelectorAll("[data-treatment-anchor-select]");

  if (!selects.length) {
    return;
  }

  const syncSelectValueWithHash = (select) => {
    const hash = window.location.hash;
    if (!hash) {
      return;
    }

    const matchingOption = Array.from(select.options).find((option) => option.value === hash);
    if (matchingOption) {
      select.value = hash;
    }
  };

  const scrollToHashTarget = (hash) => {
    if (!hash || !hash.startsWith("#")) {
      return;
    }

    const target = document.querySelector(hash);
    if (!target) {
      return;
    }

    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      target.scrollIntoView({ block: "start" });
      history.pushState(null, "", hash);
      return;
    }

    const header = document.getElementById("site-header");
    const headerH = header ? header.offsetHeight : 0;
    const gap = 14;
    const rect = target.getBoundingClientRect();
    const targetY = rect.top + window.scrollY - headerH - gap;
    const dist = Math.abs(targetY - window.scrollY);
    const duration = Math.min(1150, Math.max(420, dist * 0.42));

    smoothScrollToY(targetY, duration);
    history.pushState(null, "", hash);
  };

  selects.forEach((select) => {
    syncSelectValueWithHash(select);

    select.addEventListener("change", (event) => {
      const hash = String(event.target.value || "");
      scrollToHashTarget(hash);
    });
  });

  window.addEventListener("hashchange", () => {
    selects.forEach((select) => {
      syncSelectValueWithHash(select);
    });
  });
};

const initDiagnosisStepsSlider = () => {
  const $ = window.jQuery;

  if (!$?.fn?.slick) {
    return;
  }

  const slider = document.querySelector("[data-diagnosis-steps-slider]");
  const markers = Array.from(document.querySelectorAll("[data-diagnosis-step-marker]"));

  if (!slider || slider.classList.contains("slick-initialized")) {
    return;
  }

  const slideCount = slider.children.length;

  const syncMarkers = (currentSlide) => {
    markers.forEach((marker, index) => {
      marker.classList.toggle("is-active", index === currentSlide);
    });
  };

  $(slider).on("init reInit afterChange", (_event, slick, currentSlide) => {
    const currentIndex = typeof currentSlide === "number" ? currentSlide : 0;
    syncMarkers(currentIndex);
  });

  $(slider).slick({
    arrows: false,
    dots: false,
    infinite: false,
    speed: 550,
    autoplay: slideCount > 1,
    autoplaySpeed: 3200,
    pauseOnHover: true,
    pauseOnFocus: true,
    swipe: true,
    draggable: true,
    touchMove: true,
    swipeToSlide: true,
    initialSlide: 0,
    slidesToShow: 4.25,
    slidesToScroll: 1,
    adaptiveHeight: false,
    responsive: [
      {
        breakpoint: 1280,
        settings: {
          slidesToShow: 3.25,
        },
      },
      {
        breakpoint: 1024,
        settings: {
          slidesToShow: 2.25,
        },
      },
      {
        breakpoint: 768,
        settings: {
          slidesToShow: 1.25,
        },
      },
      {
        breakpoint: 640,
        settings: {
          slidesToShow: 1,
        },
      },
    ],
  });

  window.addEventListener("load", () => {
    $(slider).slick("setPosition");
  });

  window.addEventListener("resize", () => {
    $(slider).slick("setPosition");
  });
};

const initResultsModal = () => {
  const modal = document.getElementById("results-modal");
  const beforeImage = document.getElementById("results-modal-before");
  const afterImage = document.getElementById("results-modal-after");
  const cardTitle = document.getElementById("results-modal-card-title");
  const testimonial = document.getElementById("results-modal-testimonial");
  const subtitle = document.getElementById("results-modal-subtitle");
  const subDescription = document.getElementById("results-modal-sub-description");
  const closeButton = document.getElementById("results-modal-close");
  const footerPrev = document.getElementById("results-modal-prev");
  const footerNext = document.getElementById("results-modal-next");
  const imagePrev = document.getElementById("results-modal-image-prev");
  const imageNext = document.getElementById("results-modal-image-next");
  const triggers = [...document.querySelectorAll(".result-card-trigger")];

  if (!modal || !beforeImage || !afterImage || !cardTitle || !testimonial || !subtitle || !subDescription || !closeButton || triggers.length === 0) {
    return;
  }

  let currentIndex = 0;
  /** @type {string[]} */
  let currentGallery = [];
  let galleryPairIndex = 0;

  const parseGalleryFromTrigger = (trigger) => {
    const raw = trigger.getAttribute("data-result-gallery") || "";
    try {
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed)) {
        return parsed.filter((u) => typeof u === "string" && u.trim() !== "");
      }
    } catch {
      /* ignore */
    }

    const b = trigger.dataset.resultBefore || "";
    const a = trigger.dataset.resultAfter || "";
    return b || a ? [b || a, a || b].filter((u) => u) : [];
  };

  const galleryMaxPairIndex = () => Math.max(0, currentGallery.length - 2);

  const canStepGallery = () => currentGallery.length > 2;

  const syncGalleryArrows = () => {
    const step = canStepGallery();
    if (imagePrev) {
      imagePrev.disabled = !step;
    }
    if (imageNext) {
      imageNext.disabled = !step;
    }
  };

  const applyGalleryPair = () => {
    if (currentGallery.length === 0) {
      beforeImage.src = "";
      afterImage.src = "";
      return;
    }

    if (currentGallery.length === 1) {
      beforeImage.src = currentGallery[0];
      afterImage.src = currentGallery[0];
      return;
    }

    const i = Math.min(galleryPairIndex, galleryMaxPairIndex());
    beforeImage.src = currentGallery[i] || "";
    afterImage.src = currentGallery[i + 1] || currentGallery[i] || "";
  };

  const showGalleryNext = (event) => {
    event.stopPropagation();
    if (!canStepGallery()) {
      return;
    }

    const maxI = galleryMaxPairIndex();
    galleryPairIndex = galleryPairIndex >= maxI ? 0 : galleryPairIndex + 1;
    applyGalleryPair();
  };

  const showGalleryPrev = (event) => {
    event.stopPropagation();
    if (!canStepGallery()) {
      return;
    }

    const maxI = galleryMaxPairIndex();
    galleryPairIndex = galleryPairIndex <= 0 ? maxI : galleryPairIndex - 1;
    applyGalleryPair();
  };

  const renderResult = (index) => {
    const trigger = triggers[index];

    if (!trigger) {
      return;
    }

    currentIndex = index;
    currentGallery = parseGalleryFromTrigger(trigger);
    galleryPairIndex = 0;
    applyGalleryPair();
    syncGalleryArrows();

    cardTitle.textContent = trigger.dataset.resultCardTitle || "";
    testimonial.textContent = `"${trigger.dataset.resultTestimonial || ""}"`;
    subtitle.textContent = trigger.dataset.resultSubtitle || "";
    subDescription.textContent = trigger.dataset.resultSubDescription || "";
  };

  const openModal = (index) => {
    renderResult(index);
    modal.classList.remove("hidden");
    document.body.classList.add("overflow-hidden");
  };

  const closeModal = () => {
    modal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
  };

  const showNextResult = (event) => {
    event.stopPropagation();
    renderResult((currentIndex + 1) % triggers.length);
  };

  const showPrevResult = (event) => {
    event.stopPropagation();
    renderResult((currentIndex - 1 + triggers.length) % triggers.length);
  };

  triggers.forEach((trigger, index) => {
    trigger.addEventListener("click", () => {
      openModal(index);
    });
  });

  closeButton.addEventListener("click", closeModal);
  footerPrev?.addEventListener("click", showPrevResult);
  footerNext?.addEventListener("click", showNextResult);
  imagePrev?.addEventListener("click", showGalleryPrev);
  imageNext?.addEventListener("click", showGalleryNext);

  modal.addEventListener("click", (event) => {
    if (event.target === modal) {
      closeModal();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (modal.classList.contains("hidden")) {
      return;
    }

    if (event.key === "ArrowRight") {
      if (canStepGallery()) {
        showGalleryNext(event);
      } else {
        showNextResult(event);
      }
    }

    if (event.key === "ArrowLeft") {
      if (canStepGallery()) {
        showGalleryPrev(event);
      } else {
        showPrevResult(event);
      }
    }

    if (event.key === "Escape") {
      closeModal();
    }
  });
};

const initAssessmentWizard = () => {
  const root = document.getElementById("assessment-page");

  if (!root) {
    return;
  }
  

  const configElement = document.getElementById("assessment-config");

  if (!configElement?.textContent) {
    return;
  }

  const steps = JSON.parse(configElement.textContent);
  const mainHeader = document.getElementById("assessment-main-header");
  const screens = {
    landing: root.querySelector('[data-assessment-screen="landing"]'),
    wizard: root.querySelector('[data-assessment-screen="wizard"]'),
    complete: root.querySelector('[data-assessment-screen="complete"]'),
  };
  const title = root.querySelector("[data-assessment-title]");
  const optionsWrap = root.querySelector("[data-assessment-options]");
  const formWrap = root.querySelector("[data-assessment-form-wrap]");
  const whyButton = root.querySelector("[data-assessment-why-button]");
  const progressItems = [...root.querySelectorAll(".assessment-progress__item")];
  const startButton = root.querySelector("[data-assessment-start]");
  const backButton = root.querySelector("[data-assessment-back]");
  const closeButton = root.querySelector("[data-assessment-close]");
  const submitButton = root.querySelector("[data-assessment-submit]");
  const modal = root.querySelector("[data-assessment-modal]");
  const modalDescription = root.querySelector("[data-assessment-modal-description]");
  const modalCloseButtons = [...root.querySelectorAll("[data-assessment-modal-close]")];
  const submitLoadingOverlay = root.querySelector("[data-assessment-submit-loading]");
  const inputs = {
    name: root.querySelector('[data-assessment-input="name"]'),
    whatsapp: root.querySelector('[data-assessment-input="whatsapp"]'),
    gender: root.querySelector('[data-assessment-input="gender"]'),
    birthdate: root.querySelector('[data-assessment-input="birthdate"]'),
    branchOffice: root.querySelector('[data-assessment-input="branchOffice"]'),
    consent: root.querySelector('[data-assessment-input="consent"]'),
  };
  const genderSelect = root.querySelector("[data-assessment-select]");
  const genderTrigger = root.querySelector("[data-assessment-select-trigger]");
  const genderMenu = root.querySelector("[data-assessment-select-menu]");
  const genderLabel = root.querySelector("[data-assessment-select-label]");
  const genderOptions = [...root.querySelectorAll("[data-assessment-select-option]")];
  const genderPlaceholder = root.dataset.genderPlaceholder || "Select gender";
  const assessmentEndpoint = window.eurohairlabAssessment?.endpoint || "";
  const branchOfficesConfigured = (window.eurohairlabAssessment?.branch_offices?.length ?? 0) > 0;
  const sourcePageSlug = String(root.dataset.sourcePageSlug || "").trim();
  const agentMaskingIdFromUrl = (() => {
    try {
      const c = new URLSearchParams(window.location.search).get("code");
      return typeof c === "string" && c.trim() !== "" ? c.trim() : "";
    } catch {
      return "";
    }
  })();

  const rawLimits = window.eurohairlabAssessment?.limits || {};
  const limits = {
    max_name_utf8_bytes: Number(rawLimits.max_name_utf8_bytes) || 191,
    max_answer_utf8_bytes: Number(rawLimits.max_answer_utf8_bytes) || 500,
    max_question_utf8_bytes: Number(rawLimits.max_question_utf8_bytes) || 500,
    whatsapp_digits_min: Number(rawLimits.whatsapp_digits_min) || 8,
    whatsapp_digits_max: Number(rawLimits.whatsapp_digits_max) || 20,
  };

  const utf8ByteLength = (str) => {
    if (typeof TextEncoder !== "undefined") {
      return new TextEncoder().encode(str).length;
    }
    return str.length;
  };

  /** Mirrors `eh_assessment_normalize_whatsapp()` in the assessment plugin. */
  const normalizeAssessmentWhatsApp = (raw) => {
    let value = String(raw ?? "").trim().replace(/[^0-9+]/g, "");
    if (value === "") {
      return "";
    }
    if (value.startsWith("+")) {
      value = value.slice(1);
    }
    if (value.startsWith("0")) {
      return `62${value.slice(1).replace(/^0+/, "")}`;
    }
    if (value.startsWith("8")) {
      return `62${value}`;
    }
    return value;
  };

  const countNormalizedWaDigits = (raw) => {
    const n = normalizeAssessmentWhatsApp(raw);
    return String(n).replace(/\D/g, "").length;
  };

  /** Mirrors `eh_assessment_normalize_respondent_gender_string()` for validation + payload. */
  const normalizeGenderForSubmission = (raw) => {
    const g = String(raw || "")
      .toLowerCase()
      .replace(/[^a-z]/g, "");
    if (g === "m" || g.startsWith("male")) {
      return "pria";
    }
    if (g === "f" || g.startsWith("female")) {
      return "wanita";
    }
    return "";
  };

  const toSentenceCase = (raw) => {
    const value = String(raw ?? "").trim();
    if (!value) {
      return "";
    }

    const lower = value.toLowerCase();
    return lower.charAt(0).toUpperCase() + lower.slice(1);
  };

  /** One-level decode when WP/meta stored entities (e.g. &lt; → <). Plain text unchanged. */
  const decodeAdminLabelEntities = (raw) => {
    const t = String(raw ?? "");
    if (t === "" || !t.includes("&")) {
      return t;
    }
    const ta = document.createElement("textarea");
    ta.innerHTML = t;
    return ta.value;
  };

  /**
   * Label text for options: sentence case per line, preserve Enter as newline.
   * Assigned via textContent (no innerHTML) so "<" prints as typed, not as entity soup.
   */
  const formatOptionLabelPlain = (raw) => {
    const s = decodeAdminLabelEntities(raw);
    if (!s.trim()) {
      return "";
    }
    const lines = s.split(/\r?\n/);
    return lines
      .map((line) => {
        const t = line.trim();
        if (t === "") {
          return "";
        }
        return toSentenceCase(t);
      })
      .join("\n");
  };

  const answers = {};
  let currentStep = -1;
  let assessmentStarted = false;
  let assessmentSubmitted = false;
  let currentStepStartedAt = null;

  window.dataLayer = window.dataLayer || [];

  const pushAssessmentEvent = (eventName, extra = {}) => {
  const step = steps[currentStep] || {};
    window.dataLayer.push({
      event: eventName,
      assessment_name: "online_hair_assessment",
      page_slug: sourcePageSlug,
      step_number: currentStep + 1,
      question_key: step.key || "",
      question_title: step.title || "",
      question_type: step.type || "",
      ...extra,
    });

    console.log("[Assessment GTM]", eventName, {
      assessment_name: "online_hair_assessment",
      page_slug: sourcePageSlug,
      step_number: currentStep + 1,
      question_key: step.key || "",
      question_title: step.title || "",
      question_type: step.type || "",
      ...extra,
    });
  };

  const markStepView = () => {
    const step = steps[currentStep];
    if (!step) return;

    currentStepStartedAt = Date.now();

    pushAssessmentEvent("assessment_step_view");
  };

  const markStepComplete = (answerValue = "") => {
    const durationMs = currentStepStartedAt ? Date.now() - currentStepStartedAt : 0;

    pushAssessmentEvent("assessment_step_complete", {
      answer_value: answerValue,
      duration_ms: durationMs,
    });
  };

  root.querySelector("[data-assessment-details-form]")?.addEventListener("submit", (event) => {
    event.preventDefault();
  });

  const onFormStep = () => {
    const step = steps[currentStep];
    return Boolean(step && step.type === "form");
  };

  const quizStepsForValidation = () => steps.filter((s) => s && s.type && s.type !== "form" && s.key);

  const collectQuizErrorMessage = () => {
    const quizSteps = quizStepsForValidation();
    if (!quizSteps.length) {
      return null;
    }
    for (const step of quizSteps) {
      const block = answers[step.key];
      if (!block || !String(block.answer || "").trim()) {
        return "Silakan jawab setiap pertanyaan sebelum mengisi data Anda.";
      }
      const q = String(block.question || "");
      const a = String(block.answer || "");
      if (utf8ByteLength(q) > limits.max_question_utf8_bytes || utf8ByteLength(a) > limits.max_answer_utf8_bytes) {
        return "Salah satu jawaban Anda terlalu panjang. Silakan pilih opsi lain atau hubungi klinik.";
      }
    }
    return null;
  };

  const collectRespondentFieldErrors = () => {
    const err = {};
    const nameTrim = String(inputs.name?.value ?? "").trim();
    if (!nameTrim) {
      err.name = "Silakan masukkan nama Anda.";
    } else if (utf8ByteLength(nameTrim) > limits.max_name_utf8_bytes) {
      err.name = `Nama terlalu panjang (maksimum ${limits.max_name_utf8_bytes} byte, sesuai batas server).`;
    }

    const waRaw = inputs.whatsapp?.value ?? "";
    if (!String(waRaw).trim()) {
      err.whatsapp = "Silakan masukkan nomor WhatsApp Anda..";
    } else {
      const d = countNormalizedWaDigits(waRaw);
      if (d < limits.whatsapp_digits_min || d > limits.whatsapp_digits_max) {
        err.whatsapp = `Nomor WhatsApp tidak valid. Gunakan ${limits.whatsapp_digits_min}–${limits.whatsapp_digits_max} digit setelah kode negara (misalnya 08… atau +62…).`;
      }
    }

    if (!normalizeGenderForSubmission(inputs.gender?.value ?? "")) {
      err.gender = "Silakan pilih jenis kelamin Anda.";
    }

    if (branchOfficesConfigured && !String(inputs.branchOffice?.value || "").trim()) {
      err.branchOffice = "Silakan pilih cabang.";
    }

    if (!inputs.consent?.checked) {
      err.consent = "Anda harus menyetujui syarat dan ketentuan untuk melanjutkan.";
    }

    return err;
  };

  const clearSubmitError = () => {
    const el = root.querySelector("[data-assessment-submit-error]");
    if (el) {
      el.textContent = "";
      el.setAttribute("hidden", "");
    }
  };

  const setSubmitError = (message) => {
    const el = root.querySelector("[data-assessment-submit-error]");
    if (el) {
      el.textContent = message;
      if (message) {
        el.removeAttribute("hidden");
      } else {
        el.setAttribute("hidden", "");
      }
    }
  };

  const applyFieldErrors = (fieldErrors) => {
    const keys = ["quiz", "name", "whatsapp", "gender", "branchOffice", "consent"];
    for (const key of keys) {
      const p = root.querySelector(`[data-assessment-error-for="${key}"]`);
      const msg = fieldErrors[key];
      if (p) {
        if (msg) {
          p.textContent = msg;
          p.removeAttribute("hidden");
        } else {
          p.textContent = "";
          p.setAttribute("hidden", "");
        }
      }
    }

    inputs.name?.closest(".assessment-field")?.classList.toggle("assessment-field--invalid", Boolean(fieldErrors.name));
    inputs.whatsapp?.closest(".assessment-field")?.classList.toggle("assessment-field--invalid", Boolean(fieldErrors.whatsapp));
    genderSelect?.closest(".assessment-field")?.classList.toggle("assessment-field--invalid", Boolean(fieldErrors.gender));
    inputs.branchOffice?.closest(".assessment-field")?.classList.toggle("assessment-field--invalid", Boolean(fieldErrors.branchOffice));

    const consentBlock = inputs.consent?.closest(".assessment-consent-block");
    consentBlock?.classList.toggle("assessment-consent-block--invalid", Boolean(fieldErrors.consent));

    const aria = (el, inv) => {
      if (el) {
        el.setAttribute("aria-invalid", inv ? "true" : "false");
      }
    };
    aria(inputs.name, Boolean(fieldErrors.name));
    aria(inputs.whatsapp, Boolean(fieldErrors.whatsapp));
    aria(inputs.gender, Boolean(fieldErrors.gender));
    aria(inputs.branchOffice, Boolean(fieldErrors.branchOffice));
    aria(inputs.consent, Boolean(fieldErrors.consent));
  };

  const clearFieldErrors = () => {
    applyFieldErrors({});
  };

  const touchedDetailFields = new Set();
  let showAllDetailErrors = false;

  const buildVisibleFieldErrors = () => {
    const all = collectRespondentFieldErrors();
    const quizMsg = collectQuizErrorMessage();
    const visible = {};
    if (showAllDetailErrors && quizMsg) {
      visible.quiz = quizMsg;
    }
    const detailKeys = ["name", "whatsapp", "gender", "branchOffice", "consent"];
    for (const k of detailKeys) {
      if ((showAllDetailErrors || touchedDetailFields.has(k)) && all[k]) {
        visible[k] = all[k];
      }
    }
    return visible;
  };

  const refreshFieldErrorUi = () => {
    applyFieldErrors(buildVisibleFieldErrors());
  };

  const markDetailFieldTouched = (key) => {
    touchedDetailFields.add(key);
    refreshFieldErrorUi();
  };

  const setScreen = (screen) => {
    Object.entries(screens).forEach(([name, node]) => {
      node?.classList.toggle("hidden", name !== screen);
    });

    mainHeader?.classList.toggle("hidden", screen !== "complete");
    window.dispatchEvent(new Event("scroll"));
  };

  const closeModal = () => {
    modal?.classList.add("hidden");
    modal?.setAttribute("aria-hidden", "true");
    document.body.classList.remove("assessment-modal-open");
  };

  const openSubmitLoading = () => {
    if (!submitLoadingOverlay) {
      return;
    }
    submitLoadingOverlay.classList.remove("hidden");
    submitLoadingOverlay.setAttribute("aria-hidden", "false");
    document.body.classList.add("assessment-submit-loading-open");
  };

  const closeSubmitLoading = () => {
    if (!submitLoadingOverlay) {
      return;
    }
    submitLoadingOverlay.classList.add("hidden");
    submitLoadingOverlay.setAttribute("aria-hidden", "true");
    document.body.classList.remove("assessment-submit-loading-open");
  };

  const openModal = (description) => {
    if (!modal || !modalDescription) {
      return;
    }

    modalDescription.textContent = description;
    modal.classList.remove("hidden");
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("assessment-modal-open");
  };

  const syncSubmitState = () => {
    if (!submitButton) {
      return;
    }

    if (!onFormStep()) {
      clearFieldErrors();
      clearSubmitError();
      submitButton.disabled = true;
      touchedDetailFields.clear();
      showAllDetailErrors = false;
      return;
    }

    const quizMsg = collectQuizErrorMessage();
    submitButton.disabled = Boolean(quizMsg);
    refreshFieldErrorUi();
  };

  const closeGenderSelect = () => {
    genderMenu?.classList.add("hidden");
    genderTrigger?.setAttribute("aria-expanded", "false");
  };

  const openGenderSelect = () => {
    genderMenu?.classList.remove("hidden");
    genderTrigger?.setAttribute("aria-expanded", "true");
  };

  const setGenderValue = (value) => {
    if (!inputs.gender || !genderLabel) {
      return;
    }

    inputs.gender.value = value;
    genderLabel.textContent = toSentenceCase(value || genderPlaceholder);

    genderOptions.forEach((option) => {
      const isSelected = (option.dataset.assessmentSelectOption || "") === value;
      option.classList.toggle("is-selected", isSelected);
      option.setAttribute("aria-selected", String(isSelected));
    });

    inputs.gender.dispatchEvent(new Event("input", { bubbles: true }));
    inputs.gender.dispatchEvent(new Event("change", { bubbles: true }));
    closeGenderSelect();
  };

  const renderOptions = (step) => {
    if (!optionsWrap) {
      return;
    }

    if (step.type === "grid") {
      optionsWrap.className = "assessment-options assessment-options--grid";
      optionsWrap.innerHTML = step.options
        .map(
          (option) => `
        <button type="button" class="assessment-option assessment-option--grid" data-assessment-option="${option.value}">
          <span class="assessment-option__icon" aria-hidden="true">
            <img src="${option.icon}" alt="" loading="lazy" decoding="async">
          </span>
          <span class="assessment-option__label assessment-option__label--grid"></span>
        </button>`
        )
        .join("");
      optionsWrap.querySelectorAll(".assessment-option--grid .assessment-option__label").forEach((span, idx) => {
        const opt = step.options[idx];
        if (opt) {
          span.textContent = formatOptionLabelPlain(opt.label);
        }
      });
      return;
    }

    if (step.type === "list") {
      optionsWrap.className = "assessment-options assessment-options--list";
      optionsWrap.innerHTML = step.options
        .map(
          (option) => `
        <button type="button" class="assessment-option assessment-option--list" data-assessment-option="${option.value}">
          <span class="assessment-option__label"></span>
        </button>`
        )
        .join("");
      optionsWrap.querySelectorAll(".assessment-option--list .assessment-option__label").forEach((span, idx) => {
        const opt = step.options[idx];
        if (opt) {
          span.textContent = formatOptionLabelPlain(opt.label);
        }
      });
      return;
    }

    optionsWrap.className = "assessment-options hidden";
    optionsWrap.innerHTML = "";
  };

  const renderStep = () => {
    const step = steps[currentStep];

    if (!step || !title || !formWrap || !whyButton) {
      return;
    }

    setScreen("wizard");
    title.textContent = step.title;

    progressItems.forEach((item, index) => {
      item.classList.toggle("is-active", index <= currentStep);
    });

    if (step.type === "form") {
      optionsWrap?.classList.add("hidden");
      formWrap.classList.remove("hidden");
      whyButton.classList.add("hidden");
      touchedDetailFields.clear();
      showAllDetailErrors = false;
      clearFieldErrors();
      syncSubmitState();
      return;
    }

    formWrap.classList.add("hidden");
    renderOptions(step);
    optionsWrap?.classList.remove("hidden");
    whyButton.classList.toggle("hidden", !step.why);
    whyButton.dataset.description = step.why || "";
  };

  const goToStep = (index) => {
    currentStep = index;
    renderStep();
    markStepView();
  };

  const resetToLanding = () => {
    currentStep = -1;
    closeModal();
    setScreen("landing");
  };

  startButton?.addEventListener("click", () => {
    if (!assessmentStarted) {
      assessmentStarted = true;
      pushAssessmentEvent("assessment_start");
    }

    goToStep(0);
  });

  backButton?.addEventListener("click", () => {
    pushAssessmentEvent("assessment_back");

    if (currentStep <= 0) {
      resetToLanding();
      return;
    }

    goToStep(currentStep - 1);
  });

  closeButton?.addEventListener("click", resetToLanding);

  genderTrigger?.addEventListener("click", () => {
    const isOpen = genderTrigger.getAttribute("aria-expanded") === "true";
    if (isOpen) {
      closeGenderSelect();
      return;
    }

    openGenderSelect();
  });

  genderOptions.forEach((option) => {
    option.addEventListener("click", () => {
      setGenderValue(option.dataset.assessmentSelectOption || "");
    });
  });

  document.addEventListener("click", (event) => {
    if (!genderSelect || genderSelect.contains(event.target)) {
      return;
    }

    closeGenderSelect();
  });

  optionsWrap?.addEventListener("click", (event) => {
    const button = event.target.closest("[data-assessment-option]");

    if (!button) {
      return;
    }

    const step = steps[currentStep];

    if (!step) {
      return;
    }

    if (step.key) {
      const answerValue = button.dataset.assessmentOption || "";
      const questionText = step.title || "";

      if (utf8ByteLength(questionText) > limits.max_question_utf8_bytes) {
        openModal("Teks pertanyaan terlalu panjang. Silakan hubungi administrator situs.");
        return;
      }

      if (utf8ByteLength(answerValue) > limits.max_answer_utf8_bytes) {
        openModal("Pilihan ini terlalu panjang. Silakan pilih opsi lain.");
        return;
      }

      answers[step.key] = {
        question: questionText,
        answer: answerValue,
      };

      pushAssessmentEvent("assessment_answer", {
        answer_value: answerValue,
      });

      markStepComplete(answerValue);
    }

    if (currentStep < steps.length - 1) {
      goToStep(currentStep + 1);
    }
  });

  whyButton?.addEventListener("click", () => {
    if (whyButton.dataset.description) {
      openModal(whyButton.dataset.description);
    }
  });

  modalCloseButtons.forEach((button) => {
    button.addEventListener("click", closeModal);
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      if (submitLoadingOverlay && !submitLoadingOverlay.classList.contains("hidden")) {
        return;
      }
      closeModal();
    }
  });

  Object.entries(inputs).forEach(([key, input]) => {
    if (!input) {
      return;
    }

    input.addEventListener("input", () => {
      clearSubmitError();
      syncSubmitState();
    });

    input.addEventListener("change", () => {
      clearSubmitError();
      if (key === "consent") {
        markDetailFieldTouched("consent");
      }
      syncSubmitState();
    });
  });

  ["name", "whatsapp", "branchOffice"].forEach((key) => {
    inputs[key]?.addEventListener("blur", () => {
      markDetailFieldTouched(key);
    });
  });

  genderSelect?.addEventListener("focusout", (event) => {
    if (genderSelect.contains(event.relatedTarget)) {
      return;
    }
    markDetailFieldTouched("gender");
    syncSubmitState();
  });

  submitButton?.addEventListener("click", async () => {
    clearSubmitError();
    const fieldErr = collectRespondentFieldErrors();
    const quizMsg = collectQuizErrorMessage();
    if (Object.keys(fieldErr).length > 0 || quizMsg) {
      showAllDetailErrors = true;
      syncSubmitState();
      return;
    }

    const branchOutletMaskingId = String(inputs.branchOffice?.value || "").trim();
    const birthdate = String(inputs.birthdate?.value || "1990-01-01").trim() || "1990-01-01";
    const genderNorm = normalizeGenderForSubmission(inputs.gender?.value || "");

    const rawReportType = eurohairlabAssessment?.report_type;
    const parsedReportType =
      typeof rawReportType === "number"
        ? rawReportType
        : parseInt(String(rawReportType ?? "5"), 10);
    const reportType =
      Number.isFinite(parsedReportType) && parsedReportType >= 1 && parsedReportType <= 99
        ? parsedReportType
        : 5;

    const submission = {
      source_page_slug: sourcePageSlug,
      branch_outlet_masking_id: branchOutletMaskingId,
      report_type: reportType,
    };
    if (agentMaskingIdFromUrl) {
      submission.agent_masking_id = agentMaskingIdFromUrl;
    }

    const payload = {
      submission,
      respondent: {
        name: String(inputs.name?.value ?? "").trim(),
        whatsapp: String(inputs.whatsapp?.value ?? "").trim(),
        gender: genderNorm,
        birthdate,
        consent: Boolean(inputs.consent?.checked),
      },
      answers,
    };

    if (!assessmentEndpoint) {
      console.error("Assessment endpoint is not configured.", payload);
      setSubmitError("Assessment is not configured. Reload the page or contact the clinic.");
      return;
    }

    submitButton.disabled = true;
    openSubmitLoading();

    pushAssessmentEvent("assessment_submit", {
      respondent_gender: genderNorm,
      branch_outlet_masking_id: branchOutletMaskingId,
    });

    try {
      const response = await fetch(assessmentEndpoint, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify(payload),
      });

      if (!response.ok) {
        let detail = "";
        try {
          const errBody = await response.json();
          if (errBody && typeof errBody.message === "string" && errBody.message.trim()) {
            detail = errBody.message.trim();
          }
        } catch {
          //
        }
        const statusHint =
          response.status === 429
            ? "Too many submissions. Please wait a moment and try again."
            : response.status === 403
              ? "Submission was denied. Reload the page and try again."
              : response.status === 413
                ? "The data sent is too large."
                : "";
        throw new Error(detail || statusHint || `Submission failed (${response.status}).`);
      }

      closeModal();
      assessmentSubmitted = true;
      setScreen("complete");
      pushAssessmentEvent("assessment_complete_view");
    } catch (error) {
      console.error("Assessment submission failed", error);
      const msg =
        error instanceof Error && error.message
          ? error.message
          : "Submission failed. Check your connection and try again.";
      setSubmitError(msg);
      submitButton.disabled = false;
      syncSubmitState();
    } finally {
      closeSubmitLoading();
    }
  });

  window.addEventListener("beforeunload", () => {
    if (assessmentStarted && !assessmentSubmitted && currentStep >= 0) {
      pushAssessmentEvent("assessment_abandon");
    }
  });

  setScreen("landing");
  syncSubmitState();

  window.addEventListener("beforeunload", () => {
    if (assessmentStarted && !assessmentSubmitted && currentStep >= 0) {
      pushAssessmentEvent("assessment_abandon");
    }
  });
};

/** Eased in-page scroll (RAF) — smoother and more consistent than native `scroll-behavior` alone. */
let ehSmoothScrollToken = 0;

const easeInOutCubic = (t) =>
  t < 0.5 ? 4 * t * t * t : 1 - (-2 * t + 2) ** 3 / 2;

const smoothScrollToY = (targetY, durationMs) => {
  const startY = window.scrollY;
  const maxY = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
  const clampedTarget = Math.min(maxY, Math.max(0, targetY));
  const distance = clampedTarget - startY;

  if (Math.abs(distance) < 2) {
    return;
  }

  const token = ++ehSmoothScrollToken;
  const startTime = performance.now();

  const tick = (now) => {
    if (token !== ehSmoothScrollToken) {
      return;
    }

    const elapsed = now - startTime;
    const t = Math.min(1, elapsed / durationMs);
    const eased = easeInOutCubic(t);
    window.scrollTo(0, startY + distance * eased);

    if (t < 1) {
      requestAnimationFrame(tick);
    }
  };

  requestAnimationFrame(tick);
};

/** Same-page #anchors: eased scroll + fixed header offset; skip if user prefers reduced motion. */
const initSmoothScrollAnchors = () => {
  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    return;
  }

  document.addEventListener(
    "click",
    (event) => {
      const link = event.target.closest('a[href^="#"]');
      if (!link) {
        return;
      }

      const raw = link.getAttribute("href");
      if (!raw || raw === "#") {
        return;
      }

      let url;
      try {
        url = new URL(link.href);
      } catch {
        return;
      }

      if (url.origin !== window.location.origin || url.pathname !== window.location.pathname) {
        return;
      }

      const hash = url.hash;
      if (!hash || hash === "#") {
        return;
      }

      const target = document.querySelector(hash);
      if (!target) {
        return;
      }

      event.preventDefault();

      const header = document.getElementById("site-header");
      const headerH = header ? header.offsetHeight : 0;
      const gap = 14;
      const rect = target.getBoundingClientRect();
      const targetY = rect.top + window.scrollY - headerH - gap;
      const dist = Math.abs(targetY - window.scrollY);
      const duration = Math.min(1150, Math.max(420, dist * 0.42));

      smoothScrollToY(targetY, duration);
      history.pushState(null, "", hash);
    },
    { passive: false }
  );
};

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    initSmoothScrollAnchors();
    initAboutSliders();
    initAboutPartnership();
    initHomepageHeroSlider();
    initHomeProgramsSlider();
    initBlogFilterSlider();
    initDiagnosisStepsSlider();
    initDiagnosisTechnology();
    initTreatmentTechnology();
    initTreatmentsMobileSelect();
    initResultsModal();
    initAssessmentWizard();
  }, { once: true });
} else {
  initSmoothScrollAnchors();
  initAboutSliders();
  initAboutPartnership();
  initHomepageHeroSlider();
  initHomeProgramsSlider();
  initBlogFilterSlider();
  initDiagnosisStepsSlider();
  initDiagnosisTechnology();
  initTreatmentTechnology();
  initTreatmentsMobileSelect();
  initResultsModal();
  initAssessmentWizard();
}
