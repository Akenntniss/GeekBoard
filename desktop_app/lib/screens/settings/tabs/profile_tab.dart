import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../../services/auth_service.dart';
import '../../../services/api_service.dart';
import '../../../config/api_config.dart';

class ProfileTab extends StatefulWidget {
  final Map<String, dynamic>? userData;
  const ProfileTab({super.key, this.userData});

  @override
  State<ProfileTab> createState() => _ProfileTabState();
}

class _ProfileTabState extends State<ProfileTab> {
  late final ApiService _apiService;
  final _nomCtrl = TextEditingController();
  final _prenomCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _apiService = context.read<AuthService>().getApiService();
    _nomCtrl.text = widget.userData?['nom'] ?? '';
    _prenomCtrl.text = widget.userData?['prenom'] ?? '';
    _emailCtrl.text = widget.userData?['email'] ?? '';
    _phoneCtrl.text = widget.userData?['telephone'] ?? '';
  }

  @override
  void dispose() {
    _nomCtrl.dispose(); _prenomCtrl.dispose(); _emailCtrl.dispose(); _phoneCtrl.dispose();
    super.dispose();
  }

  Future<void> _updateProfile() async {
    setState(() => _isLoading = true);
     try {
       await _apiService.post(ApiConfig.settingsUpdateProfileEndpoint, {
         'nom': _nomCtrl.text,
         'prenom': _prenomCtrl.text,
         'email': _emailCtrl.text,
         'telephone': _phoneCtrl.text,
       });
       if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Profil mis à jour")));
     } catch (e) {
       if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur: $e")));
     } finally {
       if (mounted) setState(() => _isLoading = false);
     }
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.only(bottom: 24),
      child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildTextField("Nom", _nomCtrl),
        _buildTextField("Prénom", _prenomCtrl),
        _buildTextField("Email", _emailCtrl),
        _buildTextField("Téléphone", _phoneCtrl),
        const SizedBox(height: 20),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: _isLoading ? null : _updateProfile,
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.blue,
              padding: const EdgeInsets.symmetric(vertical: 16),
            ),
            child: _isLoading 
              ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
              : const Text("Enregistrer"),
          ),
        ),
        ],
      ),
    );
  }

  Widget _buildTextField(String label, TextEditingController ctrl) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: TextStyle(color: Theme.of(context).textTheme.bodyMedium?.color, fontSize: 14)),
          const SizedBox(height: 8),
          TextField(
            controller: ctrl,
            style: TextStyle(color: Theme.of(context).textTheme.bodyLarge?.color),
            decoration: InputDecoration(
              filled: true,
              fillColor: Theme.of(context).cardColor,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Theme.of(context).dividerColor)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            ),
          ),
        ],
      ),
    );
  }
}
