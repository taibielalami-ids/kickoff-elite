document.addEventListener("DOMContentLoaded", function () {
  const alerts = document.querySelectorAll(".alert");
  alerts.forEach(function (alert) {
    setTimeout(function () {
      alert.classList.add("fade");
      alert.classList.remove("show");
    }, 4500);
  });

  const anchorNav = document.getElementById("experienceAnchorNav");
  const progressBar = document.getElementById("scrollProgressBar");
  const backToTopBtn = document.getElementById("backToTopBtn");
  const heroParallaxLayer = document.getElementById("heroParallaxLayer");
  const heroSection = document.getElementById("hero");

  const anchorLinks = anchorNav ? Array.from(anchorNav.querySelectorAll(".anchor-link")) : [];
  const sectionIds = anchorLinks
    .map(function (link) {
      return (link.getAttribute("href") || "").replace("#", "");
    })
    .filter(function (id) {
      return id.length > 0;
    });
  const trackedSections = sectionIds
    .map(function (id) {
      return document.getElementById(id);
    })
    .filter(Boolean);

  anchorLinks.forEach(function (link) {
    link.addEventListener("click", function (event) {
      const href = link.getAttribute("href") || "";
      if (!href.startsWith("#")) {
        return;
      }
      const target = document.querySelector(href);
      if (!target) {
        return;
      }
      event.preventDefault();
      const y = window.pageYOffset + target.getBoundingClientRect().top - 95;
      window.scrollTo({ top: y, behavior: "smooth" });
    });
  });

  const revealSections = document.querySelectorAll(".reveal-section");
  if ("IntersectionObserver" in window) {
    const revealObserver = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
          }
        });
      },
      { threshold: 0.18, rootMargin: "0px 0px -40px 0px" }
    );
    revealSections.forEach(function (section) {
      revealObserver.observe(section);
    });
  } else {
    revealSections.forEach(function (section) {
      section.classList.add("is-visible");
    });
  }

  const lazyImages = document.querySelectorAll(".lazy-image");
  function markImageLoaded(img) {
    const wrapper = img.closest(".gallery-item");
    if (wrapper) {
      wrapper.classList.add("loaded");
    }
  }

  lazyImages.forEach(function (img) {
    if (img.complete && img.naturalWidth > 0) {
      markImageLoaded(img);
      return;
    }
    img.addEventListener("load", function () {
      markImageLoaded(img);
    });
    img.addEventListener("error", function () {
      markImageLoaded(img);
    });
  });

  if (backToTopBtn) {
    backToTopBtn.addEventListener("click", function () {
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }

  let ticking = false;

  function updateActiveAnchor() {
    if (anchorLinks.length === 0 || trackedSections.length === 0) {
      return;
    }

    const scrollMark = window.scrollY + 150;
    let activeId = trackedSections[0].id;

    trackedSections.forEach(function (section) {
      if (scrollMark >= section.offsetTop) {
        activeId = section.id;
      }
    });

    anchorLinks.forEach(function (link) {
      const hrefId = (link.getAttribute("href") || "").replace("#", "");
      link.classList.toggle("active", hrefId === activeId);
    });
  }

  function updateScrollProgress() {
    if (!progressBar) {
      return;
    }
    const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
    const progress = maxScroll > 0 ? (window.scrollY / maxScroll) * 100 : 0;
    progressBar.style.width = progress + "%";
  }

  function updateBackToTop() {
    if (!backToTopBtn) {
      return;
    }
    if (window.scrollY > 420) {
      backToTopBtn.classList.add("visible");
    } else {
      backToTopBtn.classList.remove("visible");
    }
  }

  function updateHeroParallax() {
    if (!heroParallaxLayer || !heroSection) {
      return;
    }
    const heroRect = heroSection.getBoundingClientRect();
    if (heroRect.bottom <= 0) {
      return;
    }
    const offset = Math.min(window.scrollY * 0.22, 95);
    heroParallaxLayer.style.transform = "translate3d(0," + offset + "px,0)";
  }

  function onScrollUpdate() {
    updateScrollProgress();
    updateActiveAnchor();
    updateBackToTop();
    updateHeroParallax();
  }

  function requestTick() {
    if (!ticking) {
      window.requestAnimationFrame(function () {
        onScrollUpdate();
        ticking = false;
      });
      ticking = true;
    }
  }

  window.addEventListener("scroll", requestTick, { passive: true });
  window.addEventListener("resize", requestTick);

  onScrollUpdate();

  const loginScene = document.getElementById("loginScene");
  if (loginScene) {
    const loginChars = Array.from(loginScene.querySelectorAll("[data-login-character]"));
    const loginInputs = Array.from(document.querySelectorAll(".login-input"));
    const loginPassword = document.getElementById("loginPassword");
    const loginTogglePassword = document.getElementById("loginTogglePassword");
    let peekTimer = null;
    let lastMouseX = window.innerWidth / 2;
    let lastMouseY = window.innerHeight / 2;

    function updateCharacterLook(event) {
      const x = event.clientX;
      const y = event.clientY;
      lastMouseX = x;
      lastMouseY = y;
      const lockPose = loginScene.classList.contains("is-typing") || loginScene.classList.contains("is-password-visible");
      loginChars.forEach(function (charEl) {
        const rect = charEl.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 3;
        const dx = x - centerX;
        const dy = y - centerY;
        const lookX = Math.max(-5, Math.min(5, dx / 30));
        const lookY = Math.max(-5, Math.min(5, dy / 30));
        charEl.style.setProperty("--look-x", lookX.toFixed(2) + "px");
        charEl.style.setProperty("--look-y", lookY.toFixed(2) + "px");

        const bodyLean = Math.max(-8, Math.min(8, -dx / 80));
        if (!lockPose) {
          charEl.style.transform = "skewX(" + bodyLean.toFixed(2) + "deg)";
        } else {
          charEl.style.removeProperty("transform");
        }
      });
    }

    function scheduleBlink(selector, minMs, maxMs) {
      const charEl = document.querySelector(selector);
      if (!charEl) return;
      const run = function () {
        const delay = Math.floor(Math.random() * (maxMs - minMs + 1)) + minMs;
        setTimeout(function () {
          charEl.classList.add("is-blinking");
          setTimeout(function () {
            charEl.classList.remove("is-blinking");
            run();
          }, 140);
        }, delay);
      };
      run();
    }

    function updateTypingState() {
      const activeTag = document.activeElement && document.activeElement.classList
        ? document.activeElement.classList.contains("login-input")
        : false;
      loginScene.classList.toggle("is-typing", activeTag);
      if (activeTag) {
        loginChars.forEach(function (charEl) {
          charEl.style.removeProperty("transform");
        });
      } else {
        updateCharacterLook({ clientX: lastMouseX, clientY: lastMouseY });
      }
    }

    function updatePasswordScene() {
      if (!loginPassword) return;
      const isVisible = loginPassword.type === "text" && loginPassword.value.length > 0;
      loginScene.classList.toggle("is-password-visible", isVisible);
      loginChars.forEach(function (charEl) {
        if (isVisible) {
          charEl.style.removeProperty("transform");
        }
      });
      if (!isVisible) {
        loginScene.classList.remove("is-peeking");
        updateCharacterLook({ clientX: lastMouseX, clientY: lastMouseY });
      }
    }

    function startPeekCycle() {
      if (!loginPassword || loginPassword.type !== "text" || loginPassword.value.length === 0) {
        loginScene.classList.remove("is-peeking");
        return;
      }
      const delay = Math.floor(Math.random() * 3000) + 2000;
      peekTimer = setTimeout(function () {
        loginScene.classList.add("is-peeking");
        setTimeout(function () {
          loginScene.classList.remove("is-peeking");
          startPeekCycle();
        }, 800);
      }, delay);
    }

    loginInputs.forEach(function (input) {
      input.addEventListener("focus", updateTypingState);
      input.addEventListener("blur", function () {
        setTimeout(updateTypingState, 0);
      });
      input.addEventListener("input", function () {
        updatePasswordScene();
        if (peekTimer) clearTimeout(peekTimer);
        startPeekCycle();
      });
    });

    if (loginTogglePassword && loginPassword) {
      loginTogglePassword.addEventListener("click", function () {
        const isHidden = loginPassword.type === "password";
        loginPassword.type = isHidden ? "text" : "password";
        loginTogglePassword.textContent = isHidden ? "Hide" : "Show";
        updatePasswordScene();
        if (peekTimer) clearTimeout(peekTimer);
        startPeekCycle();
      });
    }

    window.addEventListener("mousemove", updateCharacterLook);
    scheduleBlink("#loginCharacterPurple", 2800, 6200);
    scheduleBlink("#loginCharacterBlack", 3000, 6500);
    updateTypingState();
    updatePasswordScene();
  }

  const siteRainBg = document.getElementById("siteRainBg");
  if (siteRainBg) {
    const lightningFlash = document.getElementById("siteLightningFlash");
    const prefersReducedMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const intensity = prefersReducedMotion ? 120 : 450;
    const speed = 0.7;
    const minSize = 1;
    const maxSize = 2;

    for (let i = 0; i < intensity; i += 1) {
      const drop = document.createElement("span");
      drop.className = "site-rain-drop";
      const left = Math.random() * 100;
      const size = Math.random() * (maxSize - minSize) + minSize;
      const duration = (Math.random() * 1 + 0.5) / speed;
      const delay = Math.random() * 2;
      const opacity = Math.random() * 0.6 + 0.2;

      drop.style.left = left + "%";
      drop.style.width = size + "px";
      drop.style.height = size * 10 + "px";
      drop.style.animationDuration = duration + "s";
      drop.style.animationDelay = delay + "s";
      drop.style.opacity = opacity.toFixed(2);
      siteRainBg.appendChild(drop);
    }

    if (!prefersReducedMotion && lightningFlash) {
      function triggerLightning() {
        lightningFlash.classList.remove("is-active");
        void lightningFlash.offsetWidth;
        lightningFlash.classList.add("is-active");
      }

      function scheduleLightning() {
        const delay = (8 + Math.random() * 8) * 1000;
        setTimeout(function () {
          triggerLightning();
          scheduleLightning();
        }, delay);
      }

      scheduleLightning();
    }
  }

  const roleOrbit = document.getElementById("roleOrbit");
  if (roleOrbit) {
    const roleNodes = Array.from(roleOrbit.querySelectorAll("[data-role-node]"));
    const roleCheckboxes = Array.from(document.querySelectorAll(".role-checkbox-input"));
    const map = {};
    const prefersReducedMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    let orbitAngle = 0;
    let orbitAnimationFrame = 0;
    let orbitPaused = false;
    let lastTimestamp = 0;
    const baseAngles = roleNodes.map(function (_, index) {
      return (index / roleNodes.length) * Math.PI * 2;
    });

    roleCheckboxes.forEach(function (checkbox) {
      map[checkbox.value] = checkbox;
    });

    function layoutOrbit() {
      const size = roleOrbit.getBoundingClientRect();
      const radius = Math.min(size.width, size.height) * 0.38;
      roleNodes.forEach(function (node, index) {
        const a = baseAngles[index] + orbitAngle;
        const x = Math.cos(a) * radius;
        const y = Math.sin(a) * radius;
        const depth = (y + radius) / (radius * 2);
        const scale = 0.88 + depth * 0.28;
        node.style.transform = "translate(-50%, -50%) translate(" + x.toFixed(2) + "px, " + y.toFixed(2) + "px) scale(" + scale.toFixed(3) + ")";
        node.style.opacity = (0.66 + depth * 0.34).toFixed(2);
        node.style.zIndex = String(20 + Math.round(depth * 30));
      });
    }

    function animateOrbit(ts) {
      if (!lastTimestamp) lastTimestamp = ts;
      const delta = ts - lastTimestamp;
      lastTimestamp = ts;

      if (!orbitPaused && !prefersReducedMotion) {
        orbitAngle += delta * 0.00034;
        layoutOrbit();
      }

      orbitAnimationFrame = window.requestAnimationFrame(animateOrbit);
    }

    roleOrbit.addEventListener("mouseenter", function () {
      orbitPaused = true;
    });
    roleOrbit.addEventListener("mouseleave", function () {
      orbitPaused = false;
    });

    roleNodes.forEach(function (node) {
      const role = node.getAttribute("data-role-node");
      if (!role || !map[role]) return;

      node.addEventListener("click", function () {
        const checkbox = map[role];
        checkbox.checked = !checkbox.checked;
        node.classList.toggle("is-selected", checkbox.checked);
        node.setAttribute("aria-pressed", checkbox.checked ? "true" : "false");
      });
    });

    layoutOrbit();
    orbitAnimationFrame = window.requestAnimationFrame(animateOrbit);
    window.addEventListener("resize", layoutOrbit);
  }

  function bindTeamPositionPicker(form, field) {
    const selectedSlotInput = form.querySelector("[data-selected-slot-input]");
    const selectedSlotLabel = form.querySelector("[data-selected-slot-label]");
    if (!selectedSlotInput) {
      return;
    }

    if (!field) {
      return;
    }
    const viewerAvatar = field.getAttribute("data-viewer-avatar") || "";
    const viewerName = field.getAttribute("data-viewer-name") || "You";

    const openSlots = field.querySelectorAll(".field-slot.is-open.is-selectable");
    let selectedSlotEl = null;

    function clearPreview(slotEl) {
      if (!slotEl) return;
      slotEl.classList.remove("is-selected");
      slotEl.classList.remove("is-previewed");
      const previewAvatar = slotEl.querySelector(".field-preview-avatar");
      if (previewAvatar) {
        previewAvatar.remove();
      }
      const previewName = slotEl.querySelector(".field-name[data-preview='1']");
      if (previewName) {
        previewName.remove();
      }
      const plusBtn = slotEl.querySelector(".field-plus-btn");
      if (plusBtn) {
        plusBtn.style.display = "";
      }
    }

    function setPreview(slotEl) {
      if (!slotEl || !viewerAvatar) return;
      slotEl.classList.add("is-selected");
      slotEl.classList.add("is-previewed");
      const plusBtn = slotEl.querySelector(".field-plus-btn");
      if (plusBtn) {
        plusBtn.style.display = "none";
      }

      const avatar = document.createElement("img");
      avatar.className = "field-avatar field-preview-avatar";
      avatar.src = viewerAvatar;
      avatar.alt = viewerName;
      slotEl.insertBefore(avatar, slotEl.firstChild);

      const name = document.createElement("span");
      name.className = "field-name";
      name.dataset.preview = "1";
      name.textContent = viewerName;
      slotEl.appendChild(name);
    }

    openSlots.forEach(function (slotEl) {
      slotEl.addEventListener("click", function () {
        const slotKey = slotEl.getAttribute("data-slot-key") || "";
        const wasSame = selectedSlotEl === slotEl;
        if (selectedSlotEl) {
          clearPreview(selectedSlotEl);
        }

        if (wasSame) {
          selectedSlotEl = null;
          selectedSlotInput.value = "";
          if (selectedSlotLabel) {
            selectedSlotLabel.textContent = "No slot selected (auto assign)";
          }
          return;
        }

        selectedSlotEl = slotEl;
        selectedSlotInput.value = slotKey;
        setPreview(slotEl);
        if (selectedSlotLabel) {
          selectedSlotLabel.textContent = "Selected slot: " + slotKey.toUpperCase();
        }
      });
    });
  }

  const teamCreateForm = document.querySelector("[data-team-create-form]");
  if (teamCreateForm) {
    bindTeamPositionPicker(teamCreateForm, teamCreateForm.querySelector("[data-position-picker]"));
  }

  const teamJoinForms = document.querySelectorAll("[data-team-join-form]");
  teamJoinForms.forEach(function (form) {
    const adCard = form.closest("article");
    bindTeamPositionPicker(form, adCard ? adCard.querySelector("[data-position-picker]") : null);
  });
});

