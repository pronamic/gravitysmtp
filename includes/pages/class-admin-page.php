<?php

namespace Gravity_Forms\Gravity_SMTP\Pages;

use Gravity_Forms\Gravity_SMTP\Users\Roles;

class Admin_Page {

	const ICON_DATA_URI = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgdmlld0JveD0iMCAwIDEwMDAgMTAwMCI+PHBhdGggZmlsbD0iI2E3YWFhZCIgZD0iTTkwNS4yOCA2OTMuOTYgNjE5Ljg4IDEzNC40Yy03LjMtMTIuMzMtMjUuMjctMjIuMzktMzkuOC0yMi4zOUg0MTkuOWMtMTQuNTMgMC0zMi40MyAxMC4xMi0zOS44NyAyMi4zOUw5NC41NiA2OTMuOTZjLTcuMzcgMTIuMzMtMy4zIDI4LjY1LS4yOCA0NC45OGwyMy45NCAxMjUuNjNjMy4zIDE0LjYgMjQuOTkgMjIuNTkgMzkuNTkgMjIuNTlsMzQyLjU0LjgyIDM0MS43Ny0uODJjMTQuNiAwIDM2LjUtNy45OSAzOS41OS0yMi41OWwyMy45NC0xMjUuNjNjMy4zLTE0Ljg4IDcuMDktMzIuNjUtLjI4LTQ0Ljk4aC0uMDdabS0xNzguNSA1MS4zOC0xMzQuMzUtNDAuMjljLTE5LjE2LTYuMjctMzIuMjItMTAuNTQtMzguODItMjMuNDhsLTM4LjgyLTEwNy41OWMtMy4zLTYuNDEtNy41OC05LjQ0LTExLjkzLTkuNDRoLS4zNWMtLjA3IDAtLjI4LS4wNy0uNDIgMC00LjI4LjA3LTguNTcgMy4xLTExLjg2IDkuNWwtMzguODIgMTA3LjU5Yy02LjYgMTIuOTUtMTkuNTEgMTYuNjctMzguODIgMjMuNDhMMjc4LjE3IDc0NS40Yy0xNC43NCAwLTIwLjcxLTEwLjE5LTEzLjM0LTIyLjczbDIxOC4zLTQzNi43NWM4LjU2LTE3Ljg0IDI4LjQzLTE5LjQ5IDM4LjgyIDBsMjE4LjMgNDM2Ljc1YzcuMzcgMTIuNDcgMS40IDIyLjczLTEzLjM0IDIyLjczbC0uMTQtLjA3WiIgc3R5bGU9InN0cm9rZS13aWR0aDowIi8+PC9zdmc+';

	public function admin_pages() {
		add_menu_page( 'Gravity SMTP', __( 'SMTP', 'gravitysmtp' ), Roles::VIEW_EMAIL_LOG, 'gravitysmtp-activity-log', [ $this, 'app_page' ], self::ICON_DATA_URI, 81 );
		// add_submenu_page( 'gravitysmtp-dashboard', __( 'Dashboard', 'gravitysmtp' ), __( 'Dashboard', 'gravitysmtp' ), 'manage_options', 'gravitysmtp-dashboard', [ $this, 'app_page' ] );
		add_submenu_page( 'gravitysmtp-activity-log', __( 'Email Log', 'gravitysmtp' ), __( 'Email Log', 'gravitysmtp' ), Roles::VIEW_EMAIL_LOG, 'gravitysmtp-activity-log', [ $this, 'app_page' ] );
		add_submenu_page( 'gravitysmtp-activity-log', __( 'Settings', 'gravitysmtp' ), __( 'Settings', 'gravitysmtp' ), Roles::VIEW_GENERAL_SETTINGS, 'gravitysmtp-settings', [ $this, 'app_page' ] );
		add_submenu_page( 'gravitysmtp-activity-log', __( 'Tools', 'gravitysmtp' ), __( 'Tools', 'gravitysmtp' ), Roles::VIEW_TOOLS, 'gravitysmtp-tools', [ $this, 'app_page' ] );
		// add_submenu_page( 'gravitysmtp-email-log', __( 'Help', 'gravitysmtp' ), __( 'Help', 'gravitysmtp' ), 'manage_options', 'gravitysmtp-help', [ $this, 'app_page' ] );
	}

	public function app_page() {
		?>
		<div class="gravitysmtp-admin">
			<?php do_action( 'gravitysmtp_app_body' ); ?>
		</div>
		<?php
	}

	public function dashboard_page() {
		?>
		<div class="gravitysmtp-admin">
			<div class="gravitysmtp-app" data-js="gravitysmtp-app-root"></div>
		</div>
		<?php
	}

	public function email_log_page() {
		?>
		<div class="gravitysmtp-admin">
			<div class="gravitysmtp-app" data-js="gravitysmtp-app-root"></div>
		</div>
		<?php
	}

	public function settings_page() {
		?>
		<div class="gravitysmtp-admin">
			<div class="gravitysmtp-app" data-js="gravitysmtp-settings-app-root"></div>
		</div>
		<?php
	}

	public function tools_page() {
		?>
		<div class="gravitysmtp-admin">
			<div class="gravitysmtp-app" data-js="gravitysmtp-tools-app-root"></div>
		</div>
		<?php
	}

	public function help_page() {
		?>
		<div class="gravitysmtp-admin">
			<div class="gravitysmtp-app" data-js="gravitysmtp-app-root"></div>
		</div>
		<?php
	}
}
