<?php
namespace cnTestTask;

//init functions
/**
 *
 */
class App
{
	public static $instance = null;

	public static $main_file;

	public static $app_url;

	public function __construct($main_file)
	{
		self::$app_url = plugin_dir_url( $main_file ).'app';

		$this->initActions();
		$this->initFilters();
		$this->initShortcodes();
	}

	public static function run($main_file)
	{
	    if (!self::$instance) {
	        self::$instance = new self($main_file);
	    }

	    return self::$instance;
	}

	public function initActions() {
		add_action( 'init', array(&$this, 'onInitPostTypes'));
		add_action( 'init', array(&$this, 'onInitMetaBoxes'));
		add_action( 'wp_enqueue_scripts', array($this, 'onInitScriptsAndStyles'), 21);

		//Ajax New task Actions
		add_action( 'wp_ajax_nopriv_add_new_task', array(&$this, 'add_new_task_callback' ));
		add_action( 'wp_ajax_add_new_task', array(&$this, 'add_new_task_callback' ));

	}

	public function initShortcodes() {
		//shortcode [cn_dashboard]
		add_shortcode( 'cn_dashboard', array(&$this, 'cn_dashboard_function' ));
	}

	public function initFilters() {
		//Add table col
		add_filter('cn_tasks_thead_cols', array(&$this, 'add_custom_col'));
		add_filter('cn_tasks_tbody_row_cols', array(&$this, 'add_custom_col_data'), 10, 2);

		//Add New menu item
		add_filter('cn_menu', array(&$this, 'add_tasks_menu_item'));

		//Add Modal Html
		add_filter('cn_after_content', array(&$this, 'add_task_modal'));

		//Change Title
		add_filter( 'pre_get_document_title', array(&$this, 'change_title' ) );
	}

	public function add_custom_col($cols) {
		$new_col = __('Freelancer', 'cn');
		array_splice($cols, 2, 0, $new_col);
		return $cols;
	}

	public function add_custom_col_data($cols, $task) {
		$freelancer_id = get_post_meta(trim($task->id(), '#'), '_assigned_freelancer', true);
		$new_col = get_the_title($freelancer_id);
		array_splice($cols, 2, 0, $new_col);
		return $cols;
	}

	public function add_tasks_menu_item($menu) {
		$menu['#add_task_popup'] = [
			'title' => __('Add New Task', 'cn'),
			'icon' => 'fa-plus-circle',
			'url' => '#popup_add_task'
		];
		return $menu;
	}

	public function add_task_modal() {
		ob_start();
		?>
		<!-- Modal -->
		<div class="modal fade" id="popup_add_task" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="exampleModalLabel"><?php _e('Add new Task', 'cn'); ?></h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<form>
							<div class="form-group row">
								<label for="taskTitle" class="col-sm-2 col-form-label"><?php _e('Task Title', 'cn') ?></label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="taskTitle" placeholder="Title" name="taskTitle">
								</div>
							</div>
							<?php $freelancers = get_posts(array('post_type' => 'cn_freelancer')); ?>
							<?php if ($freelancers): ?>
								<div class="form-group row">
									<label for="freelancers" class="col-sm-2 col-form-label"><?php _e('Freelancer', 'cn'); ?></label>
									<div class="col-sm-10">
										<select class="form-control" id="freelancers" name="freelancers">
											<option><?php _e('Select freelancer', 'cn'); ?></option>
											<?php foreach ($freelancers as $freelancer): ?>
												<option value="<?php echo $freelancer->ID ?>"><?php echo $freelancer->post_title; ?></option>
											<?php endforeach ?>
										</select>
									</div>
								</div>
							<?php endif ?>
						</form>
						<button type="button" class="btn btn-primary create-new-task">Add</button>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</div>
		<?php
		echo ob_get_clean();
	}

	public function cn_dashboard_function( $atts ){
		ob_start();
		?>
		<div class="row">
			<?php if ($count_fl = wp_count_posts('cn_freelancer')): ?>
				<div class="col-md-3">
					<div class="panel panel-primary">
						<div class="panel-heading">
							<div class="row">
								<div class="col-xs-3">
									<i class="fa fa-users fa-5x"></i>
								</div>
								<div class="col-xs-9 text-right">
									<div class="huge"><?php echo $count_fl->publish; ?></div>
									<div><?php _e('Freelancers', 'cn') ?></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php endif ?>
			<?php if ($count_tasks = wp_count_posts('task')): ?>
				<div class="col-md-3">
					<div class="panel panel-green">
						<div class="panel-heading">
							<div class="row">
								<div class="col-xs-3">
									<i class="fa fa-tasks fa-5x"></i>
								</div>
								<div class="col-xs-9 text-right">
									<div class="huge"><?php echo $count_tasks->publish; ?></div>
									<div><?php _e('Tasks', 'cn') ?></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php endif ?>
		</div>

		<?php
		return ob_get_clean();
	}

