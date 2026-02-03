(function () {
  // ---------- Helpers ----------
  const $ = (sel, root = document) => root.querySelector(sel);
  
  function emitDatesUpdated() {
  document.dispatchEvent(new CustomEvent("dates:updated"));
}

 function emitChange(el) {
  if (!el) return;   // ← klíčový fix

  try {
    el.dispatchEvent(new Event("change", { bubbles: true }));
  } catch (e) {
    // fallback pro starší buildy
    const evt = document.createEvent("HTMLEvents");
    evt.initEvent("change", true, false);
    el.dispatchEvent(evt);
  }
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
    const [y, m, d] = s.split("-").map(Number);
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
  // "2026-08-02" -> Date(2026, 7, 2) (lokální půlnoc)
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

// bookedRanges = [{from:"YYYY-MM-DD", to:"YYYY-MM-DD"}]
// PŘEDPOKLAD (běžný iCal): "to" je CHECKOUT den (end-exclusive)
// => obsazené noci jsou od "from" do (to - 1)
function buildBookedNightsSet(bookedRanges) {
  const set = new Set();

  (bookedRanges || []).forEach(r => {
    const start = isoToLocalDate(r.from);
    const endExclusive = isoToLocalDate(r.to);

    // přidáme všechny noci: [start, endExclusive)
    for (let cur = new Date(start); cur < endExclusive; cur = addDaysLocal(cur, 1)) {
      set.add(localDateToISO(cur));
    }
  });

  return set;
}
// Najde první booked noc v intervalu [checkin, checkout) (checkout = odjezd, ne noc)
function findFirstBookedNightBetween(checkinDate, checkoutDate, bookedNightsSet) {
  // procházíme noci: checkin, checkin+1, ... checkout-1
  const start = new Date(checkinDate.getFullYear(), checkinDate.getMonth(), checkinDate.getDate());
  const endExclusive = new Date(checkoutDate.getFullYear(), checkoutDate.getMonth(), checkoutDate.getDate());

  for (let cur = new Date(start); cur < endExclusive; cur = addDaysLocal(cur, 1)) {
    const iso = localDateToISO(cur);
    if (bookedNightsSet.has(iso)) return cur; // první booked noc
  }
  return null;
}

// Auto-trim checkout na den příjezdu tak, aby žádná booked noc nebyla uvnitř.
// Vrací checkoutDate (odjezd) – může být stejné jako původní, nebo zkrácené.
function trimCheckoutToAvoidBooked(checkinDate, checkoutDate, bookedNightsSet) {
  const firstBookedNight = findFirstBookedNightBetween(checkinDate, checkoutDate, bookedNightsSet);
  if (!firstBookedNight) return checkoutDate;

  // když je první booked noc např. 2026-08-03,
  // tak poslední validní noc je 2026-08-02 a checkout musí být 2026-08-03.
  // tj. checkout = firstBookedNight (odjezd ráno v den booked noci)
  return new Date(firstBookedNight.getFullYear(), firstBookedNight.getMonth(), firstBookedNight.getDate());
}

  // ---------- Pricing ----------
  function getRuleForDate(pricing, isoDate) {
    const rules = pricing.rules || [];
    return rules.find(r => isoDate >= r.from && isoDate <= r.to) || null;
  }

  function nightPriceForDate(pricing, isoDate) {
    const rule = getRuleForDate(pricing, isoDate);
    if (rule?.night != null) return rule.night;

    const d = parseISODate(isoDate);
    const dow = d.getUTCDay(); // 0..6
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
    const res = await fetch(`./api/availability.php?property=${encodeURIComponent(slug)}`, { cache: "no-store" });
    if (!res.ok) throw new Error("Availability API failed");
    return res.json();
  }

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

  function clearRange(picker) {
    isProgrammaticSet = true;
    try {
      picker.clearSelection();
    } finally {
      setTimeout(() => (isProgrammaticSet = false), 0);
    }
    checkinEl.value = "";
    checkoutEl.value = "";
    emitDatesUpdated(); // jen jednou
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
          emitDatesUpdated();
          return;
        }

        const cin = new Date(date1.getFullYear(), date1.getMonth(), date1.getDate());
        let cout = new Date(date2.getFullYear(), date2.getMonth(), date2.getDate());

        if (cout <= cin) cout = addDaysLocal(cin, 1);

        const minCheckout = addDaysLocal(cin, Math.max(1, minNights));
        if (cout < minCheckout) cout = minCheckout;

        cout = trimCheckoutToAvoidBooked(cin, cout, bookedNightsSet);

        if (cout < minCheckout) {
          // invalid kvůli blokům -> nech start, vymaž end
          isProgrammaticSet = true;
          try {
            picker.clearSelection();
            picker.setDate(cin);
          } finally {
            setTimeout(() => (isProgrammaticSet = false), 0);
          }
          checkinEl.value = localDateToISO(cin);
          checkoutEl.value = "";
          emitDatesUpdated();
          return;
        }

        const startISO = localDateToISO(cin);
        const endISO = localDateToISO(cout);
        checkinEl.value = startISO;
        checkoutEl.value = endISO;

        const pickedEndISO = localDateToISO(new Date(date2.getFullYear(), date2.getMonth(), date2.getDate()));
        if (pickedEndISO !== endISO) safeSetRange(picker, cin, cout);

        emitDatesUpdated();
      });

      checkinEl.addEventListener("focus", () => picker.show());
      checkoutEl.addEventListener("focus", () => picker.show());

      // pokud chceš mít clear tlačítko, dej ho mimo a zavolej clearRange(lp)
    }
  });
}
  function attachSafeClearByKey(picker, checkinEl, checkoutEl) {
  const onKey = (e) => {
    if (e.key !== "Backspace" && e.key !== "Delete") return;

    const a = (checkinEl.value || "").trim();
    const b = (checkoutEl.value || "").trim();

    // když jsou prázdné -> clear selection (safe)
    if (!a && !b) {
      e.preventDefault();
      picker.clearSelection();
      checkinEl.value = "";
      checkoutEl.value = "";
      console.log("emit", { checkinEl, checkoutEl, cin: checkinEl?.value, cout: checkoutEl?.value });
      emitDatesUpdated();      
    }
  };

  // aby se to nepřidalo 2x (když re-inituješ), nejdřív případně remove
  checkinEl.removeEventListener("keydown", onKey);
  checkoutEl.removeEventListener("keydown", onKey);

  checkinEl.addEventListener("keydown", onKey);
  checkoutEl.addEventListener("keydown", onKey);
}
  // ---------- Bind template ----------
  function setText(selector, value) {
    const el = $(selector);
    if (el) el.textContent = value ?? "";
  }

  function setAttr(selector, attr, value) {
    const el = $(selector);
    if (el) el.setAttribute(attr, value);
  }

  function setHeroBg(url) {
    const hero = $(".dbw-hero");
    if (hero && url) hero.style.backgroundImage = `url('${url}')`;
  }

  function renderChips(chips = []) {
    const host = document.querySelector('[data-bind-list="chips"]');
    if (!host) return;
    host.innerHTML = chips.slice(0, 6).map(c => `<span class="dbw-chip">${escapeHtml(c)}</span>`).join("");
  }

 function normalizeUrl(u) {
  if (!u) return "";
  // absolutní URL necháme
  if (/^https?:\/\//i.test(u)) return u;

  // relativní cesta -> uděláme absolutní vůči aktuální stránce (/dbw-bs5/property.html)
  // takže "images/..." bude /dbw-bs5/images/...
  return new URL(u, window.location.href).toString();
}

function renderGallery(urls = []) {
  const imgs = document.querySelectorAll('#photos img[data-gallery-index]');
  if (!imgs.length) return;

  const list = (urls || []).slice(0, imgs.length);

  imgs.forEach((img, i) => {
    const slide = img.closest(".swiper-slide");
    const u = list[i];

    if (!u) {
      // nemáme fotku pro ten slot -> schovej slide
      if (slide) slide.style.display = "none";
      return;
    }

    if (slide) slide.style.display = "";
    img.src = normalizeUrl(u);
    img.loading = "lazy";
  });
}

  function renderQuickFacts(facts = []) {
    const host = $("#quickFacts");
    if (!host) return;
    const cols = (facts || []).slice(0, 4);
    host.innerHTML = cols.map((f, idx) => `
      <div class="col text-center ${idx < cols.length - 1 ? "border-end" : ""} xs-border-end-0 border-color-extra-medium-gray alt-font md-mb-15px">
        <span class="fs-19 text-dark-gray fw-600">${escapeHtml(f.label)}:</span> ${escapeHtml(f.value)}
      </div>
    `).join("");
  }

  function renderAmenitiesTop(items = []) {
    const host = document.querySelector('[data-bind-list="amenitiesTop"]');
    if (!host) return;
    host.innerHTML = (items || []).slice(0, 4).map((a, idx) => `
      <div class="col text-center ${idx < 3 ? "border-end border-color-extra-medium-gray" : ""} sm-mb-30px">
        <div class="text-base-color fs-28 mb-10px">${escapeHtml(a.icon || "✓")}</div>
        <span class="text-dark-gray d-block lh-20">${escapeHtml(a.label)}</span>
      </div>
    `).join("");
  }

  function renderAmenitiesColumns(columns = []) {
    const host = document.querySelector('[data-bind-list="amenitiesColumns"]');
    if (!host) return;

    host.innerHTML = (columns || []).slice(0, 3).map(col => `
      <div class="col-6 col-sm-4">
        <ul class="list-style-02 ps-0 mb-0">
          ${(col || []).map(item => `<li><i class="bi bi-check-circle icon-small me-10px"></i>${escapeHtml(item)}</li>`).join("")}
        </ul>
      </div>
    `).join("");
  }

  function renderHouseRules(rules = []) {
    const host = document.querySelector('[data-bind-list="houseRules"]');
    if (!host) return;
    host.innerHTML = (rules || []).map(r => `<li><i class="bi bi-check-circle icon-small me-10px"></i>${escapeHtml(r)}</li>`).join("");
  }

  function renderReviews(reviews = []) {
    const host = document.querySelector('[data-bind-list="reviews"]');
    if (!host) return;
    host.innerHTML = (reviews || []).slice(0, 6).map(r => `
      <div class="col-12 mb-15px">
        <div class="border-radius-10px bg-white box-shadow-double-large p-25px">
          <div class="d-flex align-items-center justify-content-between">
            <div class="fw-700 text-dark-gray">${escapeHtml(r.name || "Guest")}</div>
            <div class="text-base-color">
              ${"★".repeat(Math.max(0, Math.min(5, r.stars || 5)))}
            </div>
          </div>
          <div class="text-medium-gray mt-8px">${escapeHtml(r.text || "")}</div>
        </div>
      </div>
    `).join("");
  }

  function renderFAQ(items = []) {
    const host = document.querySelector('[data-bind-list="faq"]');
    if (!host) return;
    host.innerHTML = (items || []).slice(0, 8).map((q, i) => `
      <div class="accordion mb-10px" id="faqAcc${i}">
        <div class="accordion-item border-radius-10px overflow-hidden">
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
    `).join("");
  }

  function populateGuests(maxGuests = 4) {
    const sel = $("#guests");
    if (!sel) return;
    sel.innerHTML = "";
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
    const property = await fetch(jsonUrl, { cache: "no-store" }).then(r => {
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

    // Hero BG
    setHeroBg(property.heroImage || property.gallery?.[0]);

    // Chips, gallery, facts, amenities, rules, etc.
    renderChips(property.chips || []);

    // 1) render gallery
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

    // Pricing header (from/base)
    const pricing = property.pricing || null;
    if (pricing) {
      const cur = pricing.currency || "EUR";
      const from = pricing.baseNight ?? 0;
      setText('[data-bind="fromPriceText"]', `From ${formatMoney(from, cur)}`);
      setText('[data-bind="nightPriceText"]', formatMoney(from, cur));
    }

    // Guests select
    populateGuests(property.booking?.maxGuests || 4);

    // Availability + datepicker
    const availability = await fetchAvailability(property.slug || slug);
    initDatepickers(availability.booked || [], pricing?.minNightsDefault || 1);

    // Pricing UI wiring
    const priceBox = $("#priceBox");
    const btn = $("#requestBtn");
    const note = $("#paymentNote");

    const updatePricing = () => {
      if (!pricing) return;
      const checkin = $("#checkin")?.value;
      const checkout = $("#checkout")?.value;

      if (!checkin || !checkout) {
        renderPriceBox(priceBox, pricing, null, pricing.minNightsDefault || 1);
        btn.disabled = true;
        btn.textContent = "Select dates";
        note.textContent = "You won’t be charged yet";
        return;
      }

      const minReq = minNightsForRange(pricing, checkin, checkout);
      const calc = calculateTotal(pricing, checkin, checkout);
      renderPriceBox(priceBox, pricing, calc, minReq);

      const ok = calc && calc.nights >= minReq;
      btn.disabled = !ok;
      btn.textContent = ok ? "Request booking" : `Minimum ${minReq} nights`;
      note.textContent = ok ? "Request • Pay to confirm" : "Select a longer stay";
    };

    $("#checkin")?.addEventListener("change", updatePricing);
$("#checkout")?.addEventListener("change", updatePricing);
$("#guests")?.addEventListener("change", updatePricing);

// nový event z Litepickeru
document.addEventListener("dates:updated", updatePricing);

updatePricing();

    // Request button (MVP behaviour)
    btn?.addEventListener("click", () => {
      const checkin = $("#checkin")?.value;
      const checkout = $("#checkout")?.value;
      const guests = $("#guests")?.value;

      const msg = `Booking request:\n${property.title}\nCheck-in: ${checkin}\nCheck-out: ${checkout}\nGuests: ${guests}`;
      alert(msg);
    });
  }

  init().catch(err => {
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