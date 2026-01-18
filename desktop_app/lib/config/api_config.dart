/// Configuration de l'API GeekBoard
/// URL du serveur et paramètres de connexion

class ApiConfig {
  // URL de base de l'API
  // Production (servo.tools)
  static const String baseUrl = 'https://servo.tools/api/v2';
  static const String siteUrl = 'https://servo.tools';
  static const String livrePoliceUrl = '$siteUrl/ajax/export_livre_police.php';
  
  // Développement local (décommenter pour tester localement)
  // static const String baseUrl = 'http://localhost:8080/api/v2';
  
  // Timeout des requêtes (en secondes)
  static const int timeout = 30;
  
  // Endpoints
  static const String loginEndpoint = '/auth/login.php';
  static const String verifyEndpoint = '/user/me.php';
  static const String dashboardStatsEndpoint = '/dashboard/index.php';
  
  // Reparations
  static const String reparationsListEndpoint = '/reparations/list.php';
  static const String reparationsGetEndpoint = '/reparations/get.php';
  static const String reparationsUpdateEndpoint = '/reparations/update.php';
  static const String reparationsCreateEndpoint = '/reparations/create.php';
  static const String reparationsGetStatusesEndpoint = '/reparations/get_statuses.php';
  static const String reparationsUpdateSpecificStatusEndpoint = '/reparations/update_specific_status.php';
  static const String reparationsUpdateStatusEndpoint = '/reparations/update_status.php';
  static const String reparationsUpdatePriceEndpoint = '/reparations/update_price.php';
  static const String reparationsCountsEndpoint = '/reparations/counts.php';
  static const String reparationsLabelEndpoint = '/reparations/label.php';
  static const String reparationsHistoryEndpoint = '/reparations/history.php';
  static const String repairAssignmentEndpoint = '/../../ajax/repair_assignment.php'; // Attempt to reach ajax folder from api/v2

  
  // Devis
  static const String devisListEndpoint = '/devis/list.php';
  static const String devisCreateEndpoint = '/devis/create.php';
  static const String devisUpdateEndpoint = '/devis/update.php';
  
  // Taches
  static const String tachesListEndpoint = '/taches/list.php';
  static const String tasksCreateEndpoint = '/taches/create.php';
  static const String tachesUpdateEndpoint = '/taches/update.php';
  
  // Knowledge
  static const String knowledgeListEndpoint = '/knowledge/list.php';
  static const String knowledgeDetailEndpoint = '/knowledge/get.php';
  
  // Inventory
  static const String inventoryListEndpoint = '/inventory/list.php';
  
  // Missions
  static const String missionsListEndpoint = '/missions/list.php';
  static const String missionsActionEndpoint = '/missions/action.php';
  
  // Presence / Absences
  static const String presenceListEndpoint = '/presence/list.php';
  
  // Formations
  static const String formationsListEndpoint = '/formations/list.php';
  static const String formationAdminDashboardEndpoint = '/formation/admin/dashboard.php';
  static const String formationAdminAssignEndpoint = '/formation/admin/assign.php';
  
  // Employees
  static const String employeesListEndpoint = '/employees/list.php';
  
  // Time Tracking
  static const String timeTrackingDashboardEndpoint = '/timetracking/dashboard.php';
  
  // Logs
  static const String logsListEndpoint = '/logs/list.php';
  
  // KPI
  static const String kpiDashboardEndpoint = '/kpi/dashboard.php';
  static const String kpiNotesEmployeesEndpoint = '/kpi/notes_employees.php';
  static const String kpiNotesStoreEndpoint = '/kpi/notes_store.php';
  static const String kpiIAProfilesEndpoint = '/kpi/ia_profiles.php';
  static const String kpiGenerateAnalysisEndpoint = '/kpi/generate_analysis.php';
  
  // Bugs
  static const String bugsListEndpoint = '/bugs/list.php';
  static const String bugsUpdateEndpoint = '/bugs/update.php';
  
  // Catalogue
  static const String catalogueListEndpoint = '/catalogue/list.php';
  static const String catalogueFiltersEndpoint = '/catalogue/filters.php';
  static const String catalogueSupplierListEndpoint = '/catalogue/supplier_list.php';
  
