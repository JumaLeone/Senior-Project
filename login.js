document.addEventListener("DOMContentLoaded", function () {
  const registerBtn = document.getElementById("registerBtn");
  const loginBtn = document.getElementById("loginBtn");
  const registerForm = document.getElementById("registerForm");
  const loginForm = document.getElementById("loginForm");

  registerBtn.addEventListener("click", function () {
    registerForm.classList.remove("hidden");
    loginForm.classList.add("hidden");
    registerBtn.classList.add("active");
    loginBtn.classList.remove("active");
  });

  loginBtn.addEventListener("click", function () {
    registerForm.classList.add("hidden");
    loginForm.classList.remove("hidden");
    registerBtn.classList.remove("active");
    loginBtn.classList.add("active");
  });
});
