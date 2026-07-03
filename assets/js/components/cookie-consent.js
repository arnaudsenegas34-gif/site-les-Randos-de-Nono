/**
 * Bandeau de consentement cookies — n'active Google Analytics
 * qu'après acceptation explicite de l'utilisateur (RGPD).
 */
(function () {
  var COOKIE_NAME = 'rando_nono_ga_consent';
  var COOKIE_MAX_AGE = 60 * 60 * 24 * 180; // 6 mois
  var gaLoaded = false;

  function getCookie( name ) {
    var match = document.cookie.match( new RegExp( '(?:^|; )' + name + '=([^;]*)' ) );
    return match ? decodeURIComponent( match[1] ) : null;
  }

  function setCookie( name, value ) {
    document.cookie = name + '=' + value + '; max-age=' + COOKIE_MAX_AGE + '; path=/; SameSite=Lax';
  }

  function loadGA() {
    if ( gaLoaded || ! window.randoNonoGA || ! window.randoNonoGA.id ) return;
    gaLoaded = true;

    var script = document.createElement( 'script' );
    script.async = true;
    script.src = 'https://www.googletagmanager.com/gtag/js?id=' + window.randoNonoGA.id;
    document.head.appendChild( script );

    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { dataLayer.push( arguments ); };
    gtag( 'js', new Date() );
    gtag( 'config', window.randoNonoGA.id );
  }

  function showBanner() {
    var banner = document.getElementById( 'cookie-consent' );
    if ( banner ) banner.classList.add( 'is-visible' );
  }

  function hideBanner() {
    var banner = document.getElementById( 'cookie-consent' );
    if ( banner ) banner.classList.remove( 'is-visible' );
  }

  document.addEventListener( 'DOMContentLoaded', function () {
    if ( ! window.randoNonoGA || ! window.randoNonoGA.id ) return;

    var consent = getCookie( COOKIE_NAME );
    if ( consent === '1' ) {
      loadGA();
    } else if ( consent !== '0' ) {
      showBanner();
    }

    var acceptBtn = document.getElementById( 'cookie-consent-accept' );
    var refuseBtn = document.getElementById( 'cookie-consent-refuse' );
    var manageBtn = document.getElementById( 'cookie-consent-manage' );

    if ( acceptBtn ) {
      acceptBtn.addEventListener( 'click', function () {
        setCookie( COOKIE_NAME, '1' );
        loadGA();
        hideBanner();
      } );
    }
    if ( refuseBtn ) {
      refuseBtn.addEventListener( 'click', function () {
        setCookie( COOKIE_NAME, '0' );
        hideBanner();
      } );
    }
    if ( manageBtn ) {
      manageBtn.addEventListener( 'click', function () {
        showBanner();
      } );
    }
  } );
})();
