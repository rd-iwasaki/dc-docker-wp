$(document).ready(function () {
  $(".question").addClass("active").click(function () {
    $(this).toggleClass("active").next().slideToggle("300");
    $(this).toggleClass('open');
    return false;
  });
});