  // Settings
  static const String settingsGetEndpoint = '/settings/get.php';
  static const String settingsUpdateProfileEndpoint = '/settings/update_profile.php';
  static const String settingsUpdatePasswordEndpoint = '/settings/update_password.php';
  static const String settingsUpdatePreferencesEndpoint = '/settings/update_preferences.php';
  static const String settingsUpdateCompanyEndpoint = '/settings/update_company.php';
  static const String settingsNotificationsEndpoint = '/settings/notifications.php';
  static const String settingsBillingEndpoint = '/settings/billing.php';
  static const String settingsSmsEndpoint = '/settings/sms.php';
  static const String settingsRelanceEndpoint = '/settings/relance.php';
  
  // Clients
  static const String clientsListEndpoint = '/clients/list.php';
  static const String clientsCreateEndpoint = '/clients/create.php';
  static const String clientsGetEndpoint = '/clients/get.php';
  static const String clientsUpdateEndpoint = '/clients/update.php';
  static const String clientsDeleteEndpoint = '/clients/delete.php';
  
  // Commandes
  static const String commandesListEndpoint = '/commandes/list.php';
  static const String commandesCreateEndpoint = '/commandes/create.php';
  static const String commandesUpdateEndpoint = '/commandes/update.php';
  
  // Rachat
  static const String rachatListEndpoint = '/rachat/list.php';
  static const String rachatCreateEndpoint = '/rachat/create.php';
  
  // Screens
  static const String screensListEndpoint = '/screens/list.php';
  static const String screensGetEndpoint = '/screens/get.php';
  static const String screensCreateEndpoint = '/screens/create.php';
  static const String screensDeleteEndpoint = '/screens/delete.php';
  static const String screensUpdateEndpoint = '/screens/update.php';
  static const String screensAddSlideEndpoint = '/screens/add_slide.php';
  static const String screensDeleteSlideEndpoint = '/screens/delete_slide.php';
  static const String screensStateEndpoint = '/screens/state.php';
  static const String screensGetUsersEndpoint = '/screens/get_shop_users.php';
  static const String screensAssignUsersEndpoint = '/screens/assign_users.php';
  
  // Search
  static const String universalSearchEndpoint = '/search/universal.php';

  // Partners
  static const String partnersListEndpoint = '/partners/list.php';
  static const String partnersCreateEndpoint = '/partners/create.php';
  static const String partnersTransactionsEndpoint = '/partners/transactions.php';
  static const String partnersCreateTransactionEndpoint = '/partners/create_transaction.php';
  static const String partnersValidateTransactionEndpoint = '/partners/validate_transaction.php';
  static const String partnersSendSmsEndpoint = '/partners/send_sms.php';

  // Suppliers
  static const String suppliersListEndpoint = '/suppliers/list.php';
  static const String suppliersCreateEndpoint = '/suppliers/create.php';
  static const String suppliersDeleteEndpoint = '/suppliers/delete.php';
  static const String suppliersAuthEndpoint = '/suppliers/auth.php';

  // SMS
  static const String smsListEndpoint = '/sms/list.php';
  static const String smsResendEndpoint = '/sms/resend.php';
  
  // SMS Templates
  // SMS Templates & Workflow
  static const String smsTemplatesEndpoint = '/sms/templates.php'; // New [GET]
  static const String smsPreviewEndpoint = '/sms/preview.php'; // New [POST]
  static const String smsSendEndpoint = '/sms/send.php'; // New [POST]

  // Devis
  static const String devisDetailsEndpoint = '/devis/details.php';
  static const String devisUpdateStatusEndpoint = '/devis/update_status.php';
  static const String devisBatchSendEndpoint = '/devis/batch_send.php'; // New [POST]
  static const String smsAiGenerateEndpoint = '/sms/ai_generate.php'; // New [POST]

  // Legacy / Management (kept for compatibility if needed)
  static const String smsTemplatesListEndpoint = '/sms/templates/list.php';
  static const String smsTemplatesCreateEndpoint = '/sms/templates/create.php';
  static const String smsTemplatesUpdateEndpoint = '/sms/templates/update.php';
  static const String smsTemplatesDeleteEndpoint = '/sms/templates/delete.php';
  static const String smsTemplatesToggleEndpoint = '/sms/templates/toggle.php';
  static const String smsTemplatesVariablesEndpoint = '/sms/templates/variables.php';
  static const String smsSendTemplateEndpoint = '/sms/send_template.php';
}
