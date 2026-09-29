/**
 * @file
 * VvjbCarouselElement — `<vvjb-carousel>` custom element.
 *
 * Extends `Drupal.Vvj.ElementBase` from `vvj_core` for the standard
 * lifecycle. The base class is pulled from the `Drupal.Vvj` namespace —
 * Drupal core's cross-file sharing pattern — with load order guaranteed
 * by the `vvjb` library's `dependencies:`.
 *
 * Implements the full v1 vvjb behavior set:
 *   - Horizontal / vertical / hybrid orientations with breakpoint switch
 *   - Autoplay with configurable interval, pause-on-hover, pause-when-hidden,
 *     pause-when-out-of-viewport, pause-on-reduced-motion
 *   - Looping
 *   - Touch / swipe gestures (RTL-aware)
 *   - Keyboard navigation (arrow keys, Space, Home, End)
 *   - Arrow + dot navigation; scrollable-dots overflow indicators
 *   - Play/pause button, progress bar (RAF-driven), page counter
 *   - Deep-linking via URL hash (#carousel-{id}-{n})
 *   - ResizeObserver-driven layout updates
 *   - ARIA attributes + screen-reader announcements
 *
 * Public methods (used by the Drupal.vvjb.* compat shim):
 *   - goToSlide(slideNumber: number) → boolean
 *   - getCurrentSlide() → number
 *   - getTotalSlides() → number
 *   - nextSlide() → boolean
 *   - prevSlide() → boolean
 *   - pause() → boolean
 *   - resume() → boolean
 *
 * @license SPDX-License-Identifier: GPL-2.0-or-later
 */

// phpcs:disable Drupal.NamingConventions.UpperCaseConstant
// phpcs:disable Generic.PHP.UpperCaseConstant
// phpcs:disable Squiz.WhiteSpace.OperatorSpacing

