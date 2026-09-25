<?php
/**
 * Class WP_REST_API_Log_Test_Admin_List_Table
 *
 * @package wp-rest-api-log
 */

/**
 * Tests for the log entries list in the admin.
 */
class WP_REST_API_Log_Test_Admin_List_Table extends WP_UnitTestCase {

	/**
	 * List table helper under test.
	 *
	 * @var WP_REST_API_Log_Admin_List_Table
	 */
	protected $list_table;

	/**
	 * Loads the admin APIs.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Signature is fixed by the test case.
		require_once ABSPATH . 'wp-admin/includes/template.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';
	}

	/**
	 * Sets up each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->list_table = new WP_REST_API_Log_Admin_List_Table();
	}

	/**
	 * Resets globals changed by the tests.
	 *
	 * @return void
	 */
	public function tear_down() {
		$_GET = array();
		unset( $GLOBALS['current_screen'] );
		parent::tear_down();
	}

	/**
	 * Inserts a log entry.
	 *
	 * @param  array $args Insert arguments.
	 * @return int Post ID.
	 */
	protected function insert_entry( $args = array() ) {
		$db = new WP_REST_API_Log_DB();
		return $db->insert(
			wp_parse_args(
				$args,
				array(
					'route'                => '/wp/v2/posts',
					'method'               => 'POST',
					'status'               => 201,
					'ip_address'           => '10.0.0.9',
					'http_x_forwarded_for' => '172.16.5.5',
					'user'                 => 'list_user',
					'milliseconds'         => 1234,
					'response'             => array(
						'body' => 'abcdef',
					),
				)
			)
		);
	}

	/**
	 * Returns the output of a list table column.
	 *
	 * @param  string $column  Column name.
	 * @param  int    $post_id Post ID.
	 * @return string
	 */
	protected function column( $column, $post_id ) {
		ob_start();
		$this->list_table->custom_column( $column, $post_id );
		return ob_get_clean();
	}

	/**
	 * Tests that the list table hooks are registered on admin_init.
	 *
	 * @return void
	 */
	public function test_hooks() {

		$this->list_table->plugins_loaded();
		$this->assertSame( 10, has_action( 'admin_init', array( $this->list_table, 'admin_init' ) ) );

		$this->list_table->admin_init();

		$this->assertSame( 10, has_filter( 'post_row_actions', array( $this->list_table, 'post_row_actions' ) ) );
		$this->assertSame( 10, has_filter( 'manage_edit-wp-rest-api-log_columns', array( $this->list_table, 'custom_columns' ) ) );
		$this->assertSame( 10, has_action( 'manage_wp-rest-api-log_posts_custom_column', array( $this->list_table, 'custom_column' ) ) );
		$this->assertSame( 10, has_filter( 'bulk_actions-edit-wp-rest-api-log', array( $this->list_table, 'remove_edit_bulk_action' ) ) );
		$this->assertSame( 10, has_action( 'restrict_manage_posts', array( $this->list_table, 'add_dropdowns' ) ) );
		$this->assertSame( 10, has_action( 'pre_get_posts', array( $this->list_table, 'add_tax_queries' ) ) );
	}

	/**
	 * Tests the row actions for log entries.
	 *
	 * @return void
	 */
	public function test_post_row_actions() {

		$post_id = $this->insert_entry();
		$actions = array(
			'edit'                 => 'Edit',
			'inline hide-if-no-js' => 'Quick Edit',
			'trash'                => 'Trash',
		);

		$result = $this->list_table->post_row_actions( $actions, get_post( $post_id ) );

		$this->assertSame( array( 'view', 'trash' ), array_keys( $result ) );
		$this->assertStringContainsString( esc_url( get_permalink( $post_id ) ), $result['view'] );
		$this->assertStringContainsString( 'aria-label="View &#8220;/wp/v2/posts&#8221;"', $result['view'] );

		// Trashed entries do not get a view link.
		wp_trash_post( $post_id );
		$result = $this->list_table->post_row_actions( $actions, get_post( $post_id ) );
		$this->assertSame( array( 'trash' ), array_keys( $result ) );

		// Other post types are left alone.
		$other_post = self::factory()->post->create_and_get();
		$this->assertSame( $actions, $this->list_table->post_row_actions( $actions, $other_post ) );
	}

	/**
	 * Tests the list table columns.
	 *
	 * @return void
	 */
	public function test_custom_columns() {

		$columns = $this->list_table->custom_columns(
			array(
				'cb'     => '',
				'title'  => 'Title',
				'author' => 'Author',
			)
		);

		$this->assertSame( array( 'cb', 'date', 'method', 'title', 'status', 'elapsed', 'length', 'ip-address', 'user' ), array_keys( $columns ) );
	}

