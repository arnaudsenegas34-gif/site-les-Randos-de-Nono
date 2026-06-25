<footer class="site-footer">
  <div class="logo">Les Randos de <span>Nono</span></div>
  <p>Traces GPX & récits de rando · Hérault, France</p>
  <p>
    © <?php echo date( 'Y' ); ?> — Les Randos de Nono
    <?php
    $ml_page = get_page_by_path( 'mentions-legales' );
    if ( ! $ml_page ) $ml_page = get_page_by_path( 'mention-legale' );
    $ml_url = $ml_page ? get_permalink( $ml_page ) : home_url( '/mentions-legales/' );
    ?>
    · <a href="<?php echo esc_url( $ml_url ); ?>">Mentions légales</a>
  </p>
</footer>

<?php wp_footer(); ?>
</body>
</html>
