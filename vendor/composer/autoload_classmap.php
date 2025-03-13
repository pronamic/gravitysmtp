<?php

// autoload_classmap.php @generated by Composer

$vendorDir = dirname(__DIR__);
$baseDir = dirname($vendorDir);

return array(
    'Composer\\InstalledVersions' => $vendorDir . '/composer/InstalledVersions.php',
    'Gravity_Forms\\Gravity_SMTP\\Alerts\\Alerts_Handler' => $baseDir . '/includes/alerts/class-alerts-handler.php',
    'Gravity_Forms\\Gravity_SMTP\\Alerts\\Alerts_Service_Provider' => $baseDir . '/includes/alerts/class-alerts-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Alerts\\Config\\Alerts_Config' => $baseDir . '/includes/alerts/config/class-alerts-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Alerts\\Config\\Alerts_Endpoints_Config' => $baseDir . '/includes/alerts/config/class-alerts-endpoints-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Alerts\\Connectors\\Alert_Connector' => $baseDir . '/includes/alerts/connectors/interface-alert-connector.php',
    'Gravity_Forms\\Gravity_SMTP\\Alerts\\Connectors\\Slack_Alert_Connector' => $baseDir . '/includes/alerts/connectors/class-slack-alert-connector.php',
    'Gravity_Forms\\Gravity_SMTP\\Alerts\\Connectors\\Twilio_Alert_Connector' => $baseDir . '/includes/alerts/connectors/class-twilio-alert-connector.php',
    'Gravity_Forms\\Gravity_SMTP\\Alerts\\Endpoints\\Save_Alerts_Settings_Endpoint' => $baseDir . '/includes/alerts/endpoints/class-save-alerts-settings-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Alerts\\Endpoints\\Send_Test_Alert_Endpoint' => $baseDir . '/includes/alerts/endpoints/class-send-test-alert-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Apps\\App_Service_Provider' => $baseDir . '/includes/apps/class-apps-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Apps\\Config\\Apps_Config' => $baseDir . '/includes/apps/config/class-apps-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Apps\\Config\\Dashboard_Config' => $baseDir . '/includes/apps/config/class-dashboard-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Apps\\Config\\Email_Log_Config' => $baseDir . '/includes/apps/config/class-email-log-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Apps\\Config\\Email_Log_Single_Config' => $baseDir . '/includes/apps/config/class-email-log-single-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Apps\\Config\\Settings_Config' => $baseDir . '/includes/apps/config/class-settings-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Apps\\Config\\Tools_Config' => $baseDir . '/includes/apps/config/class-tools-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Apps\\Endpoints\\Get_Dashboard_Data_Endpoint' => $baseDir . '/includes/apps/endpoints/class-get-dashboard-data-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Apps\\Migration\\Endpoints\\Migrate_Settings_Endpoint' => $baseDir . '/includes/migration/endpoints/class-migrate-settings-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Apps\\Setup_Wizard\\Config\\Setup_Wizard_Config' => $baseDir . '/includes/apps/setup-wizard/config/class-setup-wizard-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Apps\\Setup_Wizard\\Config\\Setup_Wizard_Endpoints_Config' => $baseDir . '/includes/apps/setup-wizard/config/class-setup-wizard-endpoints-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Apps\\Setup_Wizard\\Endpoints\\License_Check_Endpoint' => $baseDir . '/includes/apps/setup-wizard/endpoints/class-license-check-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Apps\\Setup_Wizard\\Setup_Wizard_Service_Provider' => $baseDir . '/includes/apps/setup-wizard/class-setup-wizard-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Assets\\Assets_Service_Provider' => $baseDir . '/includes/assets/class-assets-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Config\\Connector_Config' => $baseDir . '/includes/connectors/config/class-connector-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Config\\Connector_Endpoints_Config' => $baseDir . '/includes/connectors/config/class-connector-endpoints-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Connector_Base' => $baseDir . '/includes/connectors/class-connector-base.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Connector_Factory' => $baseDir . '/includes/connectors/class-connector-factory.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Connector_Service_Provider' => $baseDir . '/includes/connectors/class-connector-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Endpoints\\Check_Background_Tasks_Endpoint' => $baseDir . '/includes/connectors/endpoints/class-check-background-tasks-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Endpoints\\Cleanup_Data_Endpoint' => $baseDir . '/includes/connectors/endpoints/class-cleanup-data-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Endpoints\\Get_Connector_Emails' => $baseDir . '/includes/connectors/endpoints/class-get-connector-emails-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Endpoints\\Get_Single_Email_Data_Endpoint' => $baseDir . '/includes/connectors/endpoints/class-get-single-email-data-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Endpoints\\Save_Connector_Settings_Endpoint' => $baseDir . '/includes/connectors/endpoints/class-save-connector-settings-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Endpoints\\Save_Plugin_Settings_Endpoint' => $baseDir . '/includes/connectors/endpoints/class-save-plugin-settings-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Endpoints\\Send_Test_Endpoint' => $baseDir . '/includes/connectors/endpoints/class-send-test-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Oauth\\Google_Oauth_Handler' => $baseDir . '/includes/connectors/oauth/class-google-oauth-handler.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Oauth\\Microsoft_Oauth_Handler' => $baseDir . '/includes/connectors/oauth/class-microsoft-oauth-handler.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Oauth\\Zoho_Oauth_Handler' => $baseDir . '/includes/connectors/oauth/class-zoho-oauth-handler.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Oauth_Data_Handler' => $baseDir . '/includes/connectors/class-oauth-data-handler.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Types\\Connector_Amazon' => $baseDir . '/includes/connectors/types/class-connector-amazon.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Types\\Connector_Brevo' => $baseDir . '/includes/connectors/types/class-connector-brevo.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Types\\Connector_Elastic_Email' => $baseDir . '/includes/connectors/types/class-connector-elasticemail.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Types\\Connector_Generic' => $baseDir . '/includes/connectors/types/class-connector-generic.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Types\\Connector_Google' => $baseDir . '/includes/connectors/types/class-connector-google.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Types\\Connector_Mailchimp' => $baseDir . '/includes/connectors/types/class-connector-mailchimp.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Types\\Connector_MailerSend' => $baseDir . '/includes/connectors/types/class-connector-mailersend.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Types\\Connector_Mailgun' => $baseDir . '/includes/connectors/types/class-connector-mailgun.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Types\\Connector_Mailjet' => $baseDir . '/includes/connectors/types/class-connector-mailjet.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Types\\Connector_Microsoft' => $baseDir . '/includes/connectors/types/class-connector-microsoft.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Types\\Connector_Phpmail' => $baseDir . '/includes/connectors/types/class-connector-phpmail.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Types\\Connector_Postmark' => $baseDir . '/includes/connectors/types/class-connector-postmark.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Types\\Connector_Sendgrid' => $baseDir . '/includes/connectors/types/class-connector-sendgrid.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Types\\Connector_Smtp2go' => $baseDir . '/includes/connectors/types/class-connector-smtp2go.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Types\\Connector_Sparkpost' => $baseDir . '/includes/connectors/types/class-connector-sparkpost.php',
    'Gravity_Forms\\Gravity_SMTP\\Connectors\\Types\\Connector_Zoho' => $baseDir . '/includes/connectors/types/class-connector-zoho.php',
    'Gravity_Forms\\Gravity_SMTP\\Data_Store\\Const_Data_Store' => $baseDir . '/includes/datastore/class-const-data-store.php',
    'Gravity_Forms\\Gravity_SMTP\\Data_Store\\Data_Store' => $baseDir . '/includes/datastore/interface-data-store.php',
    'Gravity_Forms\\Gravity_SMTP\\Data_Store\\Data_Store_Router' => $baseDir . '/includes/datastore/class-data-store-router.php',
    'Gravity_Forms\\Gravity_SMTP\\Data_Store\\Opts_Data_Store' => $baseDir . '/includes/datastore/class-opts-data-store.php',
    'Gravity_Forms\\Gravity_SMTP\\Data_Store\\Plugin_Opts_Data_Store' => $baseDir . '/includes/datastore/class-plugin-opts-data-store.php',
    'Gravity_Forms\\Gravity_SMTP\\Email_Management\\Config\\Managed_Email_Types_Config' => $baseDir . '/includes/email-management/config/class-managed-email-types-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Email_Management\\Email_Management_Service_Provider' => $baseDir . '/includes/email-management/class-email-management-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Email_Management\\Email_Stopper' => $baseDir . '/includes/email-management/class-email-stopper.php',
    'Gravity_Forms\\Gravity_SMTP\\Email_Management\\Managed_Email' => $baseDir . '/includes/email-management/class-managed-email.php',
    'Gravity_Forms\\Gravity_SMTP\\Enums\\Connector_Status_Enum' => $baseDir . '/includes/enums/class-connector-status-enum.php',
    'Gravity_Forms\\Gravity_SMTP\\Enums\\Integration_Enum' => $baseDir . '/includes/enums/class-integration-enum.php',
    'Gravity_Forms\\Gravity_SMTP\\Enums\\Status_Enum' => $baseDir . '/includes/enums/class-status-enum.php',
    'Gravity_Forms\\Gravity_SMTP\\Enums\\Suppression_Reason_Enum' => $baseDir . '/includes/enums/class-suppression-reason-enum.php',
    'Gravity_Forms\\Gravity_SMTP\\Enums\\Zoho_Datacenters_Enum' => $baseDir . '/includes/enums/class-zoho-datacenters-enum.php',
    'Gravity_Forms\\Gravity_SMTP\\Environment\\Config\\Environment_Endpoints_Config' => $baseDir . '/includes/environment/config/class-environment-endpoints-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Environment\\Endpoints\\Uninstall_Endpoint' => $baseDir . '/includes/environment/endpoints/class-uninstall-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Environment\\Environment_Details' => $baseDir . '/includes/environment/class-environment-details.php',
    'Gravity_Forms\\Gravity_SMTP\\Environment\\Environment_Service_Provider' => $baseDir . '/includes/environment/class-environment-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Errors\\Error_Handler' => $baseDir . '/includes/errors/class-error-handler.php',
    'Gravity_Forms\\Gravity_SMTP\\Errors\\Error_Handler_Service_Provider' => $baseDir . '/includes/errors/class-error-handler-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Experimental_Features\\Experiment_Features_Handler' => $baseDir . '/includes/experimental-features/class-experimental-features-handler.php',
    'Gravity_Forms\\Gravity_SMTP\\Experimental_Features\\Experimental_Features_Service_Provider' => $baseDir . '/includes/experimental-features/class-experimental-features-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Feature_Flags\\Config\\Feature_Flags_Config' => $baseDir . '/includes/feature-flags/config/class-feature-flags-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Feature_Flags\\Feature_Flag_Manager' => $baseDir . '/includes/feature-flags/class-feature-flag-manager.php',
    'Gravity_Forms\\Gravity_SMTP\\Feature_Flags\\Feature_Flag_Repository' => $baseDir . '/includes/feature-flags/class-feature-flag-repository.php',
    'Gravity_Forms\\Gravity_SMTP\\Feature_Flags\\Feature_Flags_Service_Provider' => $baseDir . '/includes/feature-flags/class-feature-flags-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Gravity_SMTP' => $baseDir . '/includes/class-gravity-smtp.php',
    'Gravity_Forms\\Gravity_SMTP\\Handler\\Config\\Handler_Endpoints_Config' => $baseDir . '/includes/handler/config/class-handler-endpoints-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Handler\\Endpoints\\Resend_Email_Endpoint' => $baseDir . '/includes/handler/endpoints/class-resend-email-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Handler\\External\\Gravity_Forms_Note_Handler' => $baseDir . '/includes/handler/external/class-gravity-forms-note-handler.php',
    'Gravity_Forms\\Gravity_SMTP\\Handler\\Handler_Service_Provider' => $baseDir . '/includes/handler/class-handler-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Handler\\Mail_Handler' => $baseDir . '/includes/handler/class-mail-handler.php',
    'Gravity_Forms\\Gravity_SMTP\\Logging\\Config\\Logging_Endpoints_Config' => $baseDir . '/includes/logging/config/class-logging-endpoints-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Logging\\Debug\\Debug_Log_Event_Handler' => $baseDir . '/includes/logging/debug/class-debug-log-event-handler.php',
    'Gravity_Forms\\Gravity_SMTP\\Logging\\Debug\\Debug_Logger' => $baseDir . '/includes/logging/debug/class-debug-logger.php',
    'Gravity_Forms\\Gravity_SMTP\\Logging\\Debug\\Null_Logger' => $baseDir . '/includes/logging/debug/class-null-logger.php',
    'Gravity_Forms\\Gravity_SMTP\\Logging\\Debug\\Null_Logging_Provider' => $baseDir . '/includes/logging/debug/class-null-logging-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Logging\\Endpoints\\Delete_Debug_Logs_Endpoint' => $baseDir . '/includes/logging/endpoints/class-delete-debug-logs-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Logging\\Endpoints\\Delete_Email_Endpoint' => $baseDir . '/includes/logging/endpoints/class-delete-email-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Logging\\Endpoints\\Delete_Events_Endpoint' => $baseDir . '/includes/logging/endpoints/class-delete-events-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Logging\\Endpoints\\Get_Email_Message_Endpoint' => $baseDir . '/includes/logging/endpoints/class-get-email-message-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Logging\\Endpoints\\Get_Paginated_Debug_Log_Items_Endpoint' => $baseDir . '/includes/logging/endpoints/class-get-paginated-debug-log-items-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Logging\\Endpoints\\Get_Paginated_Items_Endpoint' => $baseDir . '/includes/logging/endpoints/class-get-paginated-items-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Logging\\Endpoints\\Log_Item_Endpoint' => $baseDir . '/includes/logging/endpoints/class-log-item-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Logging\\Endpoints\\View_Log_Endpoint' => $baseDir . '/includes/logging/endpoints/class-view-log-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Logging\\Log\\Logger' => $baseDir . '/includes/logging/log/class-logger.php',
    'Gravity_Forms\\Gravity_SMTP\\Logging\\Log\\WP_Mail_Logger' => $baseDir . '/includes/logging/log/class-wp-mail-logger.php',
    'Gravity_Forms\\Gravity_SMTP\\Logging\\Logging_Service_Provider' => $baseDir . '/includes/logging/class-logging-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Logging\\Scheduling\\Handler' => $baseDir . '/includes/logging/scheduling/handler.php',
    'Gravity_Forms\\Gravity_SMTP\\Managed_Email_Types' => $baseDir . '/includes/email-management/class-managed-email-types.php',
    'Gravity_Forms\\Gravity_SMTP\\Migration\\Config\\Migration_Endpoints_Config' => $baseDir . '/includes/migration/config/class-migration-endpoints-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Migration\\Data\\Migration_Data_Gravityforms' => $baseDir . '/includes/migration/data/class-migration-data-gravityforms.php',
    'Gravity_Forms\\Gravity_SMTP\\Migration\\Data\\Migration_Data_Wpmailsmtp' => $baseDir . '/includes/migration/data/class-migration-data-wpmailsmtp.php',
    'Gravity_Forms\\Gravity_SMTP\\Migration\\Migration' => $baseDir . '/includes/migration/class-migration.php',
    'Gravity_Forms\\Gravity_SMTP\\Migration\\Migration_Service_Provider' => $baseDir . '/includes/migration/class-migration-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Migration\\Migrator' => $baseDir . '/includes/migration/class-migrator.php',
    'Gravity_Forms\\Gravity_SMTP\\Migration\\Migrator_Collection' => $baseDir . '/includes/migration/class-migrator-collection.php',
    'Gravity_Forms\\Gravity_SMTP\\Models\\Debug_Log_Model' => $baseDir . '/includes/models/class-debug-log-model.php',
    'Gravity_Forms\\Gravity_SMTP\\Models\\Event_Model' => $baseDir . '/includes/models/class-event-model.php',
    'Gravity_Forms\\Gravity_SMTP\\Models\\Hydrators\\Hydrator' => $baseDir . '/includes/models/hydrators/interface-hydrator.php',
    'Gravity_Forms\\Gravity_SMTP\\Models\\Hydrators\\Hydrator_Amazon' => $baseDir . '/includes/models/hydrators/class-hydrator-amazon.php',
    'Gravity_Forms\\Gravity_SMTP\\Models\\Hydrators\\Hydrator_Brevo' => $baseDir . '/includes/models/hydrators/class-hydrator-brevo.php',
    'Gravity_Forms\\Gravity_SMTP\\Models\\Hydrators\\Hydrator_Factory' => $baseDir . '/includes/models/hydrators/class-hydrator-factory.php',
    'Gravity_Forms\\Gravity_SMTP\\Models\\Hydrators\\Hydrator_Generic' => $baseDir . '/includes/models/hydrators/class-hydrator-generic.php',
    'Gravity_Forms\\Gravity_SMTP\\Models\\Hydrators\\Hydrator_Google' => $baseDir . '/includes/models/hydrators/class-hydrator-google.php',
    'Gravity_Forms\\Gravity_SMTP\\Models\\Hydrators\\Hydrator_Mailgun' => $baseDir . '/includes/models/hydrators/class-hydrator-mailgun.php',
    'Gravity_Forms\\Gravity_SMTP\\Models\\Hydrators\\Hydrator_Microsoft' => $baseDir . '/includes/models/hydrators/class-hydrator-microsoft.php',
    'Gravity_Forms\\Gravity_SMTP\\Models\\Hydrators\\Hydrator_Phpmail' => $baseDir . '/includes/models/hydrators/class-hydrator-phpmail.php',
    'Gravity_Forms\\Gravity_SMTP\\Models\\Hydrators\\Hydrator_Postmark' => $baseDir . '/includes/models/hydrators/class-hydrator-postmark.php',
    'Gravity_Forms\\Gravity_SMTP\\Models\\Hydrators\\Hydrator_Sendgrid' => $baseDir . '/includes/models/hydrators/class-hydrator-sendgrid.php',
    'Gravity_Forms\\Gravity_SMTP\\Models\\Hydrators\\Hydrator_WP_Mail' => $baseDir . '/includes/models/hydrators/class-hydrator-wp-mail.php',
    'Gravity_Forms\\Gravity_SMTP\\Models\\Log_Details_Model' => $baseDir . '/includes/models/class-log-details-model.php',
    'Gravity_Forms\\Gravity_SMTP\\Models\\Notifications_Model' => $baseDir . '/includes/models/class-notifications-model.php',
    'Gravity_Forms\\Gravity_SMTP\\Models\\Suppressed_Emails_Model' => $baseDir . '/includes/models/class-suppressed-emails-model.php',
    'Gravity_Forms\\Gravity_SMTP\\Models\\Traits\\Can_Compare_Dynamically' => $baseDir . '/includes/models/traits/trait-can-compare-dynamically.php',
    'Gravity_Forms\\Gravity_SMTP\\Pages\\Admin_Page' => $baseDir . '/includes/pages/class-admin-page.php',
    'Gravity_Forms\\Gravity_SMTP\\Pages\\Page_Service_Provider' => $baseDir . '/includes/pages/class-page-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Routing\\Handlers\\Primary_Backup_Handler' => $baseDir . '/includes/routing/handlers/class-primary-backup-handler.php',
    'Gravity_Forms\\Gravity_SMTP\\Routing\\Handlers\\Routing_Handler' => $baseDir . '/includes/routing/handlers/interface-routing-handler.php',
    'Gravity_Forms\\Gravity_SMTP\\Routing\\Routing_Service_Provider' => $baseDir . '/includes/routing/class-routing-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Suppression\\Config\\Suppression_Settings_Config' => $baseDir . '/includes/suppression/config/class-suppression-settings-config.php',
    'Gravity_Forms\\Gravity_SMTP\\Suppression\\Endpoints\\Add_Suppressed_Emails_Endpoint' => $baseDir . '/includes/suppression/endpoints/class-add-suppressed-emails-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Suppression\\Endpoints\\Get_Paginated_Items' => $baseDir . '/includes/suppression/endpoints/class-get-paginated-items.php',
    'Gravity_Forms\\Gravity_SMTP\\Suppression\\Endpoints\\Reactivate_Suppressed_Emails_Endpoint' => $baseDir . '/includes/suppression/endpoints/class-reactivate-suppressed-emails-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Suppression\\Suppression_Service_Provider' => $baseDir . '/includes/suppression/class-suppression-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Telemetry\\Telemetry_Background_Processor' => $baseDir . '/includes/telemetry/class-telemetry-background-processor.php',
    'Gravity_Forms\\Gravity_SMTP\\Telemetry\\Telemetry_Handler' => $baseDir . '/includes/telemetry/class-telemetry-handler.php',
    'Gravity_Forms\\Gravity_SMTP\\Telemetry\\Telemetry_Service_Provider' => $baseDir . '/includes/telemetry/class-telemetry-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Telemetry\\Telemetry_Snapshot_Data' => $baseDir . '/includes/telemetry/class-telemetry-snapshot-data.php',
    'Gravity_Forms\\Gravity_SMTP\\Tracking\\Open_Pixel_Handler' => $baseDir . '/includes/tracking/class-open-pixel-handler.php',
    'Gravity_Forms\\Gravity_SMTP\\Tracking\\Tracking_Service_Provider' => $baseDir . '/includes/tracking/class-tracking-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Translations\\TranslationsPress' => $baseDir . '/includes/translations/class-translationspress.php',
    'Gravity_Forms\\Gravity_SMTP\\Translations\\Translations_Service_Provider' => $baseDir . '/includes/translations/class-translations-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Users\\Roles' => $baseDir . '/includes/users/class-roles.php',
    'Gravity_Forms\\Gravity_SMTP\\Users\\Users_Service_Provider' => $baseDir . '/includes/users/class-users-service-provider.php',
    'Gravity_Forms\\Gravity_SMTP\\Utils\\AWS_Signature_Handler' => $baseDir . '/includes/utils/class-aws-signature-handler.php',
    'Gravity_Forms\\Gravity_SMTP\\Utils\\Attachments_Saver' => $baseDir . '/includes/utils/class-attachments-saver.php',
    'Gravity_Forms\\Gravity_SMTP\\Utils\\Basic_Encrypted_Hash' => $baseDir . '/includes/utils/class-basic-ecrypted-hash.php',
    'Gravity_Forms\\Gravity_SMTP\\Utils\\Booliesh' => $baseDir . '/includes/utils/class-booleish.php',
    'Gravity_Forms\\Gravity_SMTP\\Utils\\Fast_Endpoint' => $baseDir . '/includes/utils/class-fast-endpoint.php',
    'Gravity_Forms\\Gravity_SMTP\\Utils\\Header_Parser' => $baseDir . '/includes/utils/class-header-parser.php',
    'Gravity_Forms\\Gravity_SMTP\\Utils\\Import_Data_Checker' => $baseDir . '/includes/utils/class-import-data-checker.php',
    'Gravity_Forms\\Gravity_SMTP\\Utils\\Recipient' => $baseDir . '/includes/utils/class-recipient.php',
    'Gravity_Forms\\Gravity_SMTP\\Utils\\Recipient_Collection' => $baseDir . '/includes/utils/class-recipient-collection.php',
    'Gravity_Forms\\Gravity_SMTP\\Utils\\Recipient_Parser' => $baseDir . '/includes/utils/class-recipient-parser.php',
    'Gravity_Forms\\Gravity_SMTP\\Utils\\SQL_Filter_Parser' => $baseDir . '/includes/utils/class-sql-filter-parser.php',
    'Gravity_Forms\\Gravity_SMTP\\Utils\\Source_Parser' => $baseDir . '/includes/utils/class-source-parser.php',
    'Gravity_Forms\\Gravity_Tools\\API\\Gravity_Api' => $vendorDir . '/gravityforms/gravity-tools/src/API/class-gravity-api.php',
    'Gravity_Forms\\Gravity_Tools\\API\\Oauth_Handler' => $vendorDir . '/gravityforms/gravity-tools/src/API/class-oauth-handler.php',
    'Gravity_Forms\\Gravity_Tools\\Apps\\Registers_Apps' => $vendorDir . '/gravityforms/gravity-tools/src/Apps/trait-registers-apps.php',
    'Gravity_Forms\\Gravity_Tools\\Assets\\Asset_Processor' => $vendorDir . '/gravityforms/gravity-tools/src/Assets/class-asset-processor.php',
    'Gravity_Forms\\Gravity_Tools\\Background_Processing\\Background_Process' => $vendorDir . '/gravityforms/gravity-tools/src/Background_Processing/class-background-process.php',
    'Gravity_Forms\\Gravity_Tools\\Background_Processing\\WP_Async_Request' => $vendorDir . '/gravityforms/gravity-tools/src/Background_Processing/class-wp-async-request.php',
    'Gravity_Forms\\Gravity_Tools\\Cache\\Cache' => $vendorDir . '/gravityforms/gravity-tools/src/Cache/class-cache.php',
    'Gravity_Forms\\Gravity_Tools\\Config' => $vendorDir . '/gravityforms/gravity-tools/src/class-config.php',
    'Gravity_Forms\\Gravity_Tools\\Config\\App_Config' => $vendorDir . '/gravityforms/gravity-tools/src/Config/class-app-config.php',
    'Gravity_Forms\\Gravity_Tools\\Config_Collection' => $vendorDir . '/gravityforms/gravity-tools/src/class-config-collection.php',
    'Gravity_Forms\\Gravity_Tools\\Config_Data_Parser' => $vendorDir . '/gravityforms/gravity-tools/src/class-config-data-parser.php',
    'Gravity_Forms\\Gravity_Tools\\Data\\Oauth_Data_Handler' => $vendorDir . '/gravityforms/gravity-tools/src/Data/interface-oauth-data-handler.php',
    'Gravity_Forms\\Gravity_Tools\\Data\\Transient_Strategy' => $vendorDir . '/gravityforms/gravity-tools/src/Data/class-transient-strategy.php',
    'Gravity_Forms\\Gravity_Tools\\Endpoints\\Endpoint' => $vendorDir . '/gravityforms/gravity-tools/src/Endpoints/class-endpoint.php',
    'Gravity_Forms\\Gravity_Tools\\License\\API_Response' => $vendorDir . '/gravityforms/gravity-tools/src/License/class-api-response.php',
    'Gravity_Forms\\Gravity_Tools\\License\\License_API_Connector' => $vendorDir . '/gravityforms/gravity-tools/src/License/class-license-api-connector.php',
    'Gravity_Forms\\Gravity_Tools\\License\\License_API_Response' => $vendorDir . '/gravityforms/gravity-tools/src/License/class-license-api-response.php',
    'Gravity_Forms\\Gravity_Tools\\License\\License_API_Response_Factory' => $vendorDir . '/gravityforms/gravity-tools/src/License/class-license-api-response-factory.php',
    'Gravity_Forms\\Gravity_Tools\\License\\License_Statuses' => $vendorDir . '/gravityforms/gravity-tools/src/License/class-license-statuses.php',
    'Gravity_Forms\\Gravity_Tools\\Logging\\DB_Logging_Provider' => $vendorDir . '/gravityforms/gravity-tools/src/Logging/class-db-logging-provider.php',
    'Gravity_Forms\\Gravity_Tools\\Logging\\File_Logging_Provider' => $vendorDir . '/gravityforms/gravity-tools/src/Logging/class-file-logging-provider.php',
    'Gravity_Forms\\Gravity_Tools\\Logging\\Log_Line' => $vendorDir . '/gravityforms/gravity-tools/src/Logging/class-log-line.php',
    'Gravity_Forms\\Gravity_Tools\\Logging\\Logger' => $vendorDir . '/gravityforms/gravity-tools/src/Logging/class-logger.php',
    'Gravity_Forms\\Gravity_Tools\\Logging\\Logging_Provider' => $vendorDir . '/gravityforms/gravity-tools/src/Logging/interface-logging-provider.php',
    'Gravity_Forms\\Gravity_Tools\\Logging\\Parsers\\File_Log_Parser' => $vendorDir . '/gravityforms/gravity-tools/src/Logging/parsers/class-file-log-parser.php',
    'Gravity_Forms\\Gravity_Tools\\Model\\Form_Model' => $vendorDir . '/gravityforms/gravity-tools/src/Model/class-form-model.php',
    'Gravity_Forms\\Gravity_Tools\\Providers\\Config_Collection_Service_Provider' => $vendorDir . '/gravityforms/gravity-tools/src/Providers/class-config-collection-service-provider.php',
    'Gravity_Forms\\Gravity_Tools\\Providers\\Config_Service_Provider' => $vendorDir . '/gravityforms/gravity-tools/src/Providers/class-config-service-provider.php',
    'Gravity_Forms\\Gravity_Tools\\Service_Container' => $vendorDir . '/gravityforms/gravity-tools/src/class-service-container.php',
    'Gravity_Forms\\Gravity_Tools\\Service_Provider' => $vendorDir . '/gravityforms/gravity-tools/src/class-service-provider.php',
    'Gravity_Forms\\Gravity_Tools\\Telemetry\\Telemetry_Data' => $vendorDir . '/gravityforms/gravity-tools/src/Telemetry/class-telemetry-data.php',
    'Gravity_Forms\\Gravity_Tools\\Telemetry\\Telemetry_Processor' => $vendorDir . '/gravityforms/gravity-tools/src/Telemetry/class-telemetry-processor.php',
    'Gravity_Forms\\Gravity_Tools\\Updates\\Auto_Updater' => $vendorDir . '/gravityforms/gravity-tools/src/Updates/class-auto-updater.php',
    'Gravity_Forms\\Gravity_Tools\\Updates\\Updates_Service_Provider' => $baseDir . '/includes/updates/class-updates-service-provider.php',
    'Gravity_Forms\\Gravity_Tools\\Upgrades\\Upgrade_Routines' => $vendorDir . '/gravityforms/gravity-tools/src/Upgrades/class-upgrade-routines.php',
    'Gravity_Forms\\Gravity_Tools\\Utils\\Common' => $vendorDir . '/gravityforms/gravity-tools/src/Utils/class-common.php',
    'Gravity_Forms\\Gravity_Tools\\Utils\\Utils_Service_Provider' => $baseDir . '/includes/utils/class-utils-service-provider.php',
);
