/**
 * Ora & Stone — headless frontend config
 *
 * API_BASE must point at the WordPress + WooCommerce site that acts as
 * the backend. No trailing slash. The Store API is mounted under
 * /wp-json/wc/store/v1 on that site — this file only needs the origin.
 *
 * The WordPress site also needs the CORS bridge in
 * headless/backend/mu-plugin-cors.php installed, with this frontend's
 * own origin added to its allow-list. See headless/README.md.
 */
window.OS_CONFIG = {
  API_BASE: 'https://your-wordpress-site.example',
};