	public function add_new_task_callback(){
		$task_title = isset($_POST['task_title']) ? $_POST['task_title'] : '';
		$freelancer_id = isset($_POST['freelancer_id']) ? $_POST['freelancer_id'] : '';

		// New Task data array
		$task_data = array(
			'post_title'    => wp_strip_all_tags( $task_title ),
			'post_status'   => 'publish',
			'post_author'   => 1,
			'post_type' => 'task',
			'meta_input' => array('_assigned_freelancer' => $freelancer_id),
		);

		//insert post to db
		$task_id = wp_insert_post( $task_data );

		if ($task_id) {
			echo 'Success!!!';
		}

		wp_die();
	}

	/**
	 * Init post type freelancer
	 */
	public function onInitPostTypes()
	{
	    $labels = array(
	        'name'               => __( 'Freelancers', 'cn' ),
	        'singular_name'      => __( 'Freelancer',  'cn' ),
	        'menu_name'          => __( 'Freelancers', 'cn' ),
	        'name_admin_bar'     => __( 'Freelancer',  'cn' ),
	        'add_new'            => __( 'Add New', 'cn' ),
	        'add_new_item'       => __( 'Add New Freelancer', 'cn' ),
	        'new_item'           => __( 'New Freelancer', 'cn' ),
	        'edit_item'          => __( 'Edit Freelancer', 'cn' ),
	        'view_item'          => __( 'View Freelancer', 'cn' ),
	        'all_items'          => __( 'All Freelancers', 'cn' ),
	        'search_items'       => __( 'Search Freelancers', 'cn' ),
	        'parent_item_colon'  => __( 'Parent Freelancer:', 'cn' ),
	        'not_found'          => __( 'No Freelancers found.', 'cn' ),
	        'not_found_in_trash' => __( 'No Freelancers found in Trash.', 'cn' )
	    );

	    $args = array(
	        'labels'             => $labels,
	        'public'             => true,
	        'publicly_queryable' => true,
	        'show_ui'            => true,
	        'show_in_menu'       => true,
	        'query_var'          => true,
	        'rewrite'            => array( 'slug' => 'cn_freelancer' ),
	        'menu_icon'            => 'dashicons-admin-users',
	        'capability_type'    => 'post',
	        'has_archive'        => true,
	        'hierarchical'       => false,
	        'menu_position'      => null,
	        'supports'           => array( 'title', 'editor', 'thumbnail' )
	    );

	    register_post_type( 'cn_freelancer', $args );
	}

	public static function onInitMetaBoxes()
	{
		add_action('add_meta_boxes', [self::class, 'addMetaBox']);
		add_action('save_post', [self::class, 'saveMetaBox']);
	}

	public static function addMetaBox()
	{
		add_meta_box(
	      'cn_assigned_freelancer', // Unique ID
	      'Assigned Freelancer', // Box title
	      [self::class, 'htmlMetaBox'], // Content callback, must be of type callable
	   	'task', // Post type task
	   	'side' // Position of meta box
	   );
	}

	public static function saveMetaBox($post_id)
	{
		if (array_key_exists('freelancer', $_POST)) {
			update_post_meta(
				$post_id,
				'_assigned_freelancer',
				$_POST['freelancer']
			);
		}
	}

	public static function htmlMetaBox($post)
	{
		$value = get_post_meta($post->ID, '_assigned_freelancer', true);
		$freelancers = get_posts(array('post_type' => 'cn_freelancer'));
		?>
		<select name="freelancer" id="freelancer" class="postbox">
			<option value="">Select freelancer</option>
			<?php foreach ($freelancers as $freelancer): ?>
				<option value="<?php echo $freelancer->ID ?>" <?php selected($value, $freelancer->ID); ?>><?php echo $freelancer->post_title; ?></option>
			<?php endforeach ?>
		</select>
		<?php
	}

	public function onInitScriptsAndStyles()
	{
		wp_enqueue_script('jquery');

		wp_enqueue_script(
			'main',
			self::$app_url.'/vendor/js/main.js',
			['jquery'],
			null,
			true
		);

		//dataTables
		wp_enqueue_script(
			'jquery.dataTables.scripts',
			'//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js',
			['jquery'],
			null,
			true
		);
		wp_enqueue_style(
		   'jquery.dataTables.styles',
		   '//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css'
		);

		wp_localize_script('main', 'ajax',
	       array(
	       	'url' => admin_url('admin-ajax.php'),
	       )
		);
	}

	public function change_title($title) {
		if (isset($GLOBALS['pagename'])) {
			$title = ucfirst( $GLOBALS['pagename'] );
		}
		return $title;
	}
}
