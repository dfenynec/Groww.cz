<div id="template-header">
  <div class="header-content">
    <div class="header-logo">
      <img src="images/logo_allwhite.png" alt="Vaše společnost" height="32">
      <span class="header-title">Vaše společnost – Náhled šablony</span>
    </div>
    <div class="header-buttons">
      <a href="/demo-logistics.html" class="header-btn">Logistika</a>
      <a href="/demo-business.html" class="header-btn">Business</a>
      <a href="/demo-portfolio.html" class="header-btn">Portfolio</a>
      <button class="header-btn" onclick="window.history.back()">Zpět</button>
    </div>
  </div>
</div>

<style>
#template-header {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.07);
  z-index: 1000;
  font-family: 'Segoe UI', Arial, sans-serif;
}
.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 1200px;
  margin: 0 auto;
  padding: 8px 24px;
}
.header-logo {
  display: flex;
  align-items: center;
  gap: 12px;
}
.header-title {
  font-weight: 600;
  font-size: 1.1rem;
  color: #222;
}
.header-buttons {
  display: flex;
  gap: 10px;
}
.header-btn {
  background: #f5f5f5;
  border: none;
  color: #222;
  padding: 7px 18px;
  border-radius: 6px;
  font-size: 1rem;
  cursor: pointer;
  text-decoration: none;
  transition: background 0.2s, color 0.2s;
}
.header-btn:hover {
  background: #0077ff;
  color: #fff;
}
body {
  padding-top: 56px; /* výška lišty */
}
@media (max-width: 600px) {
  .header-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  .header-buttons {
    flex-wrap: wrap;
    gap: 6px;
  }
}
</style>