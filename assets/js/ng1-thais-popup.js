(function(){
    "use strict";

    function ready(fn) {
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", fn);
        } else {
            fn();
        }
    }

    ready(function(){
        var doc = document;
        var currentSlug = null;
        var knownSlugs = [];

        if (window.ng1ThaisPopupData && Array.isArray(window.ng1ThaisPopupData.slugs)) {
            knownSlugs = window.ng1ThaisPopupData.slugs.slice();
        }

        doc.querySelectorAll("[data-ng1-thais-popup]").forEach(function(el){
            var slug = el.getAttribute("data-ng1-thais-popup");
            if (slug && knownSlugs.indexOf(slug) === -1) {
                knownSlugs.push(slug);
            }
        });

        function getElements(slug) {
            if (!slug) {
                return { popup: null, overlay: null };
            }
            return {
                popup: doc.getElementById("ng1-thais-popup-" + slug),
                overlay: doc.getElementById("ng1-thais-overlay-" + slug)
            };
        }

        function closeAll() {
            doc.querySelectorAll(".ng1-thais-popup").forEach(function(item){
                if (item && item.style) {
                    item.style.display = "none";
                }
            });
            doc.querySelectorAll(".ng1-thais-overlay").forEach(function(item){
                if (item && item.style) {
                    item.style.display = "none";
                }
            });
            currentSlug = null;
        }

        function focusPopup(slug) {
            var popup = doc.getElementById("ng1-thais-popup-" + slug);
            if (!popup) {
                return;
            }
            var inner = popup.querySelector(".ng1-thais-popup-inner");
            if (inner && typeof inner.focus === "function") {
                try {
                    inner.focus({ preventScroll: true });
                } catch (err) {
                    inner.focus();
                }
            }
        }

        function openSlug(slug) {
            if (!slug) {
                return;
            }
            var elements = getElements(slug);
            if (!elements.popup || !elements.overlay) {
                return;
            }
            if (currentSlug === slug && elements.popup.style.display === "flex") {
                closeAll();
                return;
            }
            closeAll();
            elements.popup.style.display = "flex";
            elements.overlay.style.display = "block";
            currentSlug = slug;
            focusPopup(slug);
        }

        function toggleSlug(slug) {
            if (!slug) {
                return;
            }
            if (currentSlug === slug) {
                closeAll();
            } else {
                openSlug(slug);
            }
        }

        function getSlugFromHash(hash) {
            if (!hash) { return null; }
            var h = hash.charAt(0) === '#' ? hash.slice(1) : hash;
            if (!h) { return null; }
            // Patterns supported: thais-popup=slug, thais-popup-slug, direct slug (if known)
            if (h.indexOf('thais-popup=') === 0) {
                return h.substring('thais-popup='.length) || null;
            }
            if (h.indexOf('thais-popup-') === 0) {
                return h.substring('thais-popup-'.length) || null;
            }
            // Fallback: if the hash equals a known slug, accept it
            if (knownSlugs.indexOf(h) !== -1) {
                return h;
            }
            return null;
        }

        function handleHashChange() {
            var slug = getSlugFromHash(window.location.hash || '');
            if (slug) {
                openSlug(slug);
            }
        }

        function resolveSlugFromFallback(fallback) {
            if (!fallback) {
                return null;
            }
            var slug = fallback.getAttribute("data-ng1-thais-target") || fallback.dataset.ng1ThaisTarget || "";
            if (!slug) {
                var widget = fallback.closest && fallback.closest("[data-ng1-thais-popup]");
                if (widget) {
                    slug = widget.getAttribute("data-ng1-thais-popup") || "";
                }
            }
            if (!slug && knownSlugs.length > 0) {
                slug = knownSlugs[0];
            }
            return slug || null;
        }

        doc.addEventListener("click", function(event){
            var target = event.target;
            if (!target) {
                return;
            }

            if (target.closest) {
                // Intercept anchors with hash to open popup by slug
                var hashLink = target.closest('a[href^="#"]');
                if (hashLink) {
                    var href = hashLink.getAttribute('href') || '';
                    var slugFromHref = getSlugFromHash(href);
                    if (slugFromHref) {
                        event.preventDefault();
                        toggleSlug(slugFromHref);
                        return;
                    }
                }

                var trigger = target.closest("[data-ng1-thais-target]");
                if (trigger) {
                    var slug = trigger.getAttribute("data-ng1-thais-target");
                    if (slug) {
                        event.preventDefault();
                        toggleSlug(slug);
                        return;
                    }
                }

                var fallback = target.closest(".open-disponibilite, .open-disponibilité");
                if (fallback) {
                    var fallbackSlug = resolveSlugFromFallback(fallback);
                    if (fallbackSlug) {
                        event.preventDefault();
                        toggleSlug(fallbackSlug);
                        return;
                    }
                }

                var closeButton = target.closest(".ng1-thais-popup-close");
                if (closeButton) {
                    event.preventDefault();
                    closeAll();
                    return;
                }

                var overlay = target.closest(".ng1-thais-overlay");
                if (overlay) {
                    event.preventDefault();
                    closeAll();
                    return;
                }
            }
        });

        doc.addEventListener("keydown", function(event){
            if (event.key === "Escape" || event.key === "Esc") {
                closeAll();
                return;
            }
            if (event.key === " " || event.key === "Spacebar") {
                var active = event.target;
                if (active && active.classList && active.classList.contains("ng1-thais-trigger")) {
                    var slug = active.getAttribute("data-ng1-thais-target");
                    if (slug) {
                        event.preventDefault();
                        toggleSlug(slug);
                    }
                }
            }
        });

        // Open on initial hash and on further hash changes
        handleHashChange();
        window.addEventListener('hashchange', handleHashChange);
    });
})();