	/**
	 * Tests the value shown in each custom column.
	 *
	 * @return void
	 */
	public function test_custom_column() {

		$post_id = $this->insert_entry();

		$this->assertSame( 'POST', $this->column( 'method', $post_id ) );
		$this->assertSame( '201', $this->column( 'status', $post_id ) );
		$this->assertSame( '1,234ms', $this->column( 'elapsed', $post_id ) );
		$this->assertSame( '8', $this->column( 'length', $post_id ) );
		$this->assertSame( 'list_user', $this->column( 'user', $post_id ) );
		$this->assertSame( '10.0.0.9', $this->column( 'ip-address', $post_id ) );
		$this->assertSame( '', $this->column( 'unknown', $post_id ) );

		update_option( 'wp-rest-api-log-settings-general', array( 'ip-address-display' => 'http_x_forwarded_for' ) );
		$this->assertSame( '172.16.5.5', $this->column( 'ip-address', $post_id ) );

		// A second entry replaces the cached one.
		$other = $this->insert_entry( array( 'method' => 'DELETE' ) );
		$this->assertSame( 'DELETE', $this->column( 'method', $other ) );
	}

	/**
	 * Tests the taxonomy filter dropdowns.
	 *
	 * @return void
	 */
	public function test_add_dropdowns() {

		$this->insert_entry();

		ob_start();
		$this->list_table->add_dropdowns( 'post' );
		$this->assertSame( '', ob_get_clean() );

		ob_start();
		$this->list_table->add_dropdowns( WP_REST_API_Log_DB::POST_TYPE );
		$html = ob_get_clean();

		$this->assertSame( 3, substr_count( $html, '<select ' ) );
		$this->assertStringContainsString( 'name="wp-rest-api-log-method"', $html );
		$this->assertStringContainsString( 'name="wp-rest-api-log-status"', $html );
		$this->assertStringContainsString( 'name="wp-rest-api-log-source"', $html );
	}

	/**
	 * Tests that the dropdown taxonomies can be filtered.
	 *
	 * @return void
	 */
	public function test_get_dropdown_taxonomies() {

		$this->assertSame(
			array( 'wp-rest-api-log-method', 'wp-rest-api-log-status', 'wp-rest-api-log-source' ),
			$this->list_table->get_dropdown_taxonomies()
		);

		add_filter(
			'wp-rest-api-log-taxonomy-dropdowns',
			function () {
				return array( 'wp-rest-api-log-method' );
			}
		);

		$this->assertSame( array( 'wp-rest-api-log-method' ), $this->list_table->get_dropdown_taxonomies() );
	}

	/**
	 * Tests removing the Edit bulk action.
	 *
	 * @return void
	 */
	public function test_remove_edit_bulk_action() {
		$this->assertSame(
			array( 'trash' => 'Move to Trash' ),
			$this->list_table->remove_edit_bulk_action(
				array(
					'edit'  => 'Edit',
					'trash' => 'Move to Trash',
				)
			)
		);
	}

	/**
	 * Tests filtering the list by the taxonomy dropdowns.
	 *
	 * @return void
	 */
	public function test_add_tax_queries() {

		set_current_screen( 'edit-wp-rest-api-log' );

		$_GET = array(
			'wp-rest-api-log-method' => 'post',
			'wp-rest-api-log-status' => '201',
		);

		$query                   = new WP_Query();
		$GLOBALS['wp_the_query'] = $query;

		$this->list_table->add_tax_queries( $query );

		$this->assertSame(
			array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'wp-rest-api-log-method',
					'field'    => 'slug',
					'terms'    => 'post',
				),
				array(
					'taxonomy' => 'wp-rest-api-log-status',
					'field'    => 'slug',
					'terms'    => '201',
				),
			),
			$query->get( 'tax_query' )
		);
	}

	/**
	 * Tests that the list is not filtered on other screens, for secondary
	 * queries, or without a selected term.
	 *
	 * @return void
	 */
	public function test_add_tax_queries_skipped() {

		$_GET = array( 'wp-rest-api-log-method' => 'post' );

		// Not in the admin.
		$query                   = new WP_Query();
		$GLOBALS['wp_the_query'] = $query;
		$this->list_table->add_tax_queries( $query );
		$this->assertSame( '', $query->get( 'tax_query' ) );

		// Another admin screen.
		set_current_screen( 'edit-post' );
		$this->list_table->add_tax_queries( $query );
		$this->assertSame( '', $query->get( 'tax_query' ) );

		// A secondary query.
		set_current_screen( 'edit-wp-rest-api-log' );
		$secondary = new WP_Query();
		$this->list_table->add_tax_queries( $secondary );
		$this->assertSame( '', $secondary->get( 'tax_query' ) );

		// No term selected.
		$_GET = array();
		$this->list_table->add_tax_queries( $query );
		$this->assertSame( '', $query->get( 'tax_query' ) );
	}
}
