<?php
class Fluent_Support_Dashboard_Admin {

	/**
	 * all methods of this class instataite automatically
	 */
	protected $user_name;
	protected $user_pass;
    public function __construct() {
		$this->user_name = get_option('fluent_support_wp_username');
		$this->user_pass = get_option('fluent_support_app_pass');
        add_action("admin_menu", array($this, "add_menu_under_settings_page"));
        add_action('after_appsero_myaccount_sidebar', function ($tab) {
            echo '<li><a href="?tab=ticket" class="'. ($tab == 'ticket' ? 'ama-active-tab' : '') . '">Support via Email</a></li>';
        });
        add_action( 'admin_init', array($this, 'add_settings' ));
		add_action( 'appsero_myaccount_custom_tab', array($this, 'my_custom_tab_content'));
		add_action('fluentform_submission_inserted', array($this, 'after_ticket_submit'), 20, 3);
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts' ));
    }

	public function enqueue_scripts(){
        wp_enqueue_script('support-admin-js', FlUENT_SUPPORT_ADMIN . '/js/fluent-support-dashboard-admin.js', array('jquery'), time(), true);
    }

	/**
	 * This method executes after fluent form submission and creates a ticket in fluent support
	 *
	 * @param [type] $entryId
	 * @param [type] $formData
	 * @param [type] $form
	 * @return void
	 */
	public function after_ticket_submit($entryId, $formData, $form) {
		if($form->id != 4) {
			return;
		}
		global $current_user;
        wp_get_current_user();
        $email = (string) $current_user->user_email;
		$contacts_endpoints = "https://fluent.wppool.dev/wp-json/fluent-support/v2/customers?search=$email";
		$contact_response = wp_remote_get( $contacts_endpoints, array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->user_name . ':' . $this->user_pass )
			)
		) );

		$contacts_arr = json_decode(wp_remote_retrieve_body($contact_response), true);


		if(is_array($formData) && !empty($formData)){
			$ticket_subject = sanitize_text_field($formData['ticket_subject']);
			$ticket_description = sanitize_text_field($formData['ticket_description']);
			$ticket_priority = sanitize_text_field($formData['ticket_priority']);
			$support_product = sanitize_text_field($formData['support_product']);
		}
		if(!empty($support_product)){
			switch ($support_product) {
				case 'dark_mode':
					$product_id = 1;
					break;
				case 'table_sync':
					$product_id = 2;
					break;
				case 'easy_reviews':
					$product_id = 3;
					break;
				case 'jitsi_meet':
					$product_id = 4;
					break;
				default:
					$product_id = 1;
					break;
			}
		}
		if(!empty($contacts_arr['customers']['data'])){
			$customer_id = $contacts_arr['customers']['data'][0]['id'];
			$fluent_user_id = $contacts_arr['customers']['user_id'];
			$ticket_data = [
				"ticket" => [
					"create_wp_user" => "no", 
					"create_customer" => "no", 
					"mailbox_id" => 1, 
					"title" => $ticket_subject, 
					"content" => $ticket_description, 
					"customer_id" => $customer_id,
					"product_id" => $product_id, 
					"client_priority" => $ticket_priority,
				]
			 ];
		}elseif (empty($contacts_arr['customers']['data'])) {
			$ticket_data = [
				"ticket" => [
					"create_wp_user" => "yes", 
					"create_customer" => "yes", 
					"mailbox_id" => 1, 
					"title" => $ticket_subject, 
					"content" => $ticket_description, 
					"product_id" => $product_id,
					"client_priority" => $ticket_priority,
				], 
				"newCustomer" => [
					"first_name" => $current_user->user_firstname, 
					"last_name" => $current_user->user_lastname, 
					"email" => $email, 
					"username" => $current_user->user_login, 
					"password" => wp_generate_password()
				] 
			 ]; 
		}

        $url = "https://fluent.wppool.dev/wp-json/fluent-support/v2/tickets";
		$response = wp_remote_post( $url, array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->user_name . ':' . $this->user_pass )
			),
			'body' => $ticket_data
		) );

    }

	public function my_custom_tab_content( $tab ) {

		if( $tab != "ticket" ) {
			return;
		}
		?>
		<style>
			.entry-content {
				max-width: 1200px;
			}
		</style>
		<?php
		// $success_message = 'none';
		// echo do_shortcode('[fluent_support_portal]');
		
		if ($_GET['tab'] == 'ticket' && !isset($_GET['action']) && !isset($_GET['ticket_id'])) {
			global $wp;
			$current_url = home_url(add_query_arg(array(), $wp->request));
			$url = 'https://fluent.wppool.dev/wp-json/fluent-support/v2/tickets';
			$args = array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $this->user_name . ':' . $this->user_pass )
				)
			);
			$response = wp_remote_request( $url, $args );
			$ticket_array = json_decode(wp_remote_retrieve_body($response), true);
			?>
			<div id="fluent_support_client_app" data-v-app="">
				<div class="fst_client_portal">
					<div class="fst_tickets">
						<div class="fs_all_tickets">
							<div class="fs_tk_actions fs_tk_header">
								<div class="fs_tk_left">
									<div class="fs_button_groups">
										<button class="fs_btn fs_btn_active"><?php esc_html_e('All', 'fluent-integration') ?></button>
										<button class="fs_btn" id="open_status"><?php esc_html_e('Open', 'fluent-integration') ?></button>
										<button class="fs_btn" id="open_closed"><?php esc_html_e('Closed', 'fluent-integration') ?></button>
									</div>
								</div>
								<div class="fs_tk_right">
									<a href="<?php echo esc_url($current_url) ?>/?tab=ticket&action=create">
										<button class="fs_btn fs_btn_success"><?php esc_html_e('Create a New Ticket', 'fluent-integration') ?></button>
									</a>
									</div>
							</div>
							<div class="fs_tk_body">
							<div class="overlay"></div>
								<table class="fs_table fs_stripe">
									<thead>
										<tr>
											<th><?php esc_html_e('Conversation', 'fluent-integration') ?></th>
											<th></th>
											<th><?php esc_html_e('Status', 'fluent-integration') ?></th>
											<th><?php esc_html_e('Date', 'fluent-integration') ?></th>
										</tr>
									</thead>
									
									
									<tbody id="ticket_list">
										<?php
											foreach ($ticket_array['tickets']['data'] as $key => $value) {
												?>
										<tr>
											<td><a href="<?php echo esc_url($current_url) ?>/?tab=ticket&ticket_id=<?php echo esc_html($value['id']); ?>" class="fs_tk_preview"><strong><?php echo esc_html($value['title'], 'fluent-integration') ?></strong>
													<div class="prev_text_parent">
													</div>
												</a></td>
											<td class="fs_thread_count"><span class="fs_thread_count"><?php echo esc_html($value['response_count'], 'fluent-integration') ?></span></td>
											<td class="fs_tk_status"><span
													class="el-tag el-tag--success el-tag--mini el-tag--dark"><?php echo esc_html($value['status'], 'fluent-integration') ?>
													<!--v-if--></span></td>
											<td class="fs_tk_date"><span class="fs_tk_date"><?php echo esc_html($value['created_at'], 'fluent-integration'); ?></span></td>
										</tr>

										<?php
											} ?>
									</tbody>
								</table>
								<div class="fst_pagi_wrapper">
									<!---->
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php
		}else if(isset($_GET['action']) && $_GET['action'] == 'create'){
			echo do_shortcode('[fluentform id="4"]');
		}else if(!empty($_GET['ticket_id'])){
			$ticket_id = $_GET['ticket_id'];
			$url = 'https://fluent.wppool.dev/wp-json/fluent-support/v2/tickets/'.$ticket_id.'';
			$args = array(
				'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->user_name . ':' . $this->user_pass )
				)
			);
			$response = wp_remote_request( $url, $args );
			$ticket_array = json_decode(wp_remote_retrieve_body($response), true);

			?>
		<div id="fluent_support_client_app" data-v-app="">
			<div class="fst_client_portal">
				<div class="fs_ticket" >
					<div class="fs_tk_header" >
						<div class="fs_th_header">
							<hgroup >
								<div class="fs_tk_subject" >
									<h2 title="fs_tk_edit_subject" ><span class="fs_ticket_id" >#<?php echo esc_html($ticket_array['ticket']['id'], 'fluent-integration') ?></span> <?php echo $ticket_array['ticket']['title'] ?></h2>
									<div class="fs_tk_tags" >
										<span class="fs_badge fs_badge_active" ><?php echo esc_html($ticket_array['ticket']['status'], 'fluent-integration') ?></span>
									</div>
								</div>
								<div class="fs_tk_actions" >
								<a class="el-button el-button--default el-button--small" href="<?php echo esc_url(site_url(), 'fluent-integration'); ?>/my-account/?tab=ticket" data-v-76a56c0e=""><?php esc_html_e('All', 'fluent-integration') ?></a>
								</div>
							</hgroup>
						</div>
					</div>
					<div class="fs_tk_body" >
						<div class="fst_reply_box" >
							<form action="" method="POST">
								<textarea name="fs_reply_text_con" class="fs_reply_text" placeholder="<?php echo esc_url('Click Here to Write a reply', 'fluent-integration') ?>"></textarea>
								<button type="submit" class="fs_btn fs_btn_success"><?php esc_html_e('Reply', 'fluent-integration') ?></button>
							</form>
							<?php
								if(!empty($_POST) && array_key_exists('fs_reply_text_con', $_POST)){
									$user_id = $ticket_array['ticket']['customer']['user_id'];
									$email = $ticket_array['ticket']['customer']['email'];
									$user_details = array(
										'user_id' => $user_id,
										'email' => $email
									);
									$args = array(
										'headers' => array(
										'Content-Type'   => 'application/json',
										),
										'body'      => json_encode($user_details),
									);
									$get_app_password = wp_remote_post('https://fluent.wppool.dev/wp-json/fluent/v1/wppool', $args);
									$app_pass = json_decode(wp_remote_retrieve_body($get_app_password), true);

									$reply_content = "content=".$_POST["fs_reply_text_con"].".&conversation_type=response";
									$url = 'https://fluent.wppool.dev/wp-json/fluent-support/v2/customer-portal/tickets/'.$ticket_id.'/responses?'.$reply_content.'';
									$args = array(
										'headers' => array(
											'Authorization' => 'Basic ' . base64_encode( $email . ':' . $app_pass )
										)
									);
									$response = wp_remote_post( $url, $args );
								}

							?>
						</div>
						<div class="fs_threads_container" >
							<?php
							$args = array(
								'headers' => array(
								'Authorization' => 'Basic ' . base64_encode( $this->user_name . ':' . $this->user_pass )
								)
							);
							$response = wp_remote_request( 'https://fluent.wppool.dev/wp-json/fluent-support/v2/tickets/'.$ticket_id.'', $args );
							$ticket_array = json_decode(wp_remote_retrieve_body($response), true);
							if($ticket_array['ticket']['agent']['user_id']){
								$user_details = array(
									'user_id' => $ticket_array['ticket']['agent']['user_id']
								);
								$args = array(
									'headers' => array(
									'Content-Type'   => 'application/json',
									),
									'body'      => json_encode($user_details),
								);
								$gravatar_response = wp_remote_request( 'https://fluent.wppool.dev/wp-json/fluent/v1/user_gravatar', $args );
								$staff_gravatar = json_decode(wp_remote_retrieve_body($gravatar_response), true);
							}
							foreach ($ticket_array['responses'] as $key => $value) {
								$person_type = $value['person']['person_type'];
								if($person_type == 'agent'){ ?>
									<article class="fs_thread fs_thread fs_person_agent fs_conv_type_response" >
										<div class="fs_thread_content" >
											<section class="fs_avatar" >
												<img src="<?php echo esc_url($staff_gravatar, 'fluent-integration'); ?>" alt="<?php echo $value['person']['first_name'] ?>" ></section>
											<section class="fs_thread_wrap" >
												<section class="fs_thread_message" >
													<div class="fs_thread_head" >
														<div class="fs_thread_title" ><strong
																><?php esc_html_e($value['person']['first_name'], 'fluent-integration') ?></strong> <?php esc_html_e('replied', 'fluent-integration') ?></div>
														<div class="fs_thread_actions" >
															<?php echo esc_html($value['updated_at'], 'fluent-integration'); ?></div>
													</div>
													<div class="fs_thread_body" ><?php echo esc_html($value['content'], 'fluent-integration'); ?>
													</div>
												</section>
											</section>
										</div>
									</article>
								<?php
								}else if($person_type == 'customer'){ ?>
							<article class="fs_thread fs_thread fs_person_customer fs_person_own fs_conv_type_response"
								>
								<!---->
								<div class="fs_thread_content" >
									<section class="fs_avatar" ><img
											src="<?php echo esc_url(get_avatar_url(get_current_user_id()), 'fluent-integration'); ?>"
											alt="<?php esc_attr_e('Jahed Alam', 'fluent-integration') ?>" ></section>
									<section class="fs_thread_wrap" >
										<section class="fs_thread_message" >
											<div class="fs_thread_head" >
												<div class="fs_thread_title" ><strong
														><?php esc_html_e('You', 'fluent-integration') ?></strong> <?php esc_html_e('replied', 'fluent-integration') ?></div>
												<div class="fs_thread_actions" >
													<?php echo esc_html($value['updated_at'], 'fluent-integration'); ?></div>
											</div>
											<div class="fs_thread_body" >
												<p><?php echo esc_html($value['content'], 'fluent-integration'); ?></p>
											</div>
											<!---->
										</section>
									</section>
								</div>
							</article>
							<?php
												}
											}
										
										?>
							<article class="fs_thread conversion_starter" >
								<div class="fs_thread_content" >
									<section class="fs_avatar" ><img
											src="<?php echo esc_url(get_avatar_url(get_current_user_id()), 'fluent-integration'); ?>"
											alt="<?php esc_attr_e($value['person']['first_name'], 'fluent-integration') ?>" ></section>
									<section class="fs_thread_wrap" >
										<section class="fs_thread_message" >
											<div class="fs_thread_head" >
												<div class="fs_thread_title" ><strong
														><?php esc_html_e('You', 'fluent-integration') ?></strong> <?php esc_html_e('started the conversation', 'fluent-integration') ?></div>
												<div class="fs_thread_actions" >
													<?php echo esc_html($ticket_array['ticket']['created_at'], 'fluent-integration') ?></div>
											</div>
											<div class="fs_thread_body" >
												<?php echo esc_html($ticket_array['ticket']['content'], 'fluent-integration') ?></div>
											<!---->
										</section>
									</section>
								</div>
								<!---->
							</article>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php }
	}

	public function add_menu_under_settings_page() {
        add_options_page(
            __('Appsero My Account Page To Fluent Support', 'fluent-support'),
            __('Appsero to Fluent Support', 'fluent-support'),
            'manage_options',
            'fluent-support-settings',
            array( $this, 'fluent_support_settings_field' )
        );
    }
	public function fluent_support_settings_field(){
		echo '<div class="wrap"><form method="post" action="options.php">';	
		settings_fields( 'fluent-support-settings' ); // settings group name
		do_settings_sections( 'fluent-support-settings' ); // just a page slug
		submit_button();
		echo '</form></div>';
	}

	public function add_settings(){
		add_settings_section(
			'fluent_support_settings_section',
			'Fluent Support Settings',
			array( $this,'settings_section' ),
			'fluent-support-settings'
		);
	
		// Register a callback
		register_setting(
			'fluent-support-settings',
			'fluent_support_wp_username',
		);
		// Register a callback
		register_setting(
			'fluent-support-settings',
			'fluent_support_app_pass',
		);

		add_settings_field(
			'fluent_support_wp_username',
			'Fluent admin username',
			array($this, 'fluent_support_wp_username_field'),
			'fluent-support-settings',
			'fluent_support_settings_section',
			array ( 'label_for' => 'fluent_support_wp_username_label' )
		);
		add_settings_field(
			'fluent_support_app_pass',
			'Admin application password',
			array($this, 'fluent_support_app_pass'),
			'fluent-support-settings',
			'fluent_support_settings_section',
			array ( 'label_for' => 'fluent_support_app_pass' )
		);
	}
	public function settings_section(){ ?>
<?php }

	public function fluent_support_wp_username_field($args){
		$data = esc_attr( get_option( 'fluent_support_wp_username', '' ) );
		printf( '<input type="text" name="fluent_support_wp_username" value="%1$s" id="%2$s" />', $data,$args['label_for'] );
	}
	public function fluent_support_app_pass($args){
		$data = esc_attr( get_option( 'fluent_support_app_pass', '' ) );
		printf( '<input type="password" name="fluent_support_app_pass" value="%1$s" id="%2$s" /><input type="checkbox" onclick="myFunction()">Show Password', $data,$args['label_for'] );
	}

}