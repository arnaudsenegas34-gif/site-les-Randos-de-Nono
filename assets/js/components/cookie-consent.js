/**
 * Bandeau de consentement cookies — n'active Google Analytics et le pixel
 * Facebook qu'après acceptation explicite de l'utilisateur (RGPD).
 */
(function () {
  var STORAGE_KEY = 'rando_nono_ga_consent';
  var COOKIE_MAX_AGE = 60 * 60 * 24 * 180; // 6 mois
  var gaLoaded = false;
  var fbLoaded = false;

  // Cookie + localStorage en parallèle : certains navigateurs (Safari ITP,
  // extensions anti-tracking) purgent les cookies posés en JS mais laissent
  // le localStorage tranquille, et inversement en navigation privée stricte.
  function getCookie( name ) {
    var match = document.cookie.match( new RegExp( '(?:^|; )' + name + '=([^;]*)' ) );
    return match ? decodeURIComponent( match[1] ) : null;
  }

  function setCookie( name, value ) {
    // "Secure" seulement en HTTPS : en local (http://localhost) l'ajouter
    // empêcherait le navigateur d'accepter le cookie.
    var secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = name + '=' + value + '; max-age=' + COOKIE_MAX_AGE + '; path=/; SameSite=Lax' + secure;
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
    // Cookies de mesure limités à 13 mois (recommandation CNIL ; 2 ans par défaut chez Google).
    gtag( 'config', window.randoNonoGA.id, { cookie_expires: 13 * 30 * 24 * 60 * 60 } );
  }

  function loadFB() {
    if ( fbLoaded || ! window.randoNonoGA || ! window.randoNonoGA.fbPixelId ) return;
    fbLoaded = true;

    /* eslint-disable */
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
    n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
    document,'script','https://connect.facebook.net/en_US/fbevents.js');
    /* eslint-enable */

    window.fbq( 'init', window.randoNonoGA.fbPixelId );
    window.fbq( 'track', 'PageView' );
  }

  function showBanner() {
    var banner = document.getElementById( 'cookie-consent' );
    if ( banner ) banner.classList.add( 'is-visible' );
  }

  // Retrait du consentement : efface les cookies déjà posés par Google
  // Analytics (_ga, _ga_XXXX, _gid) et le pixel Facebook (_fbp), sur le
  // domaine du site et son domaine parent, où GA les dépose souvent.
  function effacerCookiesTraceurs() {
    var noms = document.cookie.split( ';' ).map( function ( c ) { return c.split( '=' )[0].trim(); } )
      .filter( function ( n ) { return /^(_ga|_gid|_gat|_fbp|_fbc)/.test( n ); } );
    var hote = window.location.hostname;
    var parent = hote.split( '.' ).slice( -2 ).join( '.' );
    noms.forEach( function ( n ) {
      [ '', '; domain=' + hote, '; domain=.' + hote, '; domain=.' + parent ].forEach( function ( d ) {
        document.cookie = n + '=; max-age=0; path=/' + d;
      } );
    } );
  }

  function hideBanner() {
    var banner = document.getElementById( 'cookie-consent' );
    if ( banner ) banner.classList.remove( 'is-visible' );
  }

  document.addEventListener( 'DOMContentLoaded', function () {
    if ( ! window.randoNonoGA || ( ! window.randoNonoGA.id && ! window.randoNonoGA.fbPixelId ) ) return;

    var consent = getStoredConsent();
    if ( consent === '1' ) {
      loadGA();
      loadFB();
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
        loadFB();
        hideBanner();
      } );
    }
    if ( refuseBtn ) {
      refuseBtn.addEventListener( 'click', function () {
        storeConsent( '0' );
        effacerCookiesTraceurs();
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
