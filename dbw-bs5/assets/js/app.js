(function () {
  // ---------- Helpers ----------
  const $ = (sel, root = document) => root.querySelector(sel);

  function emitDatesUpdated(detail = {}) {
    document.dispatchEvent(new CustomEvent("dates:updated", { detail }));
  }

  function escapeHtml(s = "") {
    return String(s)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function getParam(name, fallback = "") {
    const u = new URL(window.location.href);
    return u.searchParams.get(name) || fallback;
  }

  function parseISODate(s) {
    const [y, m, d] = String(s).split("-").map(Number);
    return new Date(Date.UTC(y, m - 1, d));
  }

  function dateToISO(d) {
    const y = d.getUTCFullYear();
    const m = String(d.getUTCMonth() + 1).padStart(2, "0");
    const day = String(d.getUTCDate()).padStart(2, "0");
    return `${y}-${m}-${day}`;
  }

  function daysBetween(checkin, checkout) {
    const a = parseISODate(checkin);
    const b = parseISODate(checkout);
    return Math.round((b - a) / (24 * 60 * 60 * 1000));
  }

  function formatMoney(amount, currency) {
    try {
      return new Intl.NumberFormat(undefined, { style: "currency", currency }).format(amount);
    } catch {
      return `${amount} ${currency}`;
    }
  }

  function isoToLocalDate(iso) {
    const [y, m, d] = String(iso).split("-").map(Number);
    return new Date(y, m - 1, d);
  }

  function localDateToISO(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${y}-${m}-${day}`;
  }

  function addDaysLocal(d, days) {
    const x = new Date(d.getFullYear(), d.getMonth(), d.getDate());
    x.setDate(x.getDate() + days);
    return x;
  }

  // bookedRanges = [{from:"YYYY-MM-DD", to:"YYYY-MM-DD"}] where "to" = checkout (end-exclusive)
  function buildBookedNightsSet(bookedRanges) {
    const set = new Set();
    (bookedRanges || []).forEach((r) => {
      const start = isoToLocalDate(r.from);
      const endExclusive = isoToLocalDate(r.to);
      for (let cur = new Date(start); cur < endExclusive; cur = addDaysLocal(cur, 1)) {
        set.add(localDateToISO(cur));
      }
    });
    return set;
  }

  function findFirstBookedNightBetween(checkinDate, checkoutDate, bookedNightsSet) {
    const start = new Date(checkinDate.getFullYear(), checkinDate.getMonth(), checkinDate.getDate());
    const endExclusive = new Date(checkoutDate.getFullYear(), checkoutDate.getMonth(), checkoutDate.getDate());

    for (let cur = new Date(start); cur < endExclusive; cur = addDaysLocal(cur, 1)) {
      const iso = localDateToISO(cur);
      if (bookedNightsSet.has(iso)) return cur;
    }
    return null;
  }

  // trim checkout so that no booked night is inside [checkin, checkout)
  function trimCheckoutToAvoidBooked(checkinDate, checkoutDate, bookedNightsSet) {
    const firstBookedNight = findFirstBookedNightBetween(checkinDate, checkoutDate, bookedNightsSet);
    if (!firstBookedNight) return checkoutDate;
    // checkout becomes the day of first booked night (end-exclusive)
    return new Date(firstBookedNight.getFullYear(), firstBookedNight.getMonth(), firstBookedNight.getDate());
  }

  // ---------- Pricing ----------
  function getRuleForDate(pricing, isoDate) {
    const rules = pricing.rules || [];
    return rules.find((r) => isoDate >= r.from && isoDate <= r.to) || null;
  }

  function nightPriceForDate(pricing, isoDate) {
    const rule = getRuleForDate(pricing, isoDate);
    if (rule?.night != null) return rule.night;

    const d = parseISODate(isoDate);
    const dow = d.getUTCDay();
    if (pricing.weekendNight != null && (pricing.weekendDays || []).includes(dow)) return pricing.weekendNight;
    return pricing.baseNight;
  }

  function minNightsForRange(pricing, checkinISO, checkoutISO) {
    let min = pricing.minNightsDefault || 1;
    const nights = daysBetween(checkinISO, checkoutISO);
    const start = parseISODate(checkinISO);

    for (let i = 0; i < nights; i++) {
      const cur = new Date(start);
      cur.setUTCDate(cur.getUTCDate() + i);
      const iso = dateToISO(cur);
      const rule = getRuleForDate(pricing, iso);
      if (rule?.minNights != null) min = Math.max(min, rule.minNights);
    }
    return min;
  }

  function calculateTotal(pricing, checkinISO, checkoutISO) {
    const nights = daysBetween(checkinISO, checkoutISO);
    if (nights <= 0) return null;

    const start = parseISODate(checkinISO);
    let subtotal = 0;

    for (let i = 0; i < nights; i++) {
      const cur = new Date(start);
      cur.setUTCDate(cur.getUTCDate() + i);
      const iso = dateToISO(cur);
      subtotal += nightPriceForDate(pricing, iso);
    }

    const cleaning = pricing.cleaningFee || 0;
    return { nights, subtotal, cleaning, total: subtotal + cleaning };
  }

  function renderPriceBox(priceBox, pricing, calc, minRequired) {
    if (!priceBox) return;

    if (!calc) {
      priceBox.innerHTML = `<div class="small text-medium-gray">Select dates to see total price.</div>`;
      return;
    }

    const cur = pricing.currency || "EUR";
    const ok = calc.nights >= minRequired;

    priceBox.innerHTML = `
      <div class="d-flex justify-content-between">
        <div><strong>${calc.nights} nights</strong></div>
        <div class="text-medium-gray">${escapeHtml(formatMoney(calc.subtotal, cur))}</div>
      </div>
      <div class="d-flex justify-content-between mt-1">
        <div class="text-medium-gray">Cleaning fee</div>
        <div class="text-medium-gray">${escapeHtml(formatMoney(calc.cleaning, cur))}</div>
      </div>
      <div class="my-2" style="height:1px;background:rgba(17,24,39,.12)"></div>
      <div class="d-flex justify-content-between">
        <div><strong>Total</strong></div>
        <div><strong>${escapeHtml(formatMoney(calc.total, cur))}</strong></div>
      </div>
      <div class="small mt-2 ${ok ? "text-medium-gray" : "text-danger"}">
        Minimum stay: ${minRequired} nights
      </div>
    `;
  }

  // ---------- Availability ----------
  async function fetchAvailability(slug) {
    const url = `./api/availability.php?property=${encodeURIComponent(slug)}`;
    const res = await fetch(url, { cache: "no-store" });

    // Debug: když je 500, uvidíš aspoň odpověď v konzoli
    if (!res.ok) {
      const t = await res.text().catch(() => "");
      console.error("Availability API error:", res.status, t);
      throw new Error("Availability API failed");
    }

    return res.json();
  }

  // ---------- Litepicker ----------
  let lp = null;

  function initDatepickers(bookedRanges, minNights = 1) {
    const checkinEl = $("#checkin");
    const checkoutEl = $("#checkout");
    if (!checkinEl || !checkoutEl) {
      console.warn("Date inputs not found");
      return;
    }

    const bookedNightsSet = buildBookedNightsSet(bookedRanges);
    const lockDays = Array.from(bookedNightsSet).map(isoToLocalDate);

    if (lp && typeof lp.destroy === "function") {
      lp.destroy();
      lp = null;
    }

    let isProgrammaticSet = false;

    function safeSetRange(picker, startDate, endDate) {
      isProgrammaticSet = true;
      try {
        picker.setDateRange(startDate, endDate);
      } finally {
        setTimeout(() => (isProgrammaticSet = false), 0);
      }
    }

    lp = new Litepicker({
      element: checkinEl,
      elementEnd: checkoutEl,
      singleMode: false,
      autoApply: true,
      numberOfMonths: 2,
      numberOfColumns: 2,
      showTooltip: true,
      format: "YYYY-MM-DD",
      minDate: new Date(),

      lockDays,
      disallowLockDaysInRange: true,
      lockDaysInclusivity: "[]",

      setup: (picker) => {
        picker.on("selected", (date1, date2) => {
          if (isProgrammaticSet) return;

          if (!date1 || !date2) {
            emitDatesUpdated({ status: "incomplete" });
            return;
          }

          const cin = new Date(date1.getFullYear(), date1.getMonth(), date1.getDate());
          let cout = new Date(date2.getFullYear(), date2.getMonth(), date2.getDate());

          // ensure at least 1 night
          if (cout <= cin) cout = addDaysLocal(cin, 1);

          // fail-safe trim against booked nights
          const trimmed = trimCheckoutToAvoidBooked(cin, cout, bookedNightsSet);
          cout = trimmed;

          // write chosen (no silent min-nights auto extend)
          checkinEl.value = localDateToISO(cin);
          checkoutEl.value = localDateToISO(cout);

          // if picker end differs (because of trim), sync it
          const pickedEndISO = localDateToISO(new Date(date2.getFullYear(), date2.getMonth(), date2.getDate()));
          const endISO = checkoutEl.value;
          if (pickedEndISO !== endISO) safeSetRange(picker, cin, cout);

          emitDatesUpdated({ status: "selected" });
        });

        checkinEl.addEventListener("focus", () => picker.show());
        checkoutEl.addEventListener("focus", () => picker.show());
      },
    });
  }

  // ---------- UI bind helpers ----------
  function setText(selector, value) {
    const el = $(selector);
    if (el) el.textContent = value ?? "";
  }

  function setAttr(selector, attr, value) {
    const el = $(selector);
    if (el) el.setAttribute(attr, value);
  }

  function normalizeUrl(u) {
    if (!u) return "";
    if (/^https?:\/\//i.test(u)) return u;
    return new URL(u, window.location.href).toString();
  }

  function renderGallery(urls = []) {
    const imgs = document.querySelectorAll("#photos img[data-gallery-index]");
    if (!imgs.length) return;

    const list = (urls || []).slice(0, imgs.length);
    imgs.forEach((img, i) => {
      const slide = img.closest(".swiper-slide");
      const u = list[i];

      if (!u) {
        if (slide) slide.style.display = "none";
        return;
      }

      if (slide) slide.style.display = "";
      img.src = normalizeUrl(u);
      img.loading = "lazy";
    });
  }

  function renderExternalLinks(links = {}) {
    const host = document.querySelector('[data-bind="externalLinks"]');
    if (!host) return;

    const norm = (x, fallbackLabel) => {
      if (!x) return null;
      if (typeof x === "string") return { url: x, label: fallbackLabel };
      return { url: x.url, label: x.label || fallbackLabel };
    };

    const airbnb = norm(links.airbnb, "View on");
    const booking = norm(links.booking, "View on");

    const items = [];

    if (airbnb?.url) {
      items.push(`
        <a class="btn btn-light m-1 btn-medium btn-rounded d-table d-lg-inline-block" href="${escapeHtml(
          airbnb.url
        )}" target="_blank" rel="noopener">
          <span class="me-2">${escapeHtml(airbnb.label)}</span>
          <img class="dbw-trust-logo" src="./images/airbnb.svg" alt="Airbnb">
        </a>
      `);
    }

    if (booking?.url) {
      items.push(`
        <a class="btn btn-light m-1 btn-medium btn-rounded d-table d-lg-inline-block" href="${escapeHtml(
          booking.url
        )}" target="_blank" rel="noopener">
          <span class="me-2">${escapeHtml(booking.label)}</span>
          <img class="dbw-trust-logo" src="./images/booking.svg" alt="Booking.com">
        </a>
      `);
    }

    host.innerHTML = items.join("");
  }

  function renderQuickFacts(facts = []) {
    const host = $("#quickFacts");
    if (!host) return;
    const cols = (facts || []).slice(0, 4);
    host.innerHTML = cols
      .map(
        (f, idx) => `
      <div class="col text-center ${idx < cols.length - 1 ? "border-end" : ""} xs-border-end-0 border-color-extra-medium-gray alt-font md-mb-15px">
        <span class="fs-19 text-dark-gray fw-600">${escapeHtml(f.label)}:</span> ${escapeHtml(f.value)}
      </div>
    `
      )
      .join("");
  }

  function renderAmenitiesTop(items = []) {
    const host = document.querySelector('[data-bind-list="amenitiesTop"]');
    if (!host) return;
    host.innerHTML = (items || [])
      .slice(0, 4)
      .map(
        (a, idx) => `
      <div class="col text-center ${idx < 3 ? "border-end border-color-extra-medium-gray" : ""} sm-mb-30px">
        <div class="text-base-color fs-28 mb-10px">${escapeHtml(a.icon || "✓")}</div>
        <span class="text-dark-gray d-block lh-20">${escapeHtml(a.label)}</span>
      </div>
    `
      )
      .join("");
  }

  function renderAmenitiesColumns(columns = []) {
    const host = document.querySelector('[data-bind-list="amenitiesColumns"]');
    if (!host) return;

    host.innerHTML = (columns || [])
      .slice(0, 3)
      .map(
        (col) => `
      <div class="col-6 col-sm-4">
        <ul class="list-style-02 ps-0 mb-0">
          ${(col || [])
            .map((item) => `<li><i class="bi bi-check-circle text-green icon-small me-10px"></i>${escapeHtml(item)}</li>`)
            .join("")}
        </ul>
      </div>
    `
      )
      .join("");
  }

  function renderHouseRules(rules = []) {
    const host = document.querySelector('[data-bind-list="houseRules"]');
    if (!host) return;
    host.innerHTML = (rules || [])
      .map((r) => `<li><i class="bi bi-exclamation-circle text-red icon-small me-10px"></i>${escapeHtml(r)}</li>`)
      .join("");
  }

  function renderReviews(reviews = []) {
    const host = document.querySelector('[data-bind-list="reviews"]');
    if (!host) return;
    host.innerHTML = (reviews || [])
      .slice(0, 6)
      .map(
        (r) => `
      <div class="col-12 mb-15px">
        <div class="border-radius-10px bg-white box-shadow-double-large p-25px">
          <div class="d-flex align-items-center justify-content-between">
            <div class="fw-700 text-dark-gray">${escapeHtml(r.name || "Guest")}</div>
            <div class="text-base-color">${"★".repeat(Math.max(0, Math.min(5, r.stars || 5)))}</div>
          </div>
          <div class="text-medium-gray mt-8px">${escapeHtml(r.text || "")}</div>
        </div>
      </div>
    `
      )
      .join("");
  }

  function renderFAQ(items = []) {
    const host = document.querySelector('[data-bind-list="faq"]');
    if (!host) return;
    host.innerHTML = (items || [])
      .slice(0, 8)
      .map(
        (q, i) => `
      <div class="accordion mb-10px" id="faqAcc${i}">
        <div class="accordion-item border border-radius-10px overflow-hidden">
          <h2 class="accordion-header" id="h${i}">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c${i}" aria-expanded="false" aria-controls="c${i}">
              ${escapeHtml(q.q)}
            </button>
          </h2>
          <div id="c${i}" class="accordion-collapse collapse" aria-labelledby="h${i}">
            <div class="accordion-body text-medium-gray">${escapeHtml(q.a)}</div>
          </div>
        </div>
      </div>
    `
      )
      .join("");
  }

  function populateGuests(maxGuests = 4) {
    const sel = $("#guests");
    if (!sel) return;

    sel.innerHTML = "";

    const placeholder = document.createElement("option");
    placeholder.value = "";
    placeholder.textContent = "Select guests";
    placeholder.disabled = true;
    placeholder.selected = true;
    sel.appendChild(placeholder);

    for (let i = 1; i <= maxGuests; i++) {
      const opt = document.createElement("option");
      opt.value = String(i);
      opt.textContent = i === 1 ? "1 guest" : `${i} guests`;
      sel.appendChild(opt);
    }
  }

  // ---------- Main ----------
  async function init() {
    const slug = getParam("p", "nissi-golden-sands-a15");
    const jsonUrl = `./data/properties/${encodeURIComponent(slug)}.json`;

    const property = await fetch(jsonUrl, { cache: "no-store" }).then((r) => {
      if (!r.ok) throw new Error(`Property JSON not found: ${jsonUrl}`);
      return r.json();
    });

    // Basic binds
    document.title = property.seo?.title || `${property.title} • Direct Booking`;
    setText('[data-bind="pageTitle"]', document.title);
    setText('[data-bind="metaDescription"]', property.seo?.description || "");
    setText('[data-bind="title"]', property.title);
    setText('[data-bind="addressLine"]', property.location?.addressLine || "");
    setText('[data-bind="rating"]', property.reviews?.rating ?? "—");
    setText('[data-bind="ratingSmall"]', property.reviews?.rating ?? "—");
    setText('[data-bind="reviewsCountText"]', `(${property.reviews?.count ?? 0} reviews)`);
    setText('[data-bind="reviewsCountSmall"]', `(${property.reviews?.count ?? 0})`);
    setText('[data-bind="description"]', property.description || "");
    setText('[data-bind="year"]', String(new Date().getFullYear()));

    // Trust links
    renderExternalLinks(property.externalLinks || {});

    // Gallery + sections
    renderGallery(property.gallery || []);
    renderQuickFacts(property.quickFacts || []);
    renderAmenitiesTop(property.amenitiesTop || []);
    renderAmenitiesColumns(property.amenitiesColumns || []);
    renderHouseRules(property.houseRules || []);
    renderReviews(property.reviews?.items || []);
    renderFAQ(property.faq || []);

    // Map embed
    const mapsUrl = property.location?.mapsEmbedUrl;
    if (mapsUrl) setAttr('iframe[data-bind-attr="mapsEmbedSrc"]', "src", mapsUrl);

    const pricing = property.pricing || null;

    // Pricing header (from/base)
    if (pricing) {
      const cur = pricing.currency || "EUR";
      const from = pricing.baseNight ?? 0;
      setText('[data-bind="fromPriceText"]', `From ${formatMoney(from, cur)}`);
      setText('[data-bind="nightPriceText"]', formatMoney(from, cur));
    }

    // Guests select
    populateGuests(property.booking?.maxGuests || 4);

    // Availability + datepicker (⚠️ guard if pricing missing)
    const availability = await fetchAvailability(property.slug || slug);
    initDatepickers(availability.booked || [], pricing?.minNightsDefault || 1);

    // UI elements
    const priceBox = $("#priceBox");
    const btn = $("#requestBtn");
    const note = $("#paymentNote");
    const msgEl = $("#enquiryMsg");

    function setMsg(text, type = "info") {
      if (!msgEl) return;
      msgEl.className = "mt-10px small";
      if (type === "success") msgEl.classList.add("text-success");
      else if (type === "error") msgEl.classList.add("text-danger");
      else msgEl.classList.add("text-medium-gray");
      msgEl.textContent = text || "";
    }

    const getVal = (id) => (document.getElementById(id)?.value || "").trim();

    const updatePricing = () => {
      if (!pricing) return;

      const checkin = getVal("checkin");
      const checkout = getVal("checkout");
      const guests = getVal("guests");

      const minDefault = pricing.minNightsDefault || 1;

      // no dates
      if (!checkin || !checkout) {
        renderPriceBox(priceBox, pricing, null, minDefault);
        if (btn) {
          btn.disabled = true;
          btn.textContent = "Select dates";
        }
        if (note) note.textContent = "You won’t be charged yet";
        setMsg("");
        return;
      }

      // compute totals
      const minReq = minNightsForRange(pricing, checkin, checkout);
      const calc = calculateTotal(pricing, checkin, checkout);

      if (!calc) {
        renderPriceBox(priceBox, pricing, null, minReq);
        if (btn) {
          btn.disabled = true;
          btn.textContent = "Select dates";
        }
        if (note) note.textContent = "Select valid dates";
        setMsg("Select valid dates.", "error");
        return;
      }

      renderPriceBox(priceBox, pricing, calc, minReq);

      // guests gating
      if (!guests) {
        if (btn) {
          btn.disabled = true;
          btn.textContent = "Select guests";
        }
        if (note) note.textContent = "Select number of guests to continue";
        setMsg("");
        return;
      }

      // min nights gating
      const ok = calc.nights >= minReq;
      if (btn) {
        btn.disabled = !ok;
        btn.textContent = ok ? "Request booking" : `Minimum ${minReq} nights`;
      }
      if (note) {
        note.textContent = ok
          ? "Request • Pay to confirm"
          : `Minimum stay is ${minReq} nights. Please extend your stay.`;
      }
      if (!ok) setMsg(`Minimum stay is ${minReq} nights. Please extend your stay.`, "error");
      else setMsg("");
    };

    // listeners
    $("#guests")?.addEventListener("change", updatePricing);
    document.addEventListener("dates:updated", updatePricing);
    updatePricing();

    async function sendEnquiry(payload) {
      const res = await fetch("./api/enquiry.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const data = await res.json().catch(() => ({}));
      return { res, data };
    }

 // Enquiry submit
btn?.addEventListener("click", async () => {
  try {
    setMsg("");

    const slugParam = getParam("p", ""); // vždy z URL
    const propertySlug = (property?.slug || slugParam || "").trim();

    const checkin = $("#checkin")?.value?.trim();
    const checkout = $("#checkout")?.value?.trim();
    const guests = $("#guests")?.value;

    const name = $("#enqName")?.value?.trim() || "";
    const email = $("#enqEmail")?.value?.trim() || "";

    if (!propertySlug) {
      setMsg("Missing property slug in URL (?p=...)", "error");
      return;
    }
    if (!checkin || !checkout) {
      setMsg("Select dates first.", "error");
      return;
    }
    if (!guests) {
      setMsg("Select guests first.", "error");
      return;
    }
    if (!name) {
      setMsg("Please enter your full name.", "error");
      return;
    }
    if (!email) {
      setMsg("Please enter your email.", "error");
      return;
    }

    btn.disabled = true;
    const oldText = btn.textContent;
    btn.textContent = "Sending...";

    const payload = {
      property: propertySlug,
      checkin,
      checkout,
      guests: Number(guests),
      name,
      email,
      // phone/message až přidáš:
      // phone: $("#enqPhone")?.value?.trim() || "",
      // message: $("#enqMessage")?.value?.trim() || "",
    };

    const res = await fetch("./api/enquiry.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok || !data?.ok) {
      if (data?.minNights) {
        setMsg(`Minimum stay is ${data.minNights} nights. Please extend your stay.`, "error");
      } else if (res.status === 409) {
        setMsg("Those dates are no longer available. Please select different dates.", "error");
      } else {
        setMsg(data?.error ? `Error: ${data.error}` : `Error sending enquiry (${res.status})`, "error");
      }
      btn.disabled = false;
      btn.textContent = oldText;
      return;
    }

    setMsg(`Enquiry sent ✅ (ID #${data.id}). We’ll get back to you shortly.`, "success");
    btn.textContent = "Sent ✅";
    btn.disabled = true;

  } catch (e) {
    console.error(e);
    setMsg("Server error. Please try again.", "error");
    btn.disabled = false;
    btn.textContent = "Request booking";
  }
});

  init().catch((err) => {
    console.error(err);
    document.body.innerHTML = `
      <div style="padding:24px;font-family:system-ui">
        <h2>Page failed to load</h2>
        <p style="color:#555">${escapeHtml(err.message || String(err))}</p>
        <p style="color:#777">Check your JSON path and slug.</p>
      </div>
    `;
  });
})();