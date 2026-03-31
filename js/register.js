document.addEventListener("DOMContentLoaded", () => {
  // ── Helpers ──────────────────────────────────────────────────
  function showError(input, msg) {
    input.classList.add("input-error");
    let hint = input.parentElement.querySelector(".field-error-msg");
    if (!hint) {
      hint = document.createElement("span");
      hint.className = "field-error-msg";
      hint.style.cssText =
        "display:block;color:#D32F2F;font-size:0.82rem;margin-top:0.3rem;";
      input.parentElement.appendChild(hint);
    }
    hint.textContent = msg;
  }

  function clearError(input) {
    input.classList.remove("input-error");
    const hint = input.parentElement.querySelector(".field-error-msg");
    if (hint) hint.textContent = "";
  }

  function clearAllErrors() {
    form
      .querySelectorAll(".input-error")
      .forEach((el) => el.classList.remove("input-error"));
    form
      .querySelectorAll(".field-error-msg")
      .forEach((el) => (el.textContent = ""));
  }

  // ── Regex patterns ────────────────────────────────────────────
  const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const PHONE_RE = /^[6-9]\d{9}$/; // Indian 10-digit mobile
  const USERNAME_RE = /^[a-zA-Z0-9_]{3,20}$/; // 3-20 alphanumeric / underscore
  const PWD_RE = /^(?=.*[A-Za-z])(?=.*\d).{8,}$/; // min 8 chars, ≥1 letter & ≥1 digit

  // ── Form reference ────────────────────────────────────────────
  const form = document.querySelector('form[action="php/register.php"]');
  if (!form) return;

  // ── Real-time error clearing ──────────────────────────────────
  form.querySelectorAll(".form-input, .form-select").forEach((input) => {
    ["input", "change"].forEach((evt) =>
      input.addEventListener(evt, () => clearError(input)),
    );
  });

  // ── Submit validation ─────────────────────────────────────────
  form.addEventListener("submit", (e) => {
    clearAllErrors();

    const get = (name) => form.querySelector(`[name="${name}"]`);
    let errors = [];
    let firstBad = null;

    function flag(input, msg) {
      showError(input, msg);
      errors.push(msg);
      if (!firstBad) firstBad = input;
    }

    // Required empty checks
    const fullName = get("full_name");
    const username = get("username");
    const email = get("email");
    const phone = get("phone");
    const gender = get("gender");
    const dob = get("dob");
    const password = get("password");
    const confirmPw = get("confirm_password");
    const role = get("role");

    if (!fullName.value.trim()) flag(fullName, "Full name is required.");

    // Username: required + format
    if (!username.value.trim()) {
      flag(username, "Username is required.");
    } else if (!USERNAME_RE.test(username.value.trim())) {
      flag(
        username,
        "Username must be 3–20 characters: letters, numbers or underscore only.",
      );
    }

    // Email: required + format
    if (!email.value.trim()) {
      flag(email, "Email is required.");
    } else if (!EMAIL_RE.test(email.value.trim())) {
      flag(
        email,
        "Please enter a valid email address (e.g. user@example.com).",
      );
    }

    // Phone: required + 10-digit Indian format
    if (!phone.value.trim()) {
      flag(phone, "Phone number is required.");
    } else if (!PHONE_RE.test(phone.value.trim())) {
      flag(
        phone,
        "Enter a valid 10-digit mobile number starting with 6–9.",
      );
    }

    // Gender
    if (!gender.value) flag(gender, "Please select a gender.");

    // Date of Birth: required + not a future date
    if (!dob.value) {
      flag(dob, "Date of birth is required.");
    } else {
      const dobDate = new Date(dob.value);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      if (dobDate >= today)
        flag(dob, "Date of birth cannot be today or a future date.");
    }

    // Password: required + strength
    if (!password.value) {
      flag(password, "Password is required.");
    } else if (!PWD_RE.test(password.value)) {
      flag(
        password,
        "Password must be at least 8 characters and include a letter and a number.",
      );
    }

    // Confirm password: required + match (only if password itself is valid)
    if (!confirmPw.value) {
      flag(confirmPw, "Please confirm your password.");
    } else if (password.value && confirmPw.value !== password.value) {
      flag(confirmPw, "Passwords do not match.");
    }

    // Role
    if (!role.value) flag(role, "Please select a role.");

    // Specialization (only required when role = doctor)
    const specInput = form.querySelector("#specializationGroup input");
    if (role.value === "doctor" && specInput && !specInput.value.trim())
      flag(specInput, "Specialization is required for doctors.");

    // ── Block submit on any error ─────────────────────────────
    if (errors.length > 0) {
      e.preventDefault();
      if (firstBad) firstBad.focus();
    }
  });

  // ── URL param notifications (from server-side redirects) ──────
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has("error")) {
    showNotification(decodeURIComponent(urlParams.get("error")), "error");
  } else if (urlParams.has("success")) {
    showNotification("Registration successful!", "success");
  }

  // ── Toggle specialization field for doctor role ───────────────
  const roleSelect = document.querySelector('select[name="role"]');
  const specGroup = document.getElementById("specializationGroup");

  roleSelect.addEventListener("change", function () {
    if (this.value === "doctor") {
      specGroup.style.display = "block";
      specGroup.querySelector("input").required = true;
    } else {
      specGroup.style.display = "none";
      specGroup.querySelector("input").required = false;
      clearError(specGroup.querySelector("input"));
    }
  });
});
