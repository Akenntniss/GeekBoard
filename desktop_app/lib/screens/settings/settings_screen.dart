import 'package:flutter/material.dart';
import 'package:geekboard_desktop/widgets/app_shell.dart';
import 'package:geekboard_desktop/services/api_service.dart';
import 'package:geekboard_desktop/config/api_config.dart';
import 'supplier_connection_screen.dart';

// Tabs
import 'tabs/profile_tab.dart';
import 'tabs/security_tab.dart';
import 'tabs/preferences_tab.dart';
import 'tabs/notifications_tab.dart';
import 'tabs/quote_relance_tab.dart';
import 'tabs/system_tab.dart';
import 'tabs/labels_tab.dart';
import 'tabs/extension_tab.dart';
import 'tabs/sms_consumption_tab.dart';
import 'tabs/billing_tab.dart';

class SettingsScreen extends StatefulWidget {
  final int initialTab;
  const SettingsScreen({super.key, this.initialTab = 0});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  final ApiService _apiService = ApiService();
  bool _isLoading = true;
  late int _selectedIndex;
  bool _isAdmin = false;
  
  // Data
  Map<String, dynamic>? _userData;
  Map<String, dynamic>? _preferences;

  @override
  void initState() {
    super.initState();
    _selectedIndex = widget.initialTab;
    _loadSettings();
  }

  Future<void> _loadSettings() async {
    setState(() => _isLoading = true);
    try {
      final data = await _apiService.get(ApiConfig.settingsGetEndpoint);
      if (mounted && data != null) {
        setState(() {
          _userData = data['profile'];
          _preferences = data['preferences'];
          _isAdmin = data['is_admin'] ?? false;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      currentRoute: '/settings',
      content: Scaffold(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        body: _isLoading 
            ? const Center(child: CircularProgressIndicator())
            : Row(
              children: [
                // Sidebar
                Container(
                  width: 280,
                  color: Theme.of(context).cardColor,
                  child: Column(
                    children: [
                      Padding(
                        padding: const EdgeInsets.all(24.0),
                        child: Row(
                        children: [
                            Icon(Icons.settings, color: Theme.of(context).primaryColor, size: 28),
                            SizedBox(width: 12),
                            Text("Paramètres", style: TextStyle(color: Theme.of(context).textTheme.bodyLarge?.color, fontSize: 22, fontWeight: FontWeight.bold)),
                          ],
                        ),
                      ),
                      Expanded(
                        child: SingleChildScrollView(
                          child: Column(
                            children: [
                              _buildNavItem(0, Icons.person, "Mon profil"),
                              _buildNavItem(1, Icons.lock, "Sécurité"),
                              _buildNavItem(2, Icons.tune, "Préférences"),
                              _buildNavItem(3, Icons.notifications, "Préférences notification"),
                              _buildNavItem(4, Icons.schedule_send, "Relance devis"),
                              _buildNavItem(5, Icons.business, "Info Entreprise"),
                              _buildNavItem(6, Icons.label, "Étiquettes"),
                              _buildNavItem(7, Icons.extension, "Extension Fournisseur"),
                              _buildNavItem(8, Icons.link, "Catalogue fournisseur"),
                              _buildNavItem(9, Icons.sms, "Consommation SMS"),
                              _buildNavItem(10, Icons.receipt_long, "Facturation"),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                
                // Content
                Expanded(
                  child: Container(
                    color: Theme.of(context).scaffoldBackgroundColor,
                    padding: const EdgeInsets.all(32),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                         Text(
                           _getTitle(_selectedIndex),
                           style: TextStyle(color: Theme.of(context).textTheme.titleLarge?.color, fontSize: 32, fontWeight: FontWeight.bold),
                         ),
                         const SizedBox(height: 32),
                         Expanded(
                           child: _buildContent(),
                         ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
      ),
    );
  }
  
  String _getTitle(int index) {
    switch (index) {
      case 0: return "Mon Profil";
      case 1: return "Sécurité";
      case 2: return "Préférences";
      case 3: return "Notifications";
      case 4: return "Relance Devis";
      case 5: return "Info Entreprise";
      case 6: return "Étiquettes";
      case 7: return "Extension Chrome";
      case 8: return "Connexion Fournisseurs";
      case 9: return "Consommation SMS";
      case 10: return "Facturation";
      default: return "";
    }
  }
  
  Widget _buildNavItem(int index, IconData icon, String label) {
    final isSelected = _selectedIndex == index;
    return InkWell(
      onTap: () => setState(() => _selectedIndex = index),
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: isSelected ? Colors.blue.withOpacity(0.2) : Colors.transparent,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: isSelected ? Colors.blue.withOpacity(0.5) : Colors.transparent),
        ),
        child: Row(
          children: [
            Icon(icon, color: isSelected ? Colors.blue : Colors.grey, size: 20),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                label, 
                style: TextStyle(
                  color: isSelected ? Theme.of(context).primaryColor : Theme.of(context).textTheme.bodyMedium?.color?.withOpacity(0.7), 
                  fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
                  fontSize: 14
                )
              ),
            ),
          ],
        ),
      ),
    );
  }
  
  Widget _buildContent() {
    switch (_selectedIndex) {
      case 0: return ProfileTab(userData: _userData);
      case 1: return const SecurityTab();
      case 2: return PreferencesTab(preferences: _preferences);
      case 3: return NotificationsTab(isAdmin: _isAdmin);
      case 4: return const QuoteRelanceTab();
      case 5: return const SystemTab();
      case 6: return const LabelsTab();
      case 7: return const ExtensionTab();
      case 8: return const SupplierSettingsView();
      case 9: return const SmsConsumptionTab();
      case 10: return const BillingTab();
      default: return const SizedBox.shrink();
    }
  }
}

