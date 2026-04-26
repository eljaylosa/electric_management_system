$(document).ready(function () {

  // load saved theme
  if (localStorage.getItem("theme") === "dark") {
    document.documentElement.setAttribute("data-theme", "dark");
    $('#themeToggle').text('☀️');
  }

  // toggle theme
  $('#themeToggle').click(function () {

    let current = document.documentElement.getAttribute("data-theme");

    if (current === "dark") {
      document.documentElement.removeAttribute("data-theme");
      localStorage.setItem("theme", "light");
      $(this).text('🌙');
    } else {
      document.documentElement.setAttribute("data-theme", "dark");
      localStorage.setItem("theme", "dark");
      $(this).text('☀️');
    }

  });

});