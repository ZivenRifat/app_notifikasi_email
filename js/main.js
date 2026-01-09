/**
 * Email Notification App
 * Handles submit (now / 5min / 12hour)
 */

(function () {
  "use strict";

  function initEmailForm() {
    const form = document.getElementById("emailForm");
    if (!form) return;

    form.addEventListener("submit", handleSubmit);
  }

  function handleSubmit(e) {
    e.preventDefault();

    const emailInput = document.getElementById("email");
    const email = emailInput.value.trim();

    const submitter = e.submitter;
    const type = submitter?.dataset?.type || "now";

    if (!email) {
      showMessage("Email tidak boleh kosong.", "error");
      return;
    }

    if (!isValidEmail(email)) {
      showMessage("Format email tidak valid.", "error");
      return;
    }

    sendEmail(email, type, submitter);
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  function sendEmail(email, type, button) {
    const form = document.getElementById("emailForm");
    const buttons = form.querySelectorAll("button");

    if (button.disabled) return;

    const originalText = button.innerHTML;

    // Disable semua tombol
    buttons.forEach((btn) => (btn.disabled = true));

    button.innerHTML = "Mengirim...";
    button.setAttribute("aria-busy", "true");

    const formData = new FormData();
    formData.append("email", email);
    formData.append("type", type);

    fetch("email/kirim_email.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          showMessage(data.message, "success");
          form.reset();
        } else {
          showMessage(data.message, "error");
        }
      })
      .catch(() => {
        showMessage(
          "Terjadi kesalahan pada sistem. Silakan coba lagi.",
          "error"
        );
      })
      .finally(() => {
        buttons.forEach((btn) => {
          btn.disabled = false;
          btn.removeAttribute("aria-busy");
          btn.innerHTML = btn === button ? originalText : btn.innerHTML;
        });
      });
  }

  /**
   * =========================
   * NOTIFICATION UI
   * =========================
   */
  function showMessage(message, type = "success", duration = 5000) {
    const container = document.getElementById("notificationContainer");
    if (!container) return alert(message);

    const notif = document.createElement("div");
    notif.className = `notification ${type}`;
    notif.setAttribute("role", "alert");

    notif.innerHTML = `
      <div class="notification-icon">${type === "success" ? "✓" : "✕"}</div>
      <div class="notification-content">
        <strong>${type === "success" ? "Berhasil" : "Error"}</strong>
        <div>${escapeHtml(message)}</div>
      </div>
      <button class="notification-close" type="button">×</button>
      <div class="notification-progress"></div>
    `;

    container.appendChild(notif);

    requestAnimationFrame(() => notif.classList.add("show"));

    const close = notif.querySelector(".notification-close");
    close.addEventListener("click", () => removeNotif(notif));

    setTimeout(() => removeNotif(notif), duration);
  }

  function removeNotif(el) {
    el.classList.remove("show");
    el.classList.add("hide");
    setTimeout(() => el.remove(), 300);
  }

  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  document.readyState === "loading"
    ? document.addEventListener("DOMContentLoaded", initEmailForm)
    : initEmailForm();
})();
