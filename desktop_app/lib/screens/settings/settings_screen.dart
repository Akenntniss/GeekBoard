import 'package:flutter/material.dart';
import 'package:geekboard_desktop/widgets/app_shell.dart';
import 'package:geekboard_desktop/services/api_service.dart';
import 'package:geekboard_desktop/config/api_config.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  final ApiService _apiService = ApiService();
  bool _isLoading = true;
  int _selectedIndex = 0; // 0: Profile, 1: Security, 2: Preferences, 3: Company (Admin)
  bool _isAdmin = false;
  
  // Data
  Map<String, dynamic>? _userData;
  Map<String, dynamic>? _preferences;
  Map<String, dynamic>? _companySettings;

  // Controllers
  final _nomCtrl = TextEditingController();
  final _prenomCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  
  final _currentPassCtrl = TextEditingController();
  final _newPassCtrl = TextEditingController();
  final _confirmPassCtrl = TextEditingController();

  final _companyNameCtrl = TextEditingController();
  final _companyPhoneCtrl = TextEditingController();
  final _companyEmailCtrl = TextEditingController();
  final _companyAddressCtrl = TextEditingController();
  final _companySiretCtrl = TextEditingController();
  final _companyHoursCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadSettings();
  }
  
  @override
  void dispose() {
    _nomCtrl.dispose(); _prenomCtrl.dispose(); _emailCtrl.dispose(); _phoneCtrl.dispose();
    _currentPassCtrl.dispose(); _newPassCtrl.dispose(); _confirmPassCtrl.dispose();
    _companyNameCtrl.dispose(); _companyPhoneCtrl.dispose(); _companyEmailCtrl.dispose();
    _companyAddressCtrl.dispose(); _companySiretCtrl.dispose(); _companyHoursCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadSettings() async {
    setState(() => _isLoading = true);
    try {
      final data = await _apiService.get(ApiConfig.settingsGetEndpoint);
      if (mounted && data != null) {
        _userData = data['profile'];
        _preferences = data['preferences'];
        _companySettings = data['company'];
        _isAdmin = data['is_admin'] ?? false;
        
        // Populate Profile
        _nomCtrl.text = _userData?['nom'] ?? '';
        _prenomCtrl.text = _userData?['prenom'] ?? '';
        _emailCtrl.text = _userData?['email'] ?? '';
        _phoneCtrl.text = _userData?['telephone'] ?? '';
        
        // Populate Company
        _companyNameCtrl.text = _companySettings?['company_name'] ?? '';
        _companyPhoneCtrl.text = _companySettings?['company_phone'] ?? '';
        _companyEmailCtrl.text = _companySettings?['company_email'] ?? '';
        _companyAddressCtrl.text = _companySettings?['company_address'] ?? '';
        _companySiretCtrl.text = _companySettings?['company_number'] ?? '';
        _companyHoursCtrl.text = _companySettings?['company_hours'] ?? '';
        
        setState(() => _isLoading = false);
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _updateProfile() async {
     try {
       await _apiService.post(ApiConfig.settingsUpdateProfileEndpoint, {
         'nom': _nomCtrl.text,
         'prenom': _prenomCtrl.text,
         'email': _emailCtrl.text,
         'telephone': _phoneCtrl.text,
       });
       ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Profil mis à jour")));
     } catch (e) {
       ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur: $e")));
     }
  }

  Future<void> _updatePassword() async {
    if (_newPassCtrl.text != _confirmPassCtrl.text) {
       ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Les mots de passe ne correspondent pas")));
       return;
    }
    try {
       await _apiService.post(ApiConfig.settingsUpdatePasswordEndpoint, {
         'current_password': _currentPassCtrl.text,
         'new_password': _newPassCtrl.text,
       });
       ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Mot de passe mis à jour")));
       _currentPassCtrl.clear(); _newPassCtrl.clear(); _confirmPassCtrl.clear();
     } catch (e) {
       ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur: $e")));
     }
  }
  
  Future<void> _updateCompany() async {
     try {
       await _apiService.post(ApiConfig.settingsUpdateCompanyEndpoint, {
         'company_name': _companyNameCtrl.text,
         'company_phone': _companyPhoneCtrl.text,
         'company_email': _companyEmailCtrl.text,
         'company_address': _companyAddressCtrl.text,
         'company_number': _companySiretCtrl.text,
         'company_hours': _companyHoursCtrl.text,
       });
       ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Paramètres entreprise sauvegardés")));
     } catch (e) {
       ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur: $e")));
     }
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      currentRoute: '/settings',
      content: Scaffold(
        backgroundColor: const Color(0xFF0F172A),
        body: _isLoading 
            ? const Center(child: CircularProgressIndicator())
            : Row(
              children: [
                // Sidebar
                Container(
                  width: 250,
                  color: const Color(0xFF1E293B),
                  child: Column(
                    children: [
                      const Padding(
                        padding: EdgeInsets.all(24.0),
                        child: Text("Paramètres", style: TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold)),
                      ),
                      _buildNavItem(0, Icons.person, "Profil"),
                      _buildNavItem(1, Icons.lock, "Sécurité"),
                      _buildNavItem(2, Icons.tune, "Préférences"),
                      if (_isAdmin) _buildNavItem(3, Icons.business, "Entreprise"),
                    ],
                  ),
                ),
                
                // Content
                Expanded(
                  child: Container(
                    padding: const EdgeInsets.all(32),
                    child: SingleChildScrollView(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                           Text(
                             _getTitle(_selectedIndex),
                             style: const TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.bold),
                           ),
                           const SizedBox(height: 32),
                           _buildContent(),
                        ],
                      ),
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
      case 3: return "Entreprise";
      default: return "";
    }
  }
  
  Widget _buildNavItem(int index, IconData icon, String label) {
    final isSelected = _selectedIndex == index;
    return InkWell(
      onTap: () => setState(() => _selectedIndex = index),
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: isSelected ? Colors.blue : Colors.transparent,
          borderRadius: BorderRadius.circular(8),
        ),
        child: Row(
          children: [
            Icon(icon, color: isSelected ? Colors.white : Colors.grey),
            const SizedBox(width: 12),
            Text(label, style: TextStyle(color: isSelected ? Colors.white : Colors.grey, fontWeight: FontWeight.w500)),
          ],
        ),
      ),
    );
  }
  
  Widget _buildContent() {
    switch (_selectedIndex) {
      case 0: return _buildProfileForm();
      case 1: return _buildSecurityForm();
      case 2: return const Text("Comming Soon: Preferences", style: TextStyle(color: Colors.grey)); // Placeholder
      case 3: return _buildCompanyForm();
      default: return const SizedBox.shrink();
    }
  }

  Widget _buildProfileForm() {
    return Column(
      children: [
        _buildTextField("Nom", _nomCtrl),
        _buildTextField("Prénom", _prenomCtrl),
        _buildTextField("Email", _emailCtrl),
        _buildTextField("Téléphone", _phoneCtrl),
        const SizedBox(height: 20),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: _updateProfile,
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.blue,
              padding: const EdgeInsets.symmetric(vertical: 16),
            ),
            child: const Text("Enregistrer"),
          ),
        ),
      ],
    );
  }

  Widget _buildSecurityForm() {
    return Column(
      children: [
        _buildTextField("Mot de passe actuel", _currentPassCtrl, obscure: true),
        _buildTextField("Nouveau mot de passe", _newPassCtrl, obscure: true),
        _buildTextField("Confirmer le mot de passe", _confirmPassCtrl, obscure: true),
        const SizedBox(height: 20),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: _updatePassword,
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.blue,
              padding: const EdgeInsets.symmetric(vertical: 16),
            ),
            child: const Text("Mettre à jour le mot de passe"),
          ),
        ),
      ],
    );
  }
  
  Widget _buildCompanyForm() {
    return Column(
      children: [
         _buildTextField("Nom de l'entreprise", _companyNameCtrl),
         _buildTextField("Adresse", _companyAddressCtrl, maxLines: 3),
         _buildTextField("Téléphone", _companyPhoneCtrl),
         _buildTextField("Email", _companyEmailCtrl),
         _buildTextField("SIRET", _companySiretCtrl),
         _buildTextField("Horaires", _companyHoursCtrl),
         const SizedBox(height: 20),
         SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: _updateCompany,
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.blue,
              padding: const EdgeInsets.symmetric(vertical: 16),
            ),
            child: const Text("Sauvegarder les paramètres"),
          ),
        ),
      ],
    );
  }

  Widget _buildTextField(String label, TextEditingController ctrl, {bool obscure = false, int maxLines = 1}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(color: Colors.grey, fontSize: 14)),
          const SizedBox(height: 8),
          TextField(
            controller: ctrl,
            obscureText: obscure,
            maxLines: maxLines,
            style: const TextStyle(color: Colors.white),
            decoration: InputDecoration(
              filled: true,
              fillColor: const Color(0xFF1E293B),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            ),
          ),
        ],
      ),
    );
  }
}
