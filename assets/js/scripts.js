jQuery(function($){
  'use strict';

  $(window).on('scroll', function(){ $('#header').toggleClass('scrolled', $(window).scrollTop() > 10); });
  $('#menuBtn').on('click', function(){ $('#header').toggleClass('nav-open'); });

  function openCart(){ $('#cartDrawer').addClass('show'); $('#overlay').addClass('show'); }
  function closeCart(){ $('#cartDrawer').removeClass('show'); $('#overlay').removeClass('show'); }
  $(document).on('click', '#cartBtn', openCart);
  $(document).on('click', '#closeCart, #overlay', closeCart);

  var toastTimer;
  function showToast(msg){
    $('#toastMsg').text(msg);
    $('#toast').addClass('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function(){ $('#toast').removeClass('show'); }, 2600);
  }

  $(document.body).on('added_to_cart', function(){
    showToast('محصول به سبد خرید اضافه شد ✔');
    var bubble = $('#cartCount');
    bubble.addClass('bump');
    setTimeout(function(){ bubble.removeClass('bump'); }, 450);
  });

  var offerEnd = Date.now() + ((2*24 + 14) * 3600 + 45 * 60 + 30) * 1000;
  function faDigits(s){ return s.replace(/\d/g, function(d){ return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }
  function pad2(n){ return String(n).padStart(2, '0'); }
  function tickOffer(){
    if(!$('#cdS').length) return;
    var s = Math.max(0, Math.floor((offerEnd - Date.now()) / 1000));
    $('#cdD').text(faDigits(pad2(Math.floor(s / 86400))));
    $('#cdH').text(faDigits(pad2(Math.floor(s / 3600) % 24)));
    $('#cdM').text(faDigits(pad2(Math.floor(s / 60) % 60)));
    $('#cdS').text(faDigits(pad2(s % 60)));
  }
  tickOffer(); setInterval(tickOffer, 1000);

  if ('IntersectionObserver' in window) {
    var revealIO = new IntersectionObserver(function(entries){
      entries.forEach(function(en){
        if (en.isIntersecting) { en.target.classList.add('in'); revealIO.unobserve(en.target); }
      });
    }, { threshold: .12 });
    document.querySelectorAll('.reveal').forEach(function(el){ revealIO.observe(el); });
  } else {
    $('.reveal').addClass('in');
  }

  $(document).on('submit', '#newsForm', function(e){
    e.preventDefault();
    showToast('عضویت شما در باشگاه مشتریان ثبت شد 🏆');
    this.reset();
  });
});
