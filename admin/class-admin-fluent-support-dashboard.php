<?php
class Fluent_Support_Dashboard_Admin {

	/**
	 * all methods of this class instataite automatically
	 */
    public function __construct() {
        add_action("admin_menu", array($this, "add_menu_under_settings_page"));
        add_action('after_appsero_myaccount_sidebar', function ($tab) {
            echo '<li><a href="?tab=ticket" class="'. ($tab == 'ticket' ? 'ama-active-tab' : '') . '">Support via Email</a></li>';
        });
        add_action( 'admin_init', array($this, 'add_settings' ));
		add_action( 'appsero_myaccount_custom_tab', array($this, 'my_custom_tab_content'));
		add_action('fluentform_submission_inserted', array($this, 'after_ticket_submit'), 20, 3);
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
				'Authorization' => 'Basic ' . base64_encode( 'saiful@wppool.dev' . ':' . 'kJqM L5Vh qDtv QpYH 1Tjw JmYI' )
			)
		) );

		$contacts_arr = json_decode(wp_remote_retrieve_body($contact_response), true);
		if(!empty($formData['support_product'])){
			switch ($formData['support_product']) {
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
					"title" => $formData['ticket_subject'], 
					"content" => $formData['ticket_description'], 
					"customer_id" => $customer_id,
					"product_id" => $product_id, 
					"client_priority" => $formData['ticket_priority'], 
				]
			 ];
		}elseif (empty($contacts_arr['customers']['data'])) {
			$ticket_data = [
				"ticket" => [
					"create_wp_user" => "yes", 
					"create_customer" => "yes", 
					"mailbox_id" => 1, 
					"title" => $formData['ticket_subject'], 
					"content" => $formData['ticket_description'], 
					"product_id" => $product_id, 
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
				'Authorization' => 'Basic ' . base64_encode( 'saiful@wppool.dev' . ':' . 'kJqM L5Vh qDtv QpYH 1Tjw JmYI' )
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
					'Authorization' => 'Basic ' . base64_encode( 'saiful@wppool.dev' . ':' . 'kJqM L5Vh qDtv QpYH 1Tjw JmYI' )
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
										<button class="fs_btn fs_btn_active">All</button>
										<button class="fs_btn" id="open_status">Open</button>
										<button class="fs_btn" id="open_closed">Closed</button>
									</div>
								</div>
								<div class="fs_tk_right"><a href="<?php echo $current_url ?>/?tab=ticket&action=create"><button
											class="fs_btn fs_btn_success">Create a New Ticket</button></a></div>
							</div>
							<div class="fs_tk_body">
							<div class="overlay"></div>
								<table class="fs_table fs_stripe">
									<thead>
										<tr>
											<th>Conversation</th>
											<th></th>
											<th>Status</th>
											<th>Date</th>
										</tr>
									</thead>
									
									
									<tbody id="ticket_list">
										<?php
											foreach ($ticket_array['tickets']['data'] as $key => $value) {
												?>
										<tr>
											<td><a href="<?php echo $current_url ?>/?tab=ticket&ticket_id=<?php echo $value['id']; ?>"
													class="fs_tk_preview"><strong><?php echo $value['title'] ?></strong>
													<div class="prev_text_parent">
														
													</div>
												</a></td>
											<td class="fs_thread_count"><span class="fs_thread_count"><?php echo $value['response_count'] ?></span></td>
											<td class="fs_tk_status"><span
													class="el-tag el-tag--success el-tag--mini el-tag--dark"><?php echo $value['status'] ?>
													<!--v-if--></span></td>
											<td class="fs_tk_date"><span class="fs_tk_date"><?php echo $value['created_at']; ?></span></td>
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
				'Authorization' => 'Basic ' . base64_encode( 'saiful@wppool.dev' . ':' . 'kJqM L5Vh qDtv QpYH 1Tjw JmYI' )
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
									<h2 title="fs_tk_edit_subject" ><span class="fs_ticket_id" >#<?php echo $ticket_array['ticket']['id'] ?></span> <?php echo $ticket_array['ticket']['title'] ?></h2>
									<div class="fs_tk_tags" >
										<span class="fs_badge fs_badge_active" ><?php echo $ticket_array['ticket']['status'] ?></span>
									</div>
								</div>
								<div class="fs_tk_actions" >
								<a class="el-button el-button--default el-button--small" href="<?php echo site_url(); ?>/my-account/?tab=ticket" data-v-76a56c0e="">All</a>
								</div>
							</hgroup>
						</div>
					</div>
					<div class="fs_tk_body" >
						<div class="fst_reply_box" >
							<form action="" method="POST">
								<textarea name="fs_reply_text_con" class="fs_reply_text" placeholder="Click Here to Write a reply"></textarea>
								<button type="submit" class="fs_btn fs_btn_success">Reply</button>
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
								'Authorization' => 'Basic ' . base64_encode( 'saiful@wppool.dev' . ':' . 'kJqM L5Vh qDtv QpYH 1Tjw JmYI' )
								)
							);
							$response = wp_remote_request( 'https://fluent.wppool.dev/wp-json/fluent-support/v2/tickets/'.$ticket_id.'', $args );
							$ticket_array = json_decode(wp_remote_retrieve_body($response), true);

							foreach ($ticket_array['responses'] as $key => $value) {
								$person_type = $value['person']['person_type'];
								if($person_type == 'agent'){ ?>
									<article class="fs_thread fs_thread fs_person_agent fs_conv_type_response" >
										<div class="fs_thread_content" >
											<section class="fs_avatar" >
												<img src="https://www.gravatar.com/avatar/499126da17e787211e0f16d379e4efdb?s=128" alt="<?php echo $value['person']['first_name'] ?>" ></section>
											<section class="fs_thread_wrap" >
												<section class="fs_thread_message" >
													<div class="fs_thread_head" >
														<div class="fs_thread_title" ><strong
																><?php echo $value['person']['first_name'] ?></strong> replied</div>
														<div class="fs_thread_actions" >
															<?php echo $value['updated_at']; ?></div>
													</div>
													<div class="fs_thread_body" ><?php echo $value['content']; ?>
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
											src="https://www.gravatar.com/avatar/c84f175837468c7dcd2da63d80cb2bbc?s=128"
											alt="Jahed Alam" ></section>
									<section class="fs_thread_wrap" >
										<section class="fs_thread_message" >
											<div class="fs_thread_head" >
												<div class="fs_thread_title" ><strong
														>You</strong> replied</div>
												<div class="fs_thread_actions" >
													<?php echo $value['updated_at']; ?></div>
											</div>
											<div class="fs_thread_body" >
												<p><?php echo $value['content']; ?></p>
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
											src="https://www.gravatar.com/avatar/c84f175837468c7dcd2da63d80cb2bbc?s=128"
											alt="Jahed Alam" ></section>
									<section class="fs_thread_wrap" >
										<section class="fs_thread_message" >
											<div class="fs_thread_head" >
												<div class="fs_thread_title" ><strong
														>You</strong> started the conversation</div>
												<div class="fs_thread_actions" >
													<?php echo $ticket_array['ticket']['created_at'] ?></div>
											</div>
											<div class="fs_thread_body" >
												<?php echo $ticket_array['ticket']['content'] ?></div>
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
            __('Appsero My Account Page To Freshdesk', 'fluent-support'),
            __('Appsero to Freshdesk', 'fluent-support'),
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
			array ( 'label_for' => 'slack_label' )
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
		printf( '<input type="text" name="fluent_support_app_pass" value="%1$s" id="%2$s" />', $data,$args['label_for'] );
	}

}