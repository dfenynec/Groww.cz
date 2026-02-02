const DATA_SOURCE_URL = "data/property.json"; // později API endpoint

async function loadData() {
  const res = await fetch(DATA_SOURCE_URL, { cache: "no-store" });
  if (!res.ok) throw new Error("Failed to load property data");
  return await res.json();
}

function getByPath(obj, path) {
  return path.split(".").reduce((acc, key) => (acc ? acc[key] : null), obj);
}

function bindSimple(data) {
  document.querySelectorAll("[data-bind]").forEach(el => {
    const path = el.getAttribute("data-bind");
    const value = getByPath(data, path);
    if (value !== null && value !== undefined) el.textContent = value;
  });
}

function escapeHtml(str) {
  return String(str)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function renderHero(data) {
  const first = data.property.gallery?.[0];
  const hero = document.getElementById("heroMedia");
  hero.innerHTML = first ? `<img src="${first}" alt="" />` : "";

  const highlights = document.getElementById("highlights");
  highlights.innerHTML = (data.property.highlights || [])
    .slice(0, 8)
    .map(h => `<span class="badge rounded-pill badge-soft">${escapeHtml(h)}</span>`)
    .join("");

  const cityCountry = `${data.property.location?.city || ""}${data.property.location?.city ? " • " : ""}${data.property.location?.country || ""}`;
  document.getElementById("cityCountry").textContent = cityCountry;
  document.getElementById("cityCountry2").textContent = cityCountry;
}

function renderDescription(data) {
  const box = document.getElementById("description");
  box.innerHTML = (data.property.description || [])
    .map(p => `<p class="text-secondary mb-2">${escapeHtml(p)}</p>`)
    .join("");
}

function renderGallery(data) {
  const box = document.getElementById("gallery");
  const imgs = (data.property.gallery || []).slice(0, 8);

  box.innerHTML = imgs.map((src) => `
    <div class="col-4 col-md-3">
      <button class="gallery-btn" type="button" data-src="${src}">
        <img class="gallery-img shadow-sm" src="${src}" alt="" loading="lazy">
      </button>
    </div>
  `).join("");

  const modalEl = document.getElementById("imageModal");
  const modal = new bootstrap.Modal(modalEl);
  const modalImg = document.getElementById("modalImage");

  box.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-src]");
    if (!btn) return;
    modalImg.src = btn.getAttribute("data-src");
    modal.show();
  });
}

function renderAmenities(data) {
  const box = document.getElementById("amenitiesList");
  const items = data.property.amenities || [];
  box.innerHTML = items.map(a => `
    <div class="col-6 col-md-4">
      <div class="d-flex gap-2 align-items-start">
        <div class="text-primary">✓</div>
        <div class="text-secondary">${escapeHtml(a)}</div>
      </div>
    </div>
  `).join("");
}

function renderRules(data) {
  const box = document.getElementById("rulesList");
  box.innerHTML = (data.property.houseRules || [])
    .map(r => `<li class="mb-2">${escapeHtml(r)}</li>`)
    .join("");
}

function renderFaq(data) {
  const box = document.getElementById("faqList");
  const items = data.property.faq || [];
  box.innerHTML = items.map((item, idx) => `
    <div class="accordion-item">
      <h2 class="accordion-header" id="h${idx}">
        <button class="accordion-button ${idx ? "collapsed" : ""}" type="button"
                data-bs-toggle="collapse" data-bs-target="#c${idx}">
          ${escapeHtml(item.q)}
        </button>
      </h2>
      <div id="c${idx}" class="accordion-collapse collapse ${idx ? "" : "show"}" data-bs-parent="#faqList">
        <div class="accordion-body text-secondary">${escapeHtml(item.a)}</div>
      </div>
    </div>
  `).join("");
}

function renderLocation(data) {
  document.getElementById("addressLine").textContent = data.property.location?.addressLine || "";

  const mapEmbedUrl = data.property.location?.mapEmbedUrl;
  if (mapEmbedUrl) {
    document.getElementById("mapBox").innerHTML = `
      <iframe title="Map" src="${mapEmbedUrl}" loading="lazy"
              style="width:100%;height:340px;border:0;display:block;"></iframe>
    `;
  }
}

function setupBookingForm(data) {
  const policies = document.getElementById("policiesLink");
  policies.href = data.booking.policiesUrl || "#";

  const emailLink = document.getElementById("contactEmailLink");
  emailLink.textContent = data.booking.contactEmail;
  emailLink.href = `mailto:${data.booking.contactEmail}`;

  const phoneLink = document.getElementById("contactPhoneLink");
  phoneLink.textContent = data.booking.contactPhone;
  phoneLink.href = `tel:${data.booking.contactPhone.replace(/\s+/g, "")}`;

  const form = document.getElementById("bookingForm");
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const fd = new FormData(form);

    const payload = {
      propertySlug: data.property.slug,
      checkin: fd.get("checkin"),
      checkout: fd.get("checkout"),
      guests: Number(fd.get("guests")),
      email: fd.get("email"),
      message: fd.get("message") || ""
    };

    // MVP fallback: mailto. Později nahradíš POSTem na backend.
    const subject = encodeURIComponent(`Booking request: ${data.property.title}`);
    const body = encodeURIComponent(
      `Property: ${data.property.title}\n` +
      `Dates: ${payload.checkin} to ${payload.checkout}\n` +
      `Guests: ${payload.guests}\n` +
      `Email: ${payload.email}\n\n` +
      `Message:\n${payload.message}\n`
    );

    window.location.href = `mailto:${data.booking.contactEmail}?subject=${subject}&body=${body}`;
  });
}

(async function init(){
  try{
    const data = await loadData();
    bindSimple(data);
    renderHero(data);
    renderDescription(data);
    renderGallery(data);
    renderAmenities(data);
    renderRules(data);
    renderFaq(data);
    renderLocation(data);
    setupBookingForm(data);

    document.getElementById("year").textContent = new Date().getFullYear();
  } catch(err){
    console.error(err);
    document.body.innerHTML = `
      <div class="container py-5">
        <h1 class="h3">Template error</h1>
        <p class="text-secondary">Could not load <code>${DATA_SOURCE_URL}</code>.</p>
      </div>
    `;
  }
})();