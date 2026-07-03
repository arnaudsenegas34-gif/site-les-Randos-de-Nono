/**
 * Bandeau de consentement cookies — n'active Google Analytics
 * qu'après acceptation explicite de l'utilisateur (RGPD).
 */
(function () {
  var STORAGE_KEY = 'rando_nono_ga_consent';
  var COOKIE_MAX_AGE = 60 * 60 * 24 * 180; // 6 mois
  var gaLoaded = false;

  // Cookie + localStorage en parallèle : certains navigateurs (Safari ITP,
  // extensions anti-tracking) purgent les cookies posés en JS mais laissent
  // le localStorage tranquille, et inversement en navigation privée stricte.
  function getCookie( name ) {
    var match = document.cookie.match( new RegExp( '(?:^|; )' + name + '=([^;]*)' ) );
    return match ? decodeURIComponent( match[1] ) : null;
  }

  function setCookie( name, value ) {
    document.cookie = name + '=' + value + '; max-age=' + COOKIE_MAX_AGE + '; path=/; SameSite=Lax';
  }

  function getStoredConsent() {
    try {
      var fromStorage = window.localStorage.getItem( STORAGE_KEY );
      if ( fromStorage !== null ) return fromStorage;
    } catch ( e ) {}
    return getCookie( STORAGE_KEY );
  }

  function storeConsent( value ) {
    setCookie( STORAGE_KEY, value );
    try {
      window.localStorage.setItem( STORAGE_KEY, value );
    } catch ( e ) {}
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

    var consent = getStoredConsent();
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
        storeConsent( '1' );
        loadGA();
        hideBanner();
      } );
    }
    if ( refuseBtn ) {
      refuseBtn.addEventListener( 'click', function () {
        storeConsent( '0' );
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