((Drupal) => {
  const { ElementBase } = Drupal.Vvj;

  const SVG_PLAY = `<svg class="svg-play" xmlns="http://www.w3.org/2000/svg" viewBox="80 -880 800 800" fill="currentColor" aria-hidden="true">
  <path d="m380-300 280-180-280-180v360ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"></path>
</svg>`;

  const SVG_PAUSE = `<svg class="svg-pause" xmlns="http://www.w3.org/2000/svg" viewBox="80 -880 800 800" fill="currentColor" aria-hidden="true">
  <path d="M360-320h80v-320h-80v320Zm160 0h80v-320h-80v320ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"></path>
</svg>`;

  const VALID_BREAKPOINTS = [576, 768, 992, 1200, 1400];

  function parseIntAllowZero(value, defaultValue) {
    const parsed = parseInt(value, 10);
    return Number.isFinite(parsed) && parsed >= 0 ? parsed : defaultValue;
  }

  function parseIntSafe(value, defaultValue, minValue = 0) {
    const parsed = parseInt(value, 10);
    return Number.isFinite(parsed) && parsed >= minValue
      ? parsed
      : defaultValue;
  }

  function isRTLContext(element) {
    return (
      element.closest('[dir="rtl"]') !== null ||
      document.documentElement.dir === 'rtl' ||
      document.body.dir === 'rtl' ||
      getComputedStyle(element).direction === 'rtl'
    );
  }

  function debounce(fn, delay) {
    let timer;
    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => fn(...args), delay);
    };
  }

  class VvjbCarouselElement extends ElementBase {
    static get patternSlug() {
      return 'vvjb';
    }

    constructor() {
      super();
      this._inner = null;
      this._wrapper = null;
      this._itemsContainer = null;
      this._items = [];
      this._nextButton = null;
      this._prevButton = null;
      this._announcer = null;
      this._playPauseButton = null;
      this._progressBar = null;
      this._currentPageSpan = null;
      this._totalPagesSpan = null;
      this._dotsContainer = null;

      this._config = {};
      this._features = {};
      this._pageIndex = 0;
      this._isPaused = false;
      this._autoSlideTimer = null;
      this._progressRaf = null;
      this._pageStartTime = null;
      this._resizeObserver = null;
      this._intersectionObserver = null;
      this._isInViewport = true;
      this._centerOffset = 0;
      this._isRTL = false;
    }

    // -----------------------------------------------------------------
    // Hydration.
    // -----------------------------------------------------------------

    onHydrate() {
      this._inner = this.querySelector(':scope > .vvjb-inner');
      if (!this._inner) {
        return;
      }

      this._wrapper = this._inner.querySelector('.vvjb-carousel-wrapper');
      this._itemsContainer = this._wrapper?.querySelector('.vvjb-items');
      if (!this._wrapper || !this._itemsContainer) {
        console.error('VVJB: Required DOM structure missing', this);
        return;
      }

      const items = this._itemsContainer.querySelectorAll('.vvjb-item');
      if (!items.length) {
        return;
      }
      this._items = items;

      this._nextButton = this._inner.querySelector('.vvjb-next');
      this._prevButton = this._inner.querySelector('.vvjb-prev');
      this._announcer = this._inner.querySelector('.vvjb-carousel-announcer');
      this._playPauseButton = this._inner.querySelector(
        '.vvjb-play-pause-button',
      );
      this._progressBar = this._inner.querySelector('.vvjb-progress-bar');
      this._currentPageSpan = this._inner.querySelector('.vvjb-current-page');
      this._totalPagesSpan = this._inner.querySelector('.vvjb-total-pages');
      this._dotsContainer = this._inner.querySelector('.vvjb-carousel-dots');

      this._readConfig();
      this._initFeatures();

      requestAnimationFrame(() =>
        requestAnimationFrame(() => {
          this._updateLayout();
          this._createDots();
          this._bindDots();
          if (this._config.deeplinkEnabled && this._config.deeplinkId) {
            this._initDeepLinking();
          }
          this._inner.removeAttribute('hidden');
          setTimeout(() => this._updateLayout(), 100);
        }),
      );

      this._inner.setAttribute('data-vvjb-initialized', 'true');
    }

    onDisconnect() {
      this._stopAutoSlide();
      this._stopProgressBar();
      this._resizeObserver?.disconnect();
      this._intersectionObserver?.disconnect();
      this._resizeObserver = null;
      this._intersectionObserver = null;
    }

    // -----------------------------------------------------------------
    // Configuration.
    // -----------------------------------------------------------------

    _readConfig() {
      const itemsDs = this._itemsContainer.dataset;
      const innerDs = this._inner.dataset;

      const breakpointParsed = parseInt(itemsDs.breakpoints, 10);
      const breakpoints = VALID_BREAKPOINTS.includes(breakpointParsed)
        ? breakpointParsed
        : 992;

      const dotsContainerDs = this._dotsContainer?.dataset ?? {};
      const scrollableDotsWidth = parseIntSafe(
        dotsContainerDs.scrollableDotsWidth,
        0,
        0,
      );

      this._isRTL = isRTLContext(this);

      this._config = {
        rawOrientation: itemsDs.orientation || 'horizontal',
        orientation: 'horizontal',
        breakpoints,
        itemsSmall: parseIntSafe(itemsDs.smallScreen, 1, 1),
        itemsBig: parseIntSafe(itemsDs.bigScreen, 3, 1),
        totalSlides: parseIntSafe(itemsDs.totalSlides, this._items.length, 1),
        slideTime: parseIntAllowZero(itemsDs.slideTime, 5000),
        gap: parseIntAllowZero(itemsDs.gap, 0),
        looping: itemsDs.carouselLoop === '1',
        navigation: itemsDs.navigation || 'both',
        deeplinkEnabled: itemsDs.deeplinkEnabled === 'true',
        deeplinkId: itemsDs.deeplinkId || '',
        scrollableDotsWidth,
      };

      this._features = {
        showPlayPause: innerDs.showPlayPause === 'true',
        showProgressBar: innerDs.showProgressBar === 'true',
        showPageCounter: innerDs.showPageCounter === 'true',
        enableKeyboardNav: innerDs.enableKeyboardNav === 'true',
        enableTouchSwipe: innerDs.enableTouchSwipe === 'true',
        enablePauseOnHover: innerDs.enablePauseOnHover === 'true',
        pauseOnReducedMotion: innerDs.pauseOnReducedMotion === 'true',
      };
    }

    _initFeatures() {
      this._bindNavigation();

      if (this._features.enableTouchSwipe) {
        this._bindTouch();
      }

      if (this._config.slideTime > 0) {
        this._bindAutoSlide();
      }

      if (this._features.enableKeyboardNav) {
        this._bindKeyboard();
      }

      if (this._features.showPlayPause && this._playPauseButton) {
        this._initPlayPauseButton();
      }

      if (this._features.showProgressBar && this._progressBar) {
        this._progressBar.setAttribute('role', 'progressbar');
        this._progressBar.setAttribute('aria-valuemin', '0');
        this._progressBar.setAttribute('aria-valuemax', '100');
        this._progressBar.setAttribute('aria-valuenow', '0');
      }

      if (this._features.showPageCounter && this._currentPageSpan) {
        this._updatePageCounter();
      }

      if (this._features.pauseOnReducedMotion) {
        this._applyReducedMotion();
      }

      if ('ResizeObserver' in window && this._wrapper) {
        this._resizeObserver = new ResizeObserver(() => this._updateLayout());
        this._resizeObserver.observe(this._wrapper);
      }

      const refreshDots = debounce(() => {
        this._createDots();
        this._bindDots();
      }, 300);

      window.addEventListener(
        'resize',
        () => {
          this._clampPageIndex();
          this._updateLayout();
          refreshDots();
        },
        { signal: this.signal },
      );
    }

    // -----------------------------------------------------------------
    // Orientation + layout math.
    // -----------------------------------------------------------------

    _resolveOrientation() {
      const raw = this._config.rawOrientation;
      const bp = this._config.breakpoints;
      if (raw === 'vertical') {
        return 'vertical';
      }
      if (raw === 'hybrid' && window.innerWidth <= bp) {
        return 'vertical';
      }
      return 'horizontal';
    }

    _getItemSize(item, isVertical) {
      return isVertical ? item.offsetHeight : item.offsetWidth;
    }

    _getItemsVisible() {
      const isVertical = this._config.orientation === 'vertical';
      const isHybrid = this._config.rawOrientation === 'hybrid';
      const isSmallScreen = window.innerWidth <= this._config.breakpoints;
      const max = isSmallScreen
        ? this._config.itemsSmall
        : this._config.itemsBig;

      if (isVertical) {
        return max;
      }
      if (isHybrid && isSmallScreen) {
        return max;
      }

      const containerSize = this._wrapper.offsetWidth;
      const gap = this._config.gap || 0;
      let total = 0;
      let count = 0;

      for (let i = 0; i < this._items.length; i++) {
        const itemSize = this._items[i].offsetWidth;
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

    _getVisibleItems() {
      const itemsVisible = this._getItemsVisible();
      const start = this._pageIndex * itemsVisible;
      return Array.from(this._items).slice(start, start + itemsVisible);
    }

    _getGroupOffset() {
      const itemsVisible = this._getItemsVisible();
      const isVertical = this._config.orientation === 'vertical';
      const gap = this._config.gap || 0;
      let offset = 0;

      for (let page = 0; page < this._pageIndex; page++) {
        const start = page * itemsVisible;
        const end = start + itemsVisible;
        for (let i = start; i < end && i < this._items.length; i++) {
          offset += this._getItemSize(this._items[i], isVertical);
          if (i < end - 1) {
            offset += gap;
          }
        }
        if (end < this._items.length) {
          offset += gap;
        }
      }

      return offset;
    }

    _setWrapperSize() {
      const itemsVisible = this._getItemsVisible();
      if (!this._items.length) {
        return;
      }

      const visibleItems = this._getVisibleItems();
      const gap = this._config.gap || 0;
      const totalGap = (itemsVisible - 1) * gap;

      if (this._config.orientation === 'vertical') {
        const totalHeight = visibleItems.reduce(
          (sum, item) => sum + this._getItemSize(item, true),
          0,
        );
        this._wrapper.style.maxHeight = `${totalHeight + totalGap}px`;
        this._wrapper.style.height = '';
        this._wrapper.style.maxWidth = '';
        this._centerOffset = 0;
      } else {
        const totalWidth = visibleItems.reduce(
          (sum, item, idx) =>
            sum + this._getItemSize(item, false) + (idx > 0 ? gap : 0),
          0,
        );
        const wrapperWidth = this._wrapper.offsetWidth;
        const centerOffset = (wrapperWidth - totalWidth) / 2;
        this._centerOffset = centerOffset > 0 ? centerOffset : 0;
        this._wrapper.style.maxWidth = '';
        this._wrapper.style.width = '';
        this._wrapper.style.maxHeight = '';
      }

      if (this._wrapper.style.visibility !== 'visible') {
        this._wrapper.style.visibility = 'visible';
        this._wrapper.classList.add('is-visible');
      }
    }

    _scrollToPage() {
      const offset = this._getGroupOffset();
      const centering = this._centerOffset || 0;

      if (this._config.orientation === 'vertical') {
        const transform = Math.round(-offset);
        this._itemsContainer.style.transform = `translateY(${transform}px)`;
      } else {
        const transform = Math.round(
          this._isRTL ? -(centering - offset) : centering - offset,
        );
        this._itemsContainer.style.transform = `translateX(${transform}px)`;
      }
    }

    _updateLayout() {
      this._config.orientation = this._resolveOrientation();
      this._setWrapperSize();

      const itemsVisible = this._getItemsVisible();
      const start = this._pageIndex * itemsVisible;
      const end = start + itemsVisible;

      this._items.forEach((item, i) => {
        const isActive = i >= start && i < end;
        item.classList.toggle('active-slide', isActive);
        item.classList.toggle('non-active-slide', !isActive);
        item.setAttribute('aria-hidden', String(!isActive));
        if (isActive) {
          item.removeAttribute('inert');
        } else {
          item.setAttribute('inert', '');
        }
        const interactiveElements = item.querySelectorAll(
          'a, button, input, textarea, select, [tabindex]',
        );
        interactiveElements.forEach((el) => {
          el.setAttribute('tabindex', isActive ? '0' : '-1');
        });
      });

      if (
        this._config.navigation === 'dots' ||
        this._config.navigation === 'both'
      ) {
        const dots = this._inner.querySelectorAll('.vvjb-carousel-dot');
        dots.forEach((dot, index) => {
          const isActive = index === this._pageIndex;
          dot.classList.toggle('active-slide', isActive);
          dot.setAttribute('aria-current', isActive ? 'true' : 'false');
        });
        if (this._config.scrollableDotsWidth > 0 && this._dotsContainer) {
          this._centerActiveDot();
        }
      }

      this._updateNavVisibility();
      this._announceVisible(start, end);
      this._scrollToPage();

      if (this._features.showPageCounter) {
        this._updatePageCounter();
      }

      if (this._features.showProgressBar && !this._isPaused) {
        this._resetProgressBar();
        this._startProgressBar();
      }
    }

    _updateNavVisibility() {
      if (
        this._config.navigation !== 'arrows' &&
        this._config.navigation !== 'both'
      ) {
        return;
      }
      const itemsVisible = this._getItemsVisible();
      const maxPage = Math.ceil(this._config.totalSlides / itemsVisible) - 1;
      if (!this._config.looping) {
        this._prevButton?.classList.toggle(
          'vvjb-hidden',
          this._pageIndex === 0,
        );
        this._nextButton?.classList.toggle(
          'vvjb-hidden',
          this._pageIndex >= maxPage,
        );
      } else {
        this._prevButton?.classList.remove('vvjb-hidden');
        this._nextButton?.classList.remove('vvjb-hidden');
      }
    }

    _announceVisible(start, end) {
      if (!this._announcer) {
        return;
      }
      const total = this._config.totalSlides;
      this._announcer.textContent = Drupal.t(
        'Showing slides @start to @end of @total',
        {
          '@start': start + 1,
          '@end': Math.min(end, total),
          '@total': total,
        },
      );
    }

    _clampPageIndex() {
      const itemsVisible = this._getItemsVisible();
      const maxPage = Math.ceil(this._config.totalSlides / itemsVisible) - 1;
      this._pageIndex = Math.min(this._pageIndex, maxPage);
    }

    // -----------------------------------------------------------------
    // Navigation primitives.
    // -----------------------------------------------------------------

    _nextSlide() {
      const itemsVisible = this._getItemsVisible();
      const totalPages = Math.ceil(this._config.totalSlides / itemsVisible);
      if (this._pageIndex < totalPages - 1) {
        this._pageIndex += 1;
      } else {
        // Past the last page: wrap to the start when looping, else stay put.
        this._pageIndex = this._config.looping ? 0 : totalPages - 1;
      }
      this.withTransition(() => this._updateLayout());
      this._updateDeepLinkHash();
    }

    _prevSlide() {
      const itemsVisible = this._getItemsVisible();
      const maxPage = Math.ceil(this._config.totalSlides / itemsVisible) - 1;
      if (this._pageIndex > 0) {
        this._pageIndex -= 1;
      } else {
        // Before the first page: wrap to the end when looping, else stay put.
        this._pageIndex = this._config.looping ? maxPage : 0;
      }
      this.withTransition(() => this._updateLayout());
      this._updateDeepLinkHash();
    }

    _goToFirstPage() {
      this._pageIndex = 0;
      this.withTransition(() => this._updateLayout());
      this._updateDeepLinkHash();
    }

    _goToLastPage() {
      const itemsVisible = this._getItemsVisible();
      this._pageIndex = Math.ceil(this._config.totalSlides / itemsVisible) - 1;
      this.withTransition(() => this._updateLayout());
      this._updateDeepLinkHash();
    }

    _bindNavigation() {
      const shouldAutoplay =
        this._config.slideTime >= 1000 && this._config.slideTime <= 15000;
      const resetAutoSlide = () => {
        if (shouldAutoplay && this._autoSlideTimer) {
          this._stopAutoSlide();
          if (!this._isPaused) {
            this._startAutoSlide();
          }
        }
      };
      this._nextButton?.addEventListener(
        'click',
        () => {
          this._nextSlide();
          resetAutoSlide();
        },
        { signal: this.signal },
      );
      this._prevButton?.addEventListener(
        'click',
        () => {
          this._prevSlide();
          resetAutoSlide();
        },
        { signal: this.signal },
      );
    }

    _bindTouch() {
      let startX = 0;
      let startY = 0;
      this._wrapper.addEventListener(
        'touchstart',
        (e) => {
          startX = e.touches[0].clientX;
          startY = e.touches[0].clientY;
        },
        { passive: true, signal: this.signal },
      );
      this._wrapper.addEventListener(
        'touchend',
        (e) => {
          const deltaX = e.changedTouches[0].clientX - startX;
          const deltaY = e.changedTouches[0].clientY - startY;
          if (Math.abs(deltaX) > Math.abs(deltaY)) {
            if (this._isRTL) {
              if (deltaX < -50) {
                this._prevSlide();
              } else if (deltaX > 50) {
                this._nextSlide();
              }
            } else if (deltaX < -50) {
              this._nextSlide();
            } else if (deltaX > 50) {
              this._prevSlide();
            }
          } else if (this._config.orientation === 'vertical') {
            if (deltaY < -50) {
              this._nextSlide();
            } else if (deltaY > 50) {
              this._prevSlide();
            }
          }
        },
        { passive: true, signal: this.signal },
      );
    }

    _bindKeyboard() {
      this.setAttribute('tabindex', '0');
      this.addEventListener(
        'keydown',
        (e) => {
          if (e.target.matches('input, textarea, [contenteditable="true"]')) {
            return;
          }
          const orientation = this._config.orientation;
          let handled = false;

          if (orientation === 'horizontal') {
            if (this._isRTL) {
              if (e.key === 'ArrowRight') {
                e.preventDefault();
                this._prevSlide();
                handled = true;
              } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                this._nextSlide();
                handled = true;
              }
            } else if (e.key === 'ArrowRight') {
              e.preventDefault();
              this._nextSlide();
              handled = true;
            } else if (e.key === 'ArrowLeft') {
              e.preventDefault();
              this._prevSlide();
              handled = true;
            }
          } else if (orientation === 'vertical') {
            if (e.key === 'ArrowDown') {
              e.preventDefault();
              this._nextSlide();
              handled = true;
            } else if (e.key === 'ArrowUp') {
              e.preventDefault();
              this._prevSlide();
              handled = true;
            }
          }

          if (!handled) {
            if (e.key === ' ' || e.key === 'Spacebar') {
              e.preventDefault();
              if (this._features.showPlayPause) {
                this._togglePlayPause();
              }
              handled = true;
            } else if (e.key === 'Home') {
              e.preventDefault();
              this._goToFirstPage();
              handled = true;
            } else if (e.key === 'End') {
              e.preventDefault();
              this._goToLastPage();
              handled = true;
            }
          }

          if (handled && !this._isPaused && this._config.slideTime > 0) {
            this._stopAutoSlide();
            this._startAutoSlide();
          }
        },
        { signal: this.signal },
      );
    }

    // -----------------------------------------------------------------
    // Dots.
    // -----------------------------------------------------------------

    _createDots() {
      const navigationType = this._config.navigation;
      if (!this._dotsContainer) {
        return;
      }

      if (navigationType !== 'dots' && navigationType !== 'both') {
        this._dotsContainer.innerHTML = '';
        return;
      }

      const fragment = document.createDocumentFragment();
      const itemsVisible = this._getItemsVisible();
      const totalPages = Math.ceil(this._config.totalSlides / itemsVisible);
      const useDeepLinks =
        this._config.deeplinkEnabled && this._config.deeplinkId;

      for (let i = 0; i < totalPages; i++) {
        let dot;
        if (useDeepLinks) {
          dot = document.createElement('a');
          dot.href = `#carousel-${this._config.deeplinkId}-${i + 1}`;
          dot.className = 'vvjb-carousel-dot';
          dot.setAttribute('role', 'button');
        } else {
          dot = document.createElement('button');
          dot.className = 'vvjb-carousel-dot';
          dot.type = 'button';
        }
        const label = Drupal.t('Go to slide group @num', { '@num': i + 1 });
        dot.setAttribute('aria-label', label);
        dot.dataset.slideGroup = String(i);
        fragment.appendChild(dot);
      }

      this._dotsContainer.innerHTML = '';
      this._dotsContainer.appendChild(fragment);

      if (this._config.scrollableDotsWidth > 0) {
        this._applyScrollableDotsWidth();
      }
    }

    _bindDots() {
      if (
        this._config.navigation !== 'dots' &&
        this._config.navigation !== 'both'
      ) {
        return;
      }
      if (!this._dotsContainer) {
        return;
      }

      this._dotsContainer
        .querySelectorAll('.vvjb-carousel-dot')
        .forEach((dot) => {
          dot.addEventListener(
            'click',
            () => {
              const groupIndex = parseInt(dot.dataset.slideGroup, 10);
              if (!Number.isFinite(groupIndex) || groupIndex < 0) {
                return;
              }
              this._pageIndex = groupIndex;
              this.withTransition(() => this._updateLayout());
              this._updateDeepLinkHash();
            },
            { signal: this.signal },
          );
        });

      if (this._config.scrollableDotsWidth > 0) {
        this._initScrollableDots();
      }
    }

    _initScrollableDots() {
      if (!this._dotsContainer || this._config.scrollableDotsWidth <= 0) {
        return;
      }
      this._applyScrollableDotsWidth();
      this._dotsContainer.addEventListener(
        'scroll',
        () => {
          this._updateDotsOverflowIndicators();
        },
        { passive: true, signal: this.signal },
      );
      requestAnimationFrame(() => this._updateDotsOverflowIndicators());
    }

    _applyScrollableDotsWidth() {
      if (!this._dotsContainer || this._config.scrollableDotsWidth <= 0) {
        return;
      }
      this._dotsContainer.style.maxWidth = `${this._config.scrollableDotsWidth}px`;
      this._dotsContainer.style.justifyContent = 'flex-start';
      requestAnimationFrame(() => {
        this._centerActiveDot();
        this._updateDotsOverflowIndicators();
      });
    }

    _centerActiveDot() {
      if (!this._dotsContainer || this._config.scrollableDotsWidth <= 0) {
        return;
      }
      const activeDot = this._dotsContainer.querySelector(
        '.vvjb-carousel-dot.active-slide',
      );
      if (!activeDot) {
        return;
      }
      const containerWidth = this._dotsContainer.offsetWidth;
      const dotOffsetLeft = activeDot.offsetLeft;
      const dotWidth = activeDot.offsetWidth;
      const scrollTarget = dotOffsetLeft - containerWidth / 2 + dotWidth / 2;
      const maxScroll = this._dotsContainer.scrollWidth - containerWidth;
      const clampedScroll = Math.max(0, Math.min(scrollTarget, maxScroll));
      this._dotsContainer.scrollTo({ left: clampedScroll, behavior: 'smooth' });
    }

    _updateDotsOverflowIndicators() {
      if (!this._dotsContainer) {
        return;
      }
      const scrollLeft = this._dotsContainer.scrollLeft;
      const scrollWidth = this._dotsContainer.scrollWidth;
      const containerWidth = this._dotsContainer.offsetWidth;
      const maxScroll = scrollWidth - containerWidth;
      const threshold = 2;
      this._dotsContainer.classList.toggle(
        'has-overflow-start',
        scrollLeft > threshold,
      );
      this._dotsContainer.classList.toggle(
        'has-overflow-end',
        scrollLeft < maxScroll - threshold,
      );
    }

    // -----------------------------------------------------------------
    // Auto-slide + play/pause + progress.
    // -----------------------------------------------------------------

    _startAutoSlide() {
      let interval = parseInt(this._config.slideTime, 10);
      if (interval === 0) {
        return;
      }
      if (!Number.isFinite(interval) || interval < 1000 || interval > 15000) {
        interval = 5000;
      }
      this._stopAutoSlide();
      this._autoSlideTimer = setInterval(() => {
        if (!this._isPaused) {
          this._nextSlide();
        }
      }, interval);
      if (this._features.showProgressBar) {
        this._startProgressBar();
      }
    }

    _stopAutoSlide() {
      if (this._autoSlideTimer) {
        clearInterval(this._autoSlideTimer);
        this._autoSlideTimer = null;
      }
      this._stopProgressBar();
    }

    _bindAutoSlide() {
      // NEVER WHILE THE PAGE IS HIDDEN. A page that loads in a background tab
      // gets no visibilitychange, and the viewport observer still fires — so
      // autoplay started anyway, and every slide threw "Transition was aborted
      // because of invalid state. Document hidden" (measured 2026-09-27).
      const start = () => {
        if (!this._isPaused && !document.hidden) {
          this._startAutoSlide();
        }
      };
      const stop = () => this._stopAutoSlide();

      start();

      if (this._features.enablePauseOnHover) {
        this._wrapper.addEventListener('mouseenter', stop, {
          signal: this.signal,
        });
        this._wrapper.addEventListener(
          'mouseleave',
          () => {
            if (this._isInViewport && !this._isPaused) {
              start();
            }
          },
          { signal: this.signal },
        );
      }

      document.addEventListener(
        'visibilitychange',
        () => {
          if (document.hidden) {
            stop();
          } else if (this._isInViewport && !this._isPaused) {
            start();
          }
        },
        { signal: this.signal },
      );

      if ('IntersectionObserver' in window) {
        this._intersectionObserver = new IntersectionObserver(
          (entries) => {
            this._isInViewport = entries[0].isIntersecting;
            if (this._isInViewport && !this._isPaused) {
              start();
            } else {
              stop();
            }
          },
          { threshold: 0.3 },
        );
        this._intersectionObserver.observe(this);
      }
    }

    _initPlayPauseButton() {
      if (!this._playPauseButton) {
        return;
      }
      this._updatePlayPauseButton();
      this._playPauseButton.addEventListener(
        'click',
        () => {
          this._togglePlayPause();
        },
        { signal: this.signal },
      );
    }

    _updatePlayPauseButton() {
      if (!this._playPauseButton) {
        return;
      }
      this._playPauseButton.innerHTML = this._isPaused ? SVG_PLAY : SVG_PAUSE;
      const label = this._isPaused
        ? Drupal.t('Play carousel')
        : Drupal.t('Pause carousel');
      this._playPauseButton.setAttribute('aria-label', label);
    }

    _togglePlayPause() {
      this._isPaused = !this._isPaused;
      this._inner.classList.toggle('is-paused', this._isPaused);
      this._updatePlayPauseButton();

      if (this._isPaused) {
        this._stopAutoSlide();
        this._stopProgressBar();
        if (this._progressBar) {
          setTimeout(() => {
            if (this._isPaused) {
              this._resetProgressBar();
            }
          }, 300);
        }
      } else {
        this._startAutoSlide();
        this._startProgressBar();
      }
    }

    _startProgressBar() {
      if (!this._features.showProgressBar || !this._progressBar) {
        return;
      }
      if (this._progressRaf) {
        return;
      }
      if (this._config.slideTime <= 0) {
        return;
      }

      this._pageStartTime = Date.now();

      const update = () => {
        if (this._isPaused || !this._pageStartTime) {
          this._stopProgressBar();
          return;
        }
        const elapsed = Date.now() - this._pageStartTime;
        const progress = Math.min(
          100,
          (elapsed / this._config.slideTime) * 100,
        );
        this._progressBar.style.setProperty('--progress', `${progress}%`);
        this._progressBar.setAttribute(
          'aria-valuenow',
          String(Math.round(progress)),
        );
        if (progress < 100) {
          this._progressRaf = requestAnimationFrame(update);
        } else {
          this._stopProgressBar();
        }
      };
      this._progressRaf = requestAnimationFrame(update);
    }

    _stopProgressBar() {
      if (this._progressRaf) {
        cancelAnimationFrame(this._progressRaf);
        this._progressRaf = null;
      }
    }

    _resetProgressBar() {
      if (!this._progressBar) {
        return;
      }
      this._stopProgressBar();
      this._progressBar.style.setProperty('--progress', '0%');
      this._progressBar.setAttribute('aria-valuenow', '0');
    }

    _updatePageCounter() {
      if (!this._currentPageSpan || !this._totalPagesSpan) {
        return;
      }
      const itemsVisible = this._getItemsVisible();
      const totalPages = Math.ceil(this._config.totalSlides / itemsVisible);
      this._currentPageSpan.textContent = String(this._pageIndex + 1);
      this._totalPagesSpan.textContent = String(totalPages);
    }

    _applyReducedMotion() {
      if (!window.matchMedia) {
        return;
      }
      if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
      }
      this._isPaused = true;
      this._inner.classList.add('is-paused', 'reduced-motion');
      this._stopAutoSlide();
      if (this._playPauseButton) {
        this._updatePlayPauseButton();
      }
      if (this._announcer) {
        this._announcer.textContent = Drupal.t(
          'Carousel paused due to reduced motion preference',
        );
      }
    }

    // -----------------------------------------------------------------
    // Deep linking.
    // -----------------------------------------------------------------

    _initDeepLinking() {
      const id = this._config.deeplinkId;
      const applyHash = (hash) => {
        if (!hash || !hash.startsWith(`#carousel-${id}-`)) {
          return;
        }
        const slideNumber = parseInt(hash.split('-').pop(), 10);
        if (!Number.isFinite(slideNumber) || slideNumber <= 0) {
          return;
        }
        const itemsVisible = this._getItemsVisible();
        const totalPages = Math.ceil(this._config.totalSlides / itemsVisible);
        const pageIndex = slideNumber - 1;
        if (pageIndex < 0 || pageIndex >= totalPages) {
          return;
        }
        this._pageIndex = pageIndex;
        this._updateLayout();
      };

      applyHash(window.location.hash);

      window.addEventListener(
        'hashchange',
        () => {
          applyHash(window.location.hash);
        },
        { signal: this.signal },
      );
    }

    _updateDeepLinkHash() {
      if (!this._config.deeplinkEnabled || !this._config.deeplinkId) {
        return;
      }
      const newHash = `#carousel-${this._config.deeplinkId}-${this._pageIndex + 1}`;
      if (window.location.hash !== newHash) {
        window.history.replaceState(null, '', newHash);
      }
    }

    // -----------------------------------------------------------------
    // Public API used by the Drupal.vvjb.* compat shim.
    // -----------------------------------------------------------------

    goToSlide(slideNumber) {
      const itemsVisible = this._getItemsVisible();
      const totalPages = Math.ceil(this._config.totalSlides / itemsVisible);
      const pageIndex = slideNumber - 1;
      if (pageIndex < 0 || pageIndex >= totalPages) {
        console.warn(
          `VVJB: Invalid slide ${slideNumber}. Must be between 1 and ${totalPages}`,
        );
        return false;
      }
      this._pageIndex = pageIndex;
      this.withTransition(() => this._updateLayout());
      this._updateDeepLinkHash();
      return true;
    }

    getCurrentSlide() {
      return this._pageIndex + 1;
    }

    getTotalSlides() {
      const itemsVisible = this._getItemsVisible();
      return Math.ceil(this._config.totalSlides / itemsVisible);
    }

    nextSlide() {
      this._nextSlide();
      return true;
    }

    prevSlide() {
      this._prevSlide();
      return true;
    }

    pause() {
      if (this._isPaused) {
        return false;
      }
      this._togglePlayPause();
      return true;
    }

    resume() {
      if (!this._isPaused) {
        return false;
      }
      this._togglePlayPause();
      return true;
    }
  }

  if (!customElements.get('vvjb-carousel')) {
    customElements.define('vvjb-carousel', VvjbCarouselElement);
  }
})(Drupal);
