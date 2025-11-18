/**
 * @file
 * Views Vanilla JavaScript Basic Carousel.
 *
 * Enhanced with play/pause, progress bar, page counter, keyboard navigation,
 * and accessibility features following Drupal 11 best practices.
 *
 * Filename:     vvjb.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, drupalSettings, once) => {
  'use strict';

  /**
   * Debounce utility function for performance optimization.
   *
   * @param {Function} func
   *   The function to debounce.
   * @param {number} delay
   *   The delay in milliseconds.
   *
   * @return {Function}
   *   The debounced function.
   */
  function debounce(func, delay) {
    let timer;
    return function(...args) {
      clearTimeout(timer);
      timer = setTimeout(() => func.apply(this, args), delay);
    };
  }

  /**
   * Detect if carousel is in RTL mode.
   *
   * @param {HTMLElement} element
   *   The element to check for RTL.
   *
   * @return {boolean}
   *   True if RTL mode is detected.
   */
  function isRTL(element) {
    return element.closest('[dir="rtl"]') !== null ||
           document.documentElement.dir === 'rtl' ||
           document.body.dir === 'rtl' ||
           getComputedStyle(element).direction === 'rtl';
  }

  /**
   * SVG icon definitions for UI elements.
   */
  const SVG_ICONS = {
    play: `<svg class="svg-play" xmlns="http://www.w3.org/2000/svg" viewBox="80 -880 800 800" fill="currentColor" aria-hidden="true">
      <path d="m380-300 280-180-280-180v360ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"></path>
    </svg>`,
    pause: `<svg class="svg-pause" xmlns="http://www.w3.org/2000/svg" viewBox="80 -880 800 800" fill="currentColor" aria-hidden="true">
      <path d="M360-320h80v-320h-80v320Zm160 0h80v-320h-80v320ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"></path>
    </svg>`
  };

  /**
   * Main Drupal behavior for VVJB carousel.
   */
  Drupal.behaviors.vvjbCarousel = {
    attach(context) {
      const carousels = once('vvjbCarousel', '.vvjb-inner', context);
      carousels.forEach(initCarousel);
    },

    /**
     * Detach behavior for cleanup.
     */
    detach(context, settings, trigger) {
      if (trigger === 'unload') {
        const carousels = context.querySelectorAll('.vvjb-inner[data-vvjb-initialized="true"]');
        carousels.forEach(container => {
          if (container.vvjbState) {
            cleanupCarousel(container.vvjbState);
            delete container.vvjbState;
          }
        });
      }
    }
  };

  /**
   * Initialize a single carousel instance.
   *
   * @param {HTMLElement} carouselInner
   *   The carousel inner container element.
   */
  function initCarousel(carouselInner) {
    // Security: Validate required DOM structure.
    const wrapper = carouselInner.querySelector('.vvjb-carousel-wrapper');
    const itemsContainer = wrapper?.querySelector('.vvjb-items');

    if (!wrapper || !itemsContainer) {
      if (typeof console !== 'undefined' && console.error) {
        console.error('VVJB: Required DOM structure missing', carouselInner);
      }
      return;
    }

    // Create state object.
    const state = createState(carouselInner);

    // Security: Validate state creation.
    if (!state || !state.items.length) {
      if (typeof console !== 'undefined' && console.warn) {
        console.warn('VVJB: Invalid state or no items found', carouselInner);
      }
      return;
    }

    // Store state on container for external access and cleanup.
    carouselInner.vvjbState = state;

    // Initialize features based on configuration.
    initializeFeatures(state);

    // Initial layout update.
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        updateLayout(state);
        createDots(state);
        bindDots(state);

        // Initialize deep linking if enabled.
        if (state.config.deeplinkEnabled && state.config.deeplinkId) {
          initializeDeepLinking(state);
        }

        // Remove hidden attribute to make carousel visible.
        state.container.removeAttribute('hidden');

        // Small delay for final layout adjustment.
        setTimeout(() => {
          updateLayout(state);
        }, 100);
      });
    });

    // Mark as initialized.
    carouselInner.setAttribute('data-vvjb-initialized', 'true');
  }

  /**
   * Create carousel state object with all configuration and references.
   *
   * @param {HTMLElement} container
   *   The carousel inner container.
   *
   * @return {Object|null}
   *   The state object or null if creation fails.
   */
  function createState(container) {
    const wrapper = container.querySelector('.vvjb-carousel-wrapper');
    const itemsContainer = wrapper.querySelector('.vvjb-items');

    // Security: Validate required elements exist.
    if (!wrapper || !itemsContainer) {
      return null;
    }

    const items = itemsContainer.querySelectorAll('.vvjb-item');

    // Security: Validate we have items.
    if (!items.length) {
      return null;
    }

    const nextButton = container.querySelector('.vvjb-next');
    const prevButton = container.querySelector('.vvjb-prev');
    const announcer = container.querySelector('.vvjb-carousel-announcer');

    // Read configuration from data attributes with sanitization.
    const dataset = itemsContainer.dataset;

    // Performance: Parse integers once and store.
    const breakpoints = parseInt(dataset.breakpoints, 10) || 992;
    const itemsSmall = parseInt(dataset.smallScreen, 10) || 1;
    const itemsBig = parseInt(dataset.bigScreen, 10) || 3;
    const totalSlides = parseInt(dataset.totalSlides, 10) || items.length;
    const slideTime = parseInt(dataset.slideTime, 10) || 5000;
    const gap = parseInt(dataset.gap, 10) || 0;

    // Read feature flags from vvjb-inner data attributes.
    const innerDataset = container.dataset;
    const showPlayPause = innerDataset.showPlayPause === 'true';
    const showProgressBar = innerDataset.showProgressBar === 'true';
    const showPageCounter = innerDataset.showPageCounter === 'true';
    const enableKeyboardNav = innerDataset.enableKeyboardNav === 'true';
    const enableTouchSwipe = innerDataset.enableTouchSwipe === 'true';
    const enablePauseOnHover = innerDataset.enablePauseOnHover === 'true';
    const pauseOnReducedMotion = innerDataset.pauseOnReducedMotion === 'true';

    // Get UI elements for new features.
    const playPauseButton = container.querySelector('.vvjb-play-pause-button');
    const progressBar = container.querySelector('.vvjb-progress-bar');
    const currentPageSpan = container.querySelector('.vvjb-current-page');
    const totalPagesSpan = container.querySelector('.vvjb-total-pages');

    // Deep linking configuration.
    const deeplinkEnabled = dataset.deeplinkEnabled === 'true';
    const deeplinkId = dataset.deeplinkId || '';

    // Detect RTL mode - ADD THIS LINE
    const rtlMode = isRTL(container);

    // Build configuration object.
    const config = {
      rawOrientation: dataset.orientation || 'horizontal',
      orientation: 'horizontal',
      breakpoints,
      itemsSmall,
      itemsBig,
      totalSlides,
      slideTime,
      gap,
      looping: dataset.carouselLoop === '1',
      navigation: dataset.navigation || 'both',
      deeplinkEnabled,
      deeplinkId,
    };

    // Build feature flags object.
    const features = {
      showPlayPause,
      showProgressBar,
      showPageCounter,
      enableKeyboardNav,
      enableTouchSwipe,
      enablePauseOnHover,
      pauseOnReducedMotion,
    };

    return {
      container,
      wrapper,
      itemsContainer,
      items,
      nextButton,
      prevButton,
      announcer,
      playPauseButton,
      progressBar,
      currentPageSpan,
      totalPagesSpan,
      config,
      features,
      pageIndex: 0,
      isPaused: false,
      autoSlideTimer: null,
      progressIntervalId: null,
      pageStartTime: null,
      observers: [],
      isRTL: rtlMode,
    };
  }

  /**
   * Initialize all enabled features for the carousel.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function initializeFeatures(state) {
    // Always bind navigation arrows.
    bindNavigation(state);

    // Conditionally bind touch/swipe.
    if (state.features.enableTouchSwipe) {
      bindTouch(state);
    }

    // Initialize auto-slide if enabled.
    if (state.config.slideTime > 0) {
      bindAutoSlide(state);
    }

    // Conditionally bind keyboard navigation.
    if (state.features.enableKeyboardNav) {
      bindKeyboard(state);
    }

    // Initialize play/pause button if enabled.
    if (state.features.showPlayPause && state.playPauseButton) {
      initPlayPauseButton(state);
    }

    // Initialize progress bar if enabled.
    if (state.features.showProgressBar && state.progressBar) {
      initProgressBar(state);
    }

    // Initialize page counter if enabled.
    if (state.features.showPageCounter && state.currentPageSpan) {
      initPageCounter(state);
    }

    // Apply reduced motion if enabled.
    if (state.features.pauseOnReducedMotion) {
      applyReducedMotion(state);
    }

    // Setup ResizeObserver for responsive layout.
    if ('ResizeObserver' in window && state.wrapper) {
      const observer = new ResizeObserver(() => updateLayout(state));
      observer.observe(state.wrapper);
      state.observers.push(observer);
    }

    // Setup window resize handler.
    const refreshDots = debounce(() => {
      createDots(state);
      bindDots(state);
    }, 300);

    const resizeHandler = () => {
      clampPageIndex(state);
      updateLayout(state);
      refreshDots();
    };

    window.addEventListener('resize', resizeHandler);
    state.resizeHandler = resizeHandler;
  }

  /**
   * Initialize play/pause button functionality.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function initPlayPauseButton(state) {
    if (!state.playPauseButton) {
      return;
    }

    // Set initial state.
    updatePlayPauseButton(state);

    // Bind click event.
    state.playPauseButton.addEventListener('click', () => {
      togglePlayPause(state);
    });
  }

  /**
   * Initialize progress bar functionality.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function initProgressBar(state) {
    if (!state.progressBar) {
      return;
    }

    // Set initial ARIA attributes.
    state.progressBar.setAttribute('role', 'progressbar');
    state.progressBar.setAttribute('aria-valuemin', '0');
    state.progressBar.setAttribute('aria-valuemax', '100');
    state.progressBar.setAttribute('aria-valuenow', '0');
  }

  /**
   * Initialize page counter functionality.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function initPageCounter(state) {
    if (!state.currentPageSpan || !state.totalPagesSpan) {
      return;
    }

    // Initial update (will calculate and set both current and total).
    updatePageCounter(state);
  }

  /**
   * Update play/pause button appearance and state.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function updatePlayPauseButton(state) {
    if (!state.playPauseButton) {
      return;
    }

    const isPaused = state.isPaused;

    // Security: Use textContent for screen reader text, innerHTML for SVG only.
    state.playPauseButton.innerHTML = isPaused ? SVG_ICONS.play : SVG_ICONS.pause;

    // Update ARIA label for accessibility.
    const label = isPaused
      ? Drupal.t('Play carousel')
      : Drupal.t('Pause carousel');

    state.playPauseButton.setAttribute('aria-label', label);
  }

  /**
   * Start progress bar animation.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function startProgressBar(state) {
    if (!state.features.showProgressBar || !state.progressBar) {
      return;
    }

    // Performance: Don't start if already running.
    if (state.progressIntervalId) {
      return;
    }

    // Security: Validate slideTime.
    if (state.config.slideTime <= 0) {
      return;
    }

    state.pageStartTime = Date.now();

    // Performance: Use requestAnimationFrame for smoother animation.
    const updateProgress = () => {
      if (state.isPaused || !state.pageStartTime) {
        stopProgressBar(state);
        return;
      }

      const elapsed = Date.now() - state.pageStartTime;
      const progress = Math.min(100, (elapsed / state.config.slideTime) * 100);

      // Update CSS custom property for animation.
      state.progressBar.style.setProperty('--progress', `${progress}%`);

      // Update ARIA value for accessibility.
      state.progressBar.setAttribute('aria-valuenow', Math.round(progress));

      if (progress < 100) {
        state.progressIntervalId = requestAnimationFrame(updateProgress);
      } else {
        stopProgressBar(state);
      }
    };

    state.progressIntervalId = requestAnimationFrame(updateProgress);
  }

  /**
   * Stop progress bar animation.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function stopProgressBar(state) {
    if (state.progressIntervalId) {
      cancelAnimationFrame(state.progressIntervalId);
      state.progressIntervalId = null;
    }
  }

  /**
   * Reset progress bar to zero.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function resetProgressBar(state) {
    if (!state.progressBar) {
      return;
    }

    stopProgressBar(state);
    state.progressBar.style.setProperty('--progress', '0%');
    state.progressBar.setAttribute('aria-valuenow', '0');
  }

  /**
   * Update page counter display.
   *
   * Recalculates total pages based on current viewport size and updates both
   * current and total page numbers. This ensures the counter stays accurate
   * when the carousel is resized or orientation changes.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function updatePageCounter(state) {
    if (!state.currentPageSpan || !state.totalPagesSpan) {
      return;
    }

    // Recalculate total pages based on current items visible.
    const itemsVisible = getItemsVisible(state);
    const totalPages = Math.ceil(state.config.totalSlides / itemsVisible);

    // Security: Use textContent to prevent XSS.
    state.currentPageSpan.textContent = state.pageIndex + 1;
    state.totalPagesSpan.textContent = totalPages;
  }

  /**
   * Toggle play/pause state.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function togglePlayPause(state) {
    state.isPaused = !state.isPaused;

    // Toggle is-paused class for CSS styling (e.g., hiding progress bar).
    if (state.isPaused) {
      state.container.classList.add('is-paused');
    } else {
      state.container.classList.remove('is-paused');
    }

    updatePlayPauseButton(state);

    if (state.isPaused) {
      stopAutoSlide(state);
      stopProgressBar(state);

      // Reset progress bar after fade animation completes.
      if (state.progressBar) {
        setTimeout(() => {
          if (state.isPaused) {  // Only reset if still paused.
            resetProgressBar(state);
          }
        }, 300);  // Match CSS transition duration.
      }
    } else {
      startAutoSlide(state);
      startProgressBar(state);
    }
  }

  /**
   * Apply reduced motion preferences.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function applyReducedMotion(state) {
    // Security: Check if matchMedia is available.
    if (!window.matchMedia) {
      return;
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (prefersReducedMotion) {
      // Auto-pause for accessibility.
      state.isPaused = true;

      // Add paused class for CSS styling.
      state.container.classList.add('is-paused');

      // Stop any auto-slide.
      stopAutoSlide(state);

      // Add CSS class for styling.
      state.container.classList.add('reduced-motion');

      // Update play/pause button if available.
      if (state.playPauseButton) {
        updatePlayPauseButton(state);
      }

      // Announce to screen reader.
      if (state.announcer) {
        state.announcer.textContent = Drupal.t('Carousel paused due to reduced motion preference');
      }
    }
  }

  /**
   * Resolve orientation based on configuration and viewport.
   *
   * @param {Object} state
   *   The carousel state object.
   *
   * @return {string}
   *   The resolved orientation ('horizontal' or 'vertical').
   */
  function resolveOrientation(state) {
    const raw = state.config.rawOrientation;
    const bp = state.config.breakpoints;

    if (raw === 'vertical') {
      return 'vertical';
    }

    if (raw === 'hybrid' && window.innerWidth <= bp) {
      return 'vertical';
    }

    return 'horizontal';
  }

  /**
   * Get item size (width or height based on orientation).
   *
   * @param {HTMLElement} item
   *   The carousel item element.
   * @param {boolean} isVertical
   *   Whether the orientation is vertical.
   *
   * @return {number}
   *   The item size in pixels.
   */
  function getItemSize(item, isVertical) {
    return isVertical ? item.offsetHeight : item.offsetWidth;
  }

  /**
   * Calculate number of items visible in viewport.
   *
   * @param {Object} state
   *   The carousel state object.
   *
   * @return {number}
   *   Number of visible items.
   */
  function getItemsVisible(state) {
    const isVertical = state.config.orientation === 'vertical';
    const isHybrid = state.config.rawOrientation === 'hybrid';
    const isSmallScreen = window.innerWidth <= state.config.breakpoints;

    const max = isSmallScreen ? state.config.itemsSmall : state.config.itemsBig;

    // Vertical mode: always return max.
    if (isVertical) {
      return max;
    }

    // Hybrid mode: act like vertical on small screens.
    if (isHybrid && isSmallScreen) {
      return max;
    }

    // Horizontal: dynamic fitting logic.
    const containerSize = state.wrapper.offsetWidth;
    const gap = state.config.gap || 0;
    let total = 0;
    let count = 0;

    for (let i = 0; i < state.items.length; i++) {
      const itemSize = state.items[i].offsetWidth;
      if (i > 0) {
        total += gap;
      }
      total += itemSize;
      if (total > containerSize) {
        break;
      }
      count++;
      if (count >= max) {
        break;
      }
    }

    return Math.max(1, count);
  }

  /**
   * Get array of currently visible items.
   *
   * @param {Object} state
   *   The carousel state object.
   *
   * @return {Array}
   *   Array of visible item elements.
   */
  function getVisibleItems(state) {
    const itemsVisible = getItemsVisible(state);
    const start = state.pageIndex * itemsVisible;
    return Array.from(state.items).slice(start, start + itemsVisible);
  }

  /**
   * Calculate scroll offset for current page.
   *
   * @param {Object} state
   *   The carousel state object.
   *
   * @return {number}
   *   The scroll offset in pixels.
   */
  function getGroupOffset(state) {
    const itemsVisible = getItemsVisible(state);
    const isVertical = state.config.orientation === 'vertical';
    const gap = state.config.gap || 0;
    let offset = 0;

    for (let page = 0; page < state.pageIndex; page++) {
      const start = page * itemsVisible;
      const end = start + itemsVisible;

      for (let i = start; i < end && i < state.items.length; i++) {
        const size = getItemSize(state.items[i], isVertical);
        offset += size;

        if (i < end - 1) {
          offset += gap;
        }
      }

      if (end < state.items.length) {
        offset += gap;
      }
    }

    return offset;
  }

  /**
   * Set wrapper size based on visible items.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function setWrapperSize(state) {
    const itemsVisible = getItemsVisible(state);

    if (!state.items.length) {
      return;
    }

    const visibleItems = getVisibleItems(state);
    const gap = state.config.gap || 0;
    const totalGap = (itemsVisible - 1) * gap;

    if (state.config.orientation === 'vertical') {
      const totalHeight = visibleItems.reduce((sum, item) => {
        return sum + getItemSize(item, true);
      }, 0);

      state.wrapper.style.maxHeight = `${totalHeight + totalGap}px`;
      state.wrapper.style.height = '';
      state.wrapper.style.maxWidth = '';
      state.centerOffset = 0;
    }
    else {
      const totalWidth = visibleItems.reduce((sum, item, idx) => {
        return sum + getItemSize(item, false) + (idx > 0 ? gap : 0);
      }, 0);

      // Calculate centering offset.
      const wrapperWidth = state.wrapper.offsetWidth;
      const centerOffset = (wrapperWidth - totalWidth) / 2;
      state.centerOffset = centerOffset > 0 ? centerOffset : 0;

      state.wrapper.style.maxWidth = '';
      state.wrapper.style.width = '';
      state.wrapper.style.maxHeight = '';
    }

    // Make wrapper visible if hidden.
    if (state.wrapper.style.visibility !== 'visible') {
      state.wrapper.style.visibility = 'visible';
      state.wrapper.classList.add('is-visible');
    }
  }

  /**
   * Scroll carousel to current page.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function scrollToPage(state) {
    const offset = getGroupOffset(state);
    const centering = state.centerOffset || 0;

    if (state.config.orientation === 'vertical') {
      state.itemsContainer.style.transform = `translateY(${-offset}px)`;
    }
    else {
      // Reverse transform direction in RTL for horizontal carousels
      const transform = state.isRTL ? -(centering - offset) : (centering - offset);
      state.itemsContainer.style.transform = `translateX(${transform}px)`;
    }
  }

  /**
   * Update carousel layout and ARIA attributes.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function updateLayout(state) {
    state.config.orientation = resolveOrientation(state);
    setWrapperSize(state);

    const itemsVisible = getItemsVisible(state);
    const start = state.pageIndex * itemsVisible;
    const end = start + itemsVisible;

    // Update item states and ARIA attributes.
    state.items.forEach((item, i) => {
      const isActive = i >= start && i < end;

      // Update classes.
      item.classList.toggle('active-slide', isActive);
      item.classList.toggle('non-active-slide', !isActive);

      // Update ARIA attributes for accessibility.
      item.setAttribute('aria-hidden', !isActive);

      // Add inert attribute to non-active items.
      if (isActive) {
        item.removeAttribute('inert');
      }
      else {
        item.setAttribute('inert', '');
      }

      // Manage tabindex for interactive elements.
      const interactiveElements = item.querySelectorAll('a, button, input, textarea, select, [tabindex]');
      interactiveElements.forEach(el => {
        el.setAttribute('tabindex', isActive ? '0' : '-1');
      });
    });

    // Update dots if navigation uses them.
    if (state.config.navigation === 'dots' || state.config.navigation === 'both') {
      const dots = state.container.querySelectorAll('.vvjb-carousel-dot');
      dots.forEach((dot, index) => {
        const isActive = index === state.pageIndex;
        dot.classList.toggle('active-slide', isActive);
        dot.setAttribute('aria-current', isActive ? 'true' : 'false');
      });
    }

    updateNavVisibility(state);
    announceVisible(state, start, end);
    scrollToPage(state);

    // Update page counter if enabled.
    if (state.features.showPageCounter) {
      updatePageCounter(state);
    }

    // Restart progress bar if not paused.
    if (state.features.showProgressBar && !state.isPaused) {
      resetProgressBar(state);
      startProgressBar(state);
    }
  }

  /**
   * Update navigation button visibility.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function updateNavVisibility(state) {
    if (state.config.navigation !== 'arrows' && state.config.navigation !== 'both') {
      return;
    }

    const itemsVisible = getItemsVisible(state);
    const maxPage = Math.ceil(state.config.totalSlides / itemsVisible) - 1;

    if (!state.config.looping) {
      if (state.prevButton) {
        state.prevButton.classList.toggle('vvjb-hidden', state.pageIndex === 0);
      }

      if (state.nextButton) {
        state.nextButton.classList.toggle('vvjb-hidden', state.pageIndex >= maxPage);
      }
    }
    else {
      if (state.prevButton) {
        state.prevButton.classList.remove('vvjb-hidden');
      }

      if (state.nextButton) {
        state.nextButton.classList.remove('vvjb-hidden');
      }
    }
  }

  /**
   * Announce visible slides to screen readers.
   *
   * @param {Object} state
   *   The carousel state object.
   * @param {number} start
   *   Start index of visible items.
   * @param {number} end
   *   End index of visible items.
   */
  function announceVisible(state, start, end) {
    if (!state.announcer) {
      return;
    }

    const total = state.config.totalSlides;

    // Security: Use textContent to prevent XSS.
    state.announcer.textContent = Drupal.t('Showing slides @start to @end of @total', {
      '@start': start + 1,
      '@end': Math.min(end, total),
      '@total': total,
    });
  }

  /**
   * Navigate to next page.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function nextSlide(state) {
    const itemsVisible = getItemsVisible(state);
    const totalPages = Math.ceil(state.config.totalSlides / itemsVisible);

    state.pageIndex = (state.pageIndex >= totalPages - 1)
      ? (state.config.looping ? 0 : totalPages - 1)
      : state.pageIndex + 1;

    updateLayout(state);
    updateDeepLinkHash(state);
  }

  /**
   * Navigate to previous page.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function prevSlide(state) {
    const itemsVisible = getItemsVisible(state);
    const maxPage = Math.ceil(state.config.totalSlides / itemsVisible) - 1;

    state.pageIndex = (state.pageIndex > 0)
      ? state.pageIndex - 1
      : (state.config.looping ? maxPage : 0);

    updateLayout(state);
    updateDeepLinkHash(state);
  }

  /**
   * Navigate to first page.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function goToFirstPage(state) {
    state.pageIndex = 0;
    updateLayout(state);
    updateDeepLinkHash(state);
  }

  /**
   * Navigate to last page.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function goToLastPage(state) {
    const itemsVisible = getItemsVisible(state);
    const maxPage = Math.ceil(state.config.totalSlides / itemsVisible) - 1;
    state.pageIndex = maxPage;
    updateLayout(state);
    updateDeepLinkHash(state);
  }

  /**
   * Bind navigation button events.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function bindNavigation(state) {
    const shouldAutoplay = state.config.slideTime > 0 &&
      state.config.slideTime >= 1000 &&
      state.config.slideTime <= 15000;

    const resetAutoSlide = () => {
      if (shouldAutoplay && state.autoSlideTimer) {
        stopAutoSlide(state);
        if (!state.isPaused) {
          startAutoSlide(state);
        }
      }
    };

    state.nextButton?.addEventListener('click', () => {
      nextSlide(state);
      resetAutoSlide();
    });

    state.prevButton?.addEventListener('click', () => {
      prevSlide(state);
      resetAutoSlide();
    });
  }

  /**
   * Bind touch/swipe gesture events.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function bindTouch(state) {
    let startX = 0;
    let startY = 0;

    state.wrapper.addEventListener('touchstart', e => {
      startX = e.touches[0].clientX;
      startY = e.touches[0].clientY;
    }, { passive: true });

    state.wrapper.addEventListener('touchend', e => {
      const deltaX = e.changedTouches[0].clientX - startX;
      const deltaY = e.changedTouches[0].clientY - startY;

      // Horizontal swipe detection
      if (Math.abs(deltaX) > Math.abs(deltaY)) {
        // Reverse swipe direction in RTL mode
        if (state.isRTL) {
          if (deltaX < -50) {
            prevSlide(state);  // Swipe left = previous in RTL
          }
          else if (deltaX > 50) {
            nextSlide(state);  // Swipe right = next in RTL
          }
        }
        else {
          if (deltaX < -50) {
            nextSlide(state);  // Swipe left = next in LTR
          }
          else if (deltaX > 50) {
            prevSlide(state);  // Swipe right = previous in LTR
          }
        }
      }
      // Vertical swipe detection for vertical orientation
      else if (state.config.orientation === 'vertical') {
        if (deltaY < -50) {
          nextSlide(state);
        }
        else if (deltaY > 50) {
          prevSlide(state);
        }
      }
    }, { passive: true });
  }

  /**
   * Create dot navigation elements.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function createDots(state) {
    const navigationType = state.config.navigation;
    const dotsContainer = state.container.querySelector('.vvjb-carousel-dots');

    if (!dotsContainer) {
      return;
    }

    if (navigationType !== 'dots' && navigationType !== 'both') {
      dotsContainer.innerHTML = '';
      return;
    }

    // Performance: Use DocumentFragment for efficient DOM manipulation.
    const fragment = document.createDocumentFragment();
    const itemsVisible = getItemsVisible(state);
    const totalPages = Math.ceil(state.config.totalSlides / itemsVisible);

    // Check if deep linking is enabled.
    const useDeepLinks = state.config.deeplinkEnabled && state.config.deeplinkId;

    for (let i = 0; i < totalPages; i++) {
      let dotElement;

      if (useDeepLinks) {
        // Create anchor link for deep linking.
        dotElement = document.createElement('a');
        dotElement.href = `#carousel-${state.config.deeplinkId}-${i + 1}`;
        dotElement.className = 'vvjb-carousel-dot';
        dotElement.setAttribute('role', 'button');
      } else {
        // Create button (original behavior).
        dotElement = document.createElement('button');
        dotElement.className = 'vvjb-carousel-dot';
        dotElement.type = 'button';
      }

      // Security: Use textContent for ARIA label.
      const label = Drupal.t('Go to slide group @num', {'@num': i + 1});
      dotElement.setAttribute('aria-label', label);
      dotElement.dataset.slideGroup = i;

      fragment.appendChild(dotElement);
    }

    // Clear and append all at once for performance.
    dotsContainer.innerHTML = '';
    dotsContainer.appendChild(fragment);
  }

  /**
   * Bind dot navigation click events.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function bindDots(state) {
    if (state.config.navigation !== 'dots' && state.config.navigation !== 'both') {
      return;
    }

    const dotsContainer = state.container.querySelector('.vvjb-carousel-dots');

    if (!dotsContainer) {
      return;
    }

    dotsContainer.querySelectorAll('.vvjb-carousel-dot').forEach(dot => {
      dot.addEventListener('click', () => {
        const groupIndex = parseInt(dot.dataset.slideGroup, 10);

        // Security: Validate group index.
        if (isNaN(groupIndex) || groupIndex < 0) {
          return;
        }

        state.pageIndex = groupIndex;
        updateLayout(state);
        updateDeepLinkHash(state);
      });
    });
  }

  /**
   * Start auto-slide functionality.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function startAutoSlide(state) {
    let interval = parseInt(state.config.slideTime, 10);

    if (interval === 0) {
      return;
    }

    // Security: Validate interval range.
    if (isNaN(interval) || interval < 1000 || interval > 15000) {
      interval = 5000;
    }

    stopAutoSlide(state);

    state.autoSlideTimer = setInterval(() => {
      if (!state.isPaused) {
        nextSlide(state);
      }
    }, interval);

    // Start progress bar if enabled.
    if (state.features.showProgressBar) {
      startProgressBar(state);
    }
  }

  /**
   * Stop auto-slide functionality.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function stopAutoSlide(state) {
    if (state.autoSlideTimer) {
      clearInterval(state.autoSlideTimer);
      state.autoSlideTimer = null;
    }

    stopProgressBar(state);
  }

  /**
   * Bind auto-slide with visibility and hover detection.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function bindAutoSlide(state) {
    let isVisible = true;

    const start = () => {
      if (!state.isPaused) {
        startAutoSlide(state);
      }
    };

    const stop = () => {
      stopAutoSlide(state);
    };

    // Initial start.
    start();

    // Pause on hover if enabled.
    if (state.features.enablePauseOnHover) {
      state.wrapper.addEventListener('mouseenter', stop);
      state.wrapper.addEventListener('mouseleave', () => {
        if (isVisible && !state.isPaused) {
          start();
        }
      });
    }

    // Pause when document is hidden.
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        stop();
      }
      else if (isVisible && !state.isPaused) {
        start();
      }
    });

    // Pause when carousel is out of viewport.
    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries) => {
        isVisible = entries[0].isIntersecting;

        if (isVisible && !state.isPaused) {
          start();
        }
        else {
          stop();
        }
      }, {
        threshold: 0.3
      });

      observer.observe(state.container);
      state.observers.push(observer);
    }
  }

  /**
   * Bind keyboard navigation events.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function bindKeyboard(state) {
    // Make container focusable for keyboard navigation.
    state.container.setAttribute('tabindex', '0');

    state.container.addEventListener('keydown', (e) => {
      // Skip if focused on input/textarea/contenteditable.
      if (e.target.matches('input, textarea, [contenteditable="true"]')) {
        return;
      }

      const orientation = state.config.orientation;
      let handled = false;

      // Arrow key navigation (orientation-aware and RTL-aware)
      if (orientation === 'horizontal') {
        // Reverse left/right arrows in RTL mode
        if (state.isRTL) {
          if (e.key === 'ArrowRight') {
            e.preventDefault();
            prevSlide(state);  // Right arrow = previous in RTL
            handled = true;
          }
          else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            nextSlide(state);  // Left arrow = next in RTL
            handled = true;
          }
        }
        else {
          if (e.key === 'ArrowRight') {
            e.preventDefault();
            nextSlide(state);  // Right arrow = next in LTR
            handled = true;
          }
          else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            prevSlide(state);  // Left arrow = previous in LTR
            handled = true;
          }
        }
      }
      else if (orientation === 'vertical') {
        // Vertical orientation - no RTL changes needed
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          nextSlide(state);
          handled = true;
        }
        else if (e.key === 'ArrowUp') {
          e.preventDefault();
          prevSlide(state);
          handled = true;
        }
      }
      // Space key: toggle play/pause.
      else if (e.key === ' ' || e.key === 'Spacebar') {
        e.preventDefault();
        if (state.features.showPlayPause) {
          togglePlayPause(state);
        }
        handled = true;
      }
      // Home key: go to first page.
      else if (e.key === 'Home') {
        e.preventDefault();
        goToFirstPage(state);
        handled = true;
      }
      // End key: go to last page.
      else if (e.key === 'End') {
        e.preventDefault();
        goToLastPage(state);
        handled = true;
      }

      // Restart auto-slide after manual navigation.
      if (handled && !state.isPaused && state.config.slideTime > 0) {
        stopAutoSlide(state);
        startAutoSlide(state);
      }
    });
  }

  /**
   * Clamp page index to valid range.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function clampPageIndex(state) {
    const itemsVisible = getItemsVisible(state);
    const maxPage = Math.ceil(state.config.totalSlides / itemsVisible) - 1;
    state.pageIndex = Math.min(state.pageIndex, maxPage);
  }

  /**
   * Clean up carousel resources.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function cleanupCarousel(state) {
    // Stop timers.
    stopAutoSlide(state);
    stopProgressBar(state);

    // Disconnect observers.
    if (state.observers && state.observers.length) {
      state.observers.forEach(observer => {
        if (observer && typeof observer.disconnect === 'function') {
          observer.disconnect();
        }
      });
      state.observers = [];
    }

    // Remove resize handler.
    if (state.resizeHandler) {
      window.removeEventListener('resize', state.resizeHandler);
    }

    // Remove hash change listener.
    if (state.eventHandlers && state.eventHandlers.hashChange) {
      window.removeEventListener('hashchange', state.eventHandlers.hashChange);
    }
  }

  /**
   * Initialize deep linking functionality.
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function initializeDeepLinking(state) {
    const deeplinkId = state.config.deeplinkId;
    
    // Check URL hash on page load.
    const hash = window.location.hash;
    if (hash && hash.startsWith(`#carousel-${deeplinkId}-`)) {
      const slideNumber = parseInt(hash.split('-').pop(), 10);
      if (!isNaN(slideNumber) && slideNumber > 0) {
        const itemsVisible = getItemsVisible(state);
        const totalPages = Math.ceil(state.config.totalSlides / itemsVisible);
        const pageIndex = slideNumber - 1;
        if (pageIndex >= 0 && pageIndex < totalPages) {
          state.pageIndex = pageIndex;
          updateLayout(state);
        } else {
          // Invalid slide number - log warning and clear hash
          if (typeof console !== 'undefined' && console.warn) {
            console.warn(`VVJB: Invalid slide number ${slideNumber} in URL. Valid range: 1-${totalPages}. Defaulting to page 1.`);
          }
          // Clear invalid hash
          if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', window.location.pathname + window.location.search);
          }
        }
      } else {
        // Invalid hash format
        if (typeof console !== 'undefined' && console.warn) {
          console.warn(`VVJB: Invalid hash format "${hash}". Expected format: #carousel-${deeplinkId}-[number]`);
        }
      }
    } else if (hash && hash.startsWith('#carousel-')) {
      // Hash is for a different carousel - silently ignore (multiple carousels on page)
      // No warning needed as this is normal behavior
    }

    // Listen for hash changes.
    const hashChangeHandler = function() {
      const hash = window.location.hash;
      if (hash && hash.startsWith(`#carousel-${deeplinkId}-`)) {
        const slideNumber = parseInt(hash.split('-').pop(), 10);
        if (!isNaN(slideNumber) && slideNumber > 0) {
          const itemsVisible = getItemsVisible(state);
          const totalPages = Math.ceil(state.config.totalSlides / itemsVisible);
          const pageIndex = slideNumber - 1;
          if (pageIndex >= 0 && pageIndex < totalPages) {
            state.pageIndex = pageIndex;
            updateLayout(state);
          } else {
            // Invalid slide number during navigation
            if (typeof console !== 'undefined' && console.warn) {
              console.warn(`VVJB: Invalid slide number ${slideNumber}. Valid range: 1-${totalPages}.`);
            }
          }
        }
      }
    };

    window.addEventListener('hashchange', hashChangeHandler);
    
    // Store handler for cleanup.
    if (!state.eventHandlers) {
      state.eventHandlers = {};
    }
    state.eventHandlers.hashChange = hashChangeHandler;
  }

  /**
   * Update URL hash when navigating (without adding to history).
   *
   * @param {Object} state
   *   The carousel state object.
   */
  function updateDeepLinkHash(state) {
    if (state.config.deeplinkEnabled && state.config.deeplinkId) {
      const newHash = `#carousel-${state.config.deeplinkId}-${state.pageIndex + 1}`;
      if (window.location.hash !== newHash) {
        history.replaceState(null, '', newHash);
      }
    }
  }

  /**
   * Public API for external carousel control.
   */
  Drupal.vvjb = Drupal.vvjb || {};

  /**
   * Helper to get container by identifier.
   */
  function getContainerByIdentifier(identifier) {
    let container = document.querySelector(`[data-deeplink-id="${identifier}"]`);
    if (!container) {
      container = document.querySelector(identifier);
    }
    return container;
  }

  /**
   * Helper to get state from identifier.
   */
  function getStateFromIdentifier(identifier) {
    const container = getContainerByIdentifier(identifier);
    if (!container) return null;
    
    const inner = container.closest('.vvjb-inner');
    return inner ? inner.vvjbState : null;
  }

  /**
   * Navigate to specific slide page.
   */
  Drupal.vvjb.goToSlide = function(identifier, slideNumber) {
    const state = getStateFromIdentifier(identifier);
    if (!state) {
      console.warn(`VVJB: Carousel "${identifier}" not found`);
      return false;
    }
    
    const itemsVisible = getItemsVisible(state);
    const totalPages = Math.ceil(state.config.totalSlides / itemsVisible);
    const pageIndex = slideNumber - 1;
    
    if (pageIndex < 0 || pageIndex >= totalPages) {
      console.warn(`VVJB: Invalid slide ${slideNumber}. Must be between 1 and ${totalPages}`);
      return false;
    }
    
    state.pageIndex = pageIndex;
    updateLayout(state);
    updateDeepLinkHash(state);
    return true;
  };

  /**
   * Get current slide page number.
   */
  Drupal.vvjb.getCurrentSlide = function(identifier) {
    const state = getStateFromIdentifier(identifier);
    return state ? state.pageIndex + 1 : null;
  };

  /**
   * Get total number of slide pages.
   */
  Drupal.vvjb.getTotalSlides = function(identifier) {
    const state = getStateFromIdentifier(identifier);
    if (!state) return null;
    const itemsVisible = getItemsVisible(state);
    return Math.ceil(state.config.totalSlides / itemsVisible);
  };

  /**
   * Navigate to next slide.
   */
  Drupal.vvjb.nextSlide = function(identifier) {
    const state = getStateFromIdentifier(identifier);
    if (!state) return false;
    nextSlide(state);
    return true;
  };

  /**
   * Navigate to previous slide.
   */
  Drupal.vvjb.prevSlide = function(identifier) {
    const state = getStateFromIdentifier(identifier);
    if (!state) return false;
    prevSlide(state);
    return true;
  };

  /**
   * Pause carousel.
   */
  Drupal.vvjb.pause = function(identifier) {
    const state = getStateFromIdentifier(identifier);
    if (!state || state.isPaused) return false;
    togglePlayPause(state);
    return true;
  };

  /**
   * Resume carousel.
   */
  Drupal.vvjb.resume = function(identifier) {
    const state = getStateFromIdentifier(identifier);
    if (!state || !state.isPaused) return false;
    togglePlayPause(state);
    return true;
  };

  /**
   * Get carousel instance.
   */
  Drupal.vvjb.getInstance = function(containerOrSelector) {
    const container = typeof containerOrSelector === 'string' 
      ? document.querySelector(containerOrSelector)
      : containerOrSelector;
    
    if (!container) return null;
    const inner = container.closest('.vvjb-inner');
    return inner ? inner.vvjbState : null;
  };

})(Drupal, drupalSettings, once);